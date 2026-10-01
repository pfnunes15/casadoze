<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;

/**
 * Uma vaga: uma consulta, um dia, uma hora, e lugares que se descontam.
 *
 * O que aqui interessa está todo em dois métodos — `hold` e `release` — e o
 * resto é leitura. Ver a nota em 0001_as_consultas.sql para porque é que
 * `seats_taken` é uma coluna e não uma contagem.
 */
final class ConsultationSlot
{
    /**
     * Segurar lugares.
     *
     * Uma instrução, e é o `WHERE` que faz o trabalho: duas pessoas a marcar a
     * mesma hora no mesmo instante fazem dois UPDATE, e o segundo actualiza zero
     * linhas. Devolve `false` a quem chegou tarde, sem nunca ter havido dois
     * donos da mesma hora.
     *
     * Contar primeiro e inserir depois seria a versão errada disto: entre a
     * contagem e a inserção cabe outra marcação, e a lotação passa.
     */
    public static function hold(int $slotId, int $people): bool
    {
        $n = App::instance()->db()->query(
            'UPDATE consultation_slots
                SET seats_taken = seats_taken + :n
              WHERE id = :id
                AND is_open = 1
                AND seats_taken + :n2 <= capacity',
            ['n' => $people, 'n2' => $people, 'id' => $slotId]
        )->rowCount();

        return $n === 1;
    }

    /**
     * A marcação viva que choca com esta vaga, se houver alguma.
     *
     * **Isto não existe no enoturismo de onde o resto vem, e tem de existir
     * aqui.** Lá uma visita é de grupo e há mais do que um guia: duas visitas à
     * mesma hora são duas salas, e nada se sobrepõe. Aqui é o Zé, e é um só — uma
     * leitura de três cartas às 21h e um Baralho Cigano às 21h são a mesma pessoa
     * em dois sítios. A lotação da vaga não apanha isto, porque são vagas
     * diferentes e cada uma tem o seu lugar livre.
     *
     * Duas vagas chocam quando se cruzam no tempo: `a < fim(b)` e `b < fim(a)`.
     * A duração vem da consulta e não da vaga, que é onde está escrita.
     *
     * **Esta leitura só vale com a agenda trancada**, e quem a tranca é
     * Booking::create — ver a nota que lá está sobre porque é um tranco com nome
     * e não um `FOR UPDATE`.
     *
     * @return array<string,mixed>|null
     */
    public static function conflict(int $slotId): ?array
    {
        return App::instance()->db()->fetch(
            "SELECT b.code, b.name, outra.starts_at, c.name AS consultation_name
               FROM consultation_slots alvo
               JOIN consultations ca ON ca.id = alvo.consultation_id
               JOIN consultation_slots outra
                      ON outra.id <> alvo.id
                     AND outra.starts_at < DATE_ADD(alvo.starts_at, INTERVAL ca.duration_min MINUTE)
               JOIN consultations c ON c.id = outra.consultation_id
                     AND DATE_ADD(outra.starts_at, INTERVAL c.duration_min MINUTE) > alvo.starts_at
               JOIN bookings b ON b.slot_id = outra.id AND b.status = 'confirmada'
              WHERE alvo.id = :id
              LIMIT 1",
            ['id' => $slotId]
        );
    }

    /**
     * Devolver lugares.
     *
     * `GREATEST(..., 0)` porque uma coluna sem sinal a descer abaixo de zero é
     * um erro de base de dados, e o que se quer aqui é que um cancelamento
     * repetido não rebente — devolve os lugares uma vez e as outras não faz mal
     * a ninguém.
     */
    public static function release(int $slotId, int $people): void
    {
        App::instance()->db()->query(
            'UPDATE consultation_slots
                SET seats_taken = GREATEST(CAST(seats_taken AS SIGNED) - :n, 0)
              WHERE id = :id',
            ['n' => $people, 'id' => $slotId]
        );
    }

    /**
     * As horas que alguém abriu à mão para esta consulta, daqui para a frente.
     *
     * Numa consulta com horário, são as horas extra — quem telefonou a pedir um
     * dia que o horário não tem. Numa sem horário, são a agenda toda.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function datedFor(int $consultationId): array
    {
        return App::instance()->db()->fetchAll(
            "SELECT * FROM consultation_slots
              WHERE consultation_id = :id AND source = 'avulsa' AND starts_at >= CURDATE()
              ORDER BY starts_at",
            ['id' => $consultationId]
        );
    }

    /**
     * Até quando é que o calendário está preenchido.
     *
     * A vaga mais distante que existe, ou `null` quando não há nenhuma. Serve
     * para o calendário poder explicar um mês vazio em vez de o mostrar e deixar
     * quem o vê a pensar que alguma coisa se partiu.
     */
    public static function horizonte(): ?\DateTimeImmutable
    {
        $ultima = App::instance()->db()->fetchColumn(
            'SELECT MAX(starts_at) FROM consultation_slots WHERE starts_at >= NOW()'
        );

        return ($ultima === null || $ultima === false)
            ? null
            : new \DateTimeImmutable((string)$ultima);
    }

