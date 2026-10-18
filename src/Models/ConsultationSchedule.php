<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;

/**
 * O horário semanal de uma consulta.
 *
 * Uma linha por dia da semana e hora: «terças às 21h». Não é uma vaga — é a
 * regra que as gera. Ver App\Services\GeradorDeVagas.
 *
 * `weekday` é 1 (segunda) a 7 (domingo), como o ISO-8601 e como o `N` do PHP.
 * Não é 0 a 6 de propósito: o zero é segunda numas linguagens e domingo noutras,
 * e um horário que muda de dia conforme quem o lê não é um horário.
 */
final class ConsultationSchedule
{
    /** Os dias, pela ordem em que se dizem. */
    public const DIAS = [
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /** @return array<int,array<string,mixed>> */
    public static function forConsultation(int $consultationId): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM consultation_schedules
              WHERE consultation_id = :id ORDER BY weekday, start_time',
            ['id' => $consultationId]
        );
    }

    /**
     * As linhas que valem para um dia — activas, do dia da semana certo, e dentro
     * das datas em que valem.
     *
     * A consulta tem de estar publicada **e** marcável: uma consulta sem agenda
     * que tivesse uma linha de horário por engano gerava vagas para uma hora que
     * a página nunca mostra, e essas vagas ficavam a ocupar o calendário do
     * backoffice sem nada do lado de fora a que correspondessem.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function activeOn(\DateTimeImmutable $day): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT h.*, c.capacity AS consultation_capacity
               FROM consultation_schedules h
               JOIN consultations c ON c.id = h.consultation_id
              WHERE h.is_active = 1
                AND c.is_published = 1
                AND c.is_bookable = 1
                AND h.weekday = :weekday
                AND (h.starts_on IS NULL OR h.starts_on <= :dia1)
                AND (h.ends_on   IS NULL OR h.ends_on   >= :dia2)
              ORDER BY h.start_time',
            /* O mesmo dia escrito duas vezes: sem emulação de prepares, o PDO não
               deixa reutilizar um nome de parâmetro em dois sítios. */
            [
                'weekday' => (int)$day->format('N'),
                'dia1'    => $day->format('Y-m-d'),
                'dia2'    => $day->format('Y-m-d'),
            ]
        );
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d): int
    {
        return App::instance()->db()->insert('consultation_schedules', self::columns($d) + [
            'consultation_id' => (int)$d['consultation_id'],
        ]);
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, array $d): void
    {
        App::instance()->db()->update('consultation_schedules', self::columns($d), 'id = :id', ['id' => $id]);
    }

    /**
     * Apagar uma linha do horário **não** apaga as vagas que ela gerou.
     *
     * É deliberado, e a chave estrangeira diz o mesmo com `ON DELETE SET NULL`:
     * uma vaga que já tem gente marcada não pode desaparecer porque alguém mexeu
     * no horário. O que fica é uma vaga sem horário — igual a uma avulsa — e
     * fecha-se ou apaga-se uma a uma, com a lista de quem lá está à vista.
     */
    public static function delete(int $id): void
    {
        App::instance()->db()->delete('consultation_schedules', 'id = :id', ['id' => $id]);
    }

    /** Vagas ainda por acontecer que esta linha gerou. O backoffice avisa. */
    public static function futureSlots(int $id): int
    {
        return (int)App::instance()->db()->fetchColumn(
            'SELECT COUNT(*) FROM consultation_slots WHERE schedule_id = :id AND starts_at >= NOW()',
            ['id' => $id]
        );
    }

    /**
     * @param array<string,mixed> $d
     * @return array<string,mixed>
     */
    private static function columns(array $d): array
    {
        $weekday  = (int)($d['weekday'] ?? 1);
        $capacity = trim((string)($d['capacity'] ?? ''));

        return [
            'weekday'    => isset(self::DIAS[$weekday]) ? $weekday : 1,
            'start_time' => self::time((string)($d['start_time'] ?? '')),
            // Vazio quer dizer «a da consulta», e é diferente de zero.
            'capacity'   => $capacity === '' ? null : max(1, (int)$capacity),
            'starts_on'  => self::date((string)($d['starts_on'] ?? '')),
            'ends_on'    => self::date((string)($d['ends_on'] ?? '')),
            'is_active'  => !empty($d['is_active']) ? 1 : 0,
        ];
    }

    /**
     * A hora, lida com formato exacto.
     *
     * O `type=time` do browser manda `HH:MM`, mas um pedido montado à mão manda o
     * que quiser. Fora do formato valem as nove da noite, que é a hora a que a
     * Casa atende — e o editor vê-a na lista e corrige.
     */
    private static function time(string $raw): string
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)/', $raw, $m) === 1) {
            return $m[1] . ':' . $m[2] . ':00';
        }
        return '21:00:00';
    }

    private static function date(string $raw): ?string
    {
        if ($raw === '') { return null; }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        return ($d !== false && $d->format('Y-m-d') === $raw) ? $raw : null;
    }
}