    public static function find(int $id): ?array
    {
        return App::instance()->db()->fetch(
            'SELECT s.*, c.name AS consultation_name, c.slug AS consultation_slug,
                    c.duration_min, c.price_cents, c.mode, c.max_party, c.notice_hours
               FROM consultation_slots s
               JOIN consultations c ON c.id = s.consultation_id
              WHERE s.id = :id',
            ['id' => $id]
        );
    }

    /**
     * As vagas de uma consulta que ainda se podem marcar.
     *
     * Três condições, e nenhuma é decoração: aberta, com lugar, e com a
     * antecedência que a consulta pede. A antecedência é da consulta e não do
     * sistema porque uma leitura completa precisa de mais aviso do que três
     * cartas — quem prepara a mesa sabe porquê.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function bookable(int $consultationId, int $days = 90): array
    {
        /* O número de dias entra no SQL por interpolação e não como parâmetro:
           o INTERVAL do MySQL não aceita um placeholder na quantidade. Está
           limitado a 1–365 na linha acima, que é o que torna a interpolação
           segura — um inteiro dentro de limites e não um valor que veio de fora. */
        $days = max(1, min(365, $days));

        return App::instance()->db()->fetchAll(
            "SELECT s.*, (s.capacity - s.seats_taken) AS livres
               FROM consultation_slots s
               JOIN consultations c ON c.id = s.consultation_id
              WHERE s.consultation_id = :id
                AND s.is_open = 1
                AND s.seats_taken < s.capacity
                AND s.starts_at >= DATE_ADD(NOW(), INTERVAL c.notice_hours HOUR)
                AND s.starts_at <  DATE_ADD(NOW(), INTERVAL {$days} DAY)
              ORDER BY s.starts_at",
            ['id' => $consultationId]
        );
    }

    /**
     * Tudo o que há num intervalo, aberto ou fechado, cheio ou vazio — para o
     * backoffice, que precisa de ver o que o site não mostra.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function between(\DateTimeImmutable $from, \DateTimeImmutable $to, ?int $consultationId = null): array
    {
        $where  = 's.starts_at >= :de AND s.starts_at < :ate';
        $params = ['de' => $from->format('Y-m-d H:i:s'), 'ate' => $to->format('Y-m-d H:i:s')];

        if ($consultationId !== null) {
            $where .= ' AND s.consultation_id = :cid';
            $params['cid'] = $consultationId;
        }

        return App::instance()->db()->fetchAll(
            "SELECT s.*, c.name AS consultation_name, c.duration_min
               FROM consultation_slots s
               JOIN consultations c ON c.id = s.consultation_id
              WHERE {$where}
              ORDER BY s.starts_at, c.name",
            $params
        );
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d): int
    {
        return App::instance()->db()->insert('consultation_slots', [
            'consultation_id' => (int)$d['consultation_id'],
            'schedule_id'     => isset($d['schedule_id']) ? (int)$d['schedule_id'] : null,
            'starts_at'       => (string)$d['starts_at'],
            'capacity'        => max(1, (int)$d['capacity']),
            'is_open'         => isset($d['is_open']) && !$d['is_open'] ? 0 : 1,
            'source'          => ($d['source'] ?? 'avulsa') === 'horario' ? 'horario' : 'avulsa',
            'note'            => mb_substr(trim((string)($d['note'] ?? '')), 0, 190),
        ]);
    }

    /**
     * Mudar a lotação de uma vaga.
     *
     * Nunca abaixo do que já lá está marcado: baixar a lotação para menos do que
     * os lugares tomados não desmarca ninguém, só faz a vaga mentir. Quem quiser
     * mesmo tirar gente, cancela as marcações primeiro.
     */
    public static function setCapacity(int $id, int $capacity): void
    {
        App::instance()->db()->query(
            'UPDATE consultation_slots SET capacity = GREATEST(:cap, seats_taken, 1) WHERE id = :id',
            ['cap' => $capacity, 'id' => $id]
        );
    }

    public static function setOpen(int $id, bool $open): void
    {
        App::instance()->db()->update('consultation_slots', ['is_open' => $open ? 1 : 0], 'id = :id', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        App::instance()->db()->delete('consultation_slots', 'id = :id', ['id' => $id]);
    }

    /** Marcações vivas nesta vaga. O backoffice pergunta antes de a apagar. */
    public static function liveBookings(int $id): int
    {
        return (int)App::instance()->db()->fetchColumn(
            "SELECT COUNT(*) FROM bookings WHERE slot_id = :id AND status = 'confirmada'",
            ['id' => $id]
        );
    }

    /**
     * Fechar as vagas de um dia, sem as apagar.
     *
     * É o que um encerramento faz. A diferença importa quando já há gente
     * marcada: as marcações continuam a existir, o backoffice mostra-as, e
     * alguém tem de avisar essas pessoas. Um fecho que apagasse as vagas apagava
     * com elas a lista de quem era preciso avisar.
     *
     * @return int quantas foram fechadas
     */
    public static function closeDay(string $ymd, ?int $consultationId = null): int
    {
        $where  = 'DATE(starts_at) = :dia AND is_open = 1';
        $params = ['dia' => $ymd];

        if ($consultationId !== null) {
            $where .= ' AND consultation_id = :cid';
            $params['cid'] = $consultationId;
        }

        return App::instance()->db()
            ->query("UPDATE consultation_slots SET is_open = 0 WHERE {$where}", $params)
            ->rowCount();
    }
}
