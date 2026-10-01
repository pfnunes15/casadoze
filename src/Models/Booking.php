<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;

/**
 * Uma marcação.
 *
 * O lugar segura-se em ConsultationSlot::hold antes de isto ser escrito, e é
 * essa a ordem que interessa: primeiro o lugar, depois o registo. Ao contrário,
 * uma marcação escrita sem lugar seguro é uma promessa que a Casa não pode
 * cumprir. Ver `create`.
 *
 * Cancelar não apaga: muda o estado e devolve os lugares. O registo do que
 * existiu fica, que é o que permite responder a «mas eu tinha marcado».
 */
final class Booking
{
    /**
     * Marcar.
     *
     * Devolve a marcação escrita, ou `null` quando a hora já não estava livre —
     * que é a única maneira honesta de dizer «chegou tarde», porque só se sabe ao
     * tentar.
     *
     * A transacção existe para o caso do meio: lugares segurados e a inserção a
     * falhar deixaria a vaga a dizer que está mais cheia do que está, e ninguém
     * daria por isso senão quando a última pessoa ouvisse que não há hora.
     *
     * **A ordem dos três passos não é arbitrária.** Primeiro perguntar se alguma
     * marcação viva choca com esta hora. Depois segurar o lugar, que é o que
     * conta a lotação da própria vaga. Só então escrever. Perguntar depois de
     * segurar obrigava a devolver o lugar à mão num caminho de erro, e um caminho
     * de erro que devolve lugares é um caminho que um dia se esquece.
     *
     * **Porque é que a agenda se tranca com um nome, e não com `FOR UPDATE`.**
     * A primeira versão disto lia os conflitos com `FOR UPDATE`, que é a resposta
     * de manual. Duas marcações ao mesmo tempo para horas que se cruzam davam um
     * *deadlock* do InnoDB — cada transacção trancava as linhas da sua vaga e
     * depois pedia as da outra, em ordens opostas. O resultado ficava correcto,
     * porque o MySQL mata uma das duas, mas a que morria levava uma excepção em
     * vez de ouvir «essa hora já não está livre»: a pessoa certa recebia um erro
     * de servidor.
     *
     * Um tranco com nome não tem ordem para inverter, e por isso não pode haver
     * *deadlock*. E diz o que é: **a agenda do Zé é uma só, e uma pessoa de cada
     * vez é que escreve nela.** Tranca-se a agenda, não as linhas que por acaso
     * uma consulta leu. O custo é as marcações ficarem em fila — numa casa com
     * uma pessoa a atender, uma fila que nunca tem mais do que uma pessoa.
     *
     * @param array<string,mixed> $d
     * @return array<string,mixed>|null
     * @throws \RuntimeException quando a agenda não se deixa trancar
     */
    public static function create(array $d): ?array
    {
        $slotId = (int)$d['slot_id'];
        $people = max(1, (int)($d['people'] ?? 1));

        return self::comAAgendaTrancada(static fn(): ?array =>
            App::instance()->db()->transaction(static function () use ($d, $slotId, $people): ?array {
                if (ConsultationSlot::conflict($slotId) !== null) {
                    return null;
                }
                if (!ConsultationSlot::hold($slotId, $people)) {
                    return null;
                }

                $code  = self::freeCode();
                $token = bin2hex(random_bytes(32));

                $id = App::instance()->db()->insert('bookings', [
                    'slot_id'    => $slotId,
                    'code'       => $code,
                    'token'      => $token,
                    'name'       => mb_substr(trim((string)$d['name']), 0, 190),
                    'email'      => mb_substr(mb_strtolower(trim((string)$d['email'])), 0, 190),
                    'phone'      => mb_substr(trim((string)($d['phone'] ?? '')), 0, 60),
                    'people'     => $people,
                    'mode'       => ($d['mode'] ?? 'presencial') === 'online' ? 'online' : 'presencial',
                    'note'       => (string)($d['note'] ?? ''),
                    'locale'     => current_locale(),
                    'ip'         => (string)($d['ip'] ?? ''),
                    'user_agent' => mb_substr((string)($d['user_agent'] ?? ''), 0, 255),
                ]);

                return self::find($id);
            })
        );
    }

    /**
     * O nome do tranco da agenda.
     *
     * Leva o nome da base de dados porque um tranco com nome no MySQL é do
     * servidor e não do esquema: dois sites do mesmo servidor com o mesmo nome de
     * tranco punham as marcações de um à espera das do outro, e ninguém daria por
     * isso senão num dia cheio. Em alojamento partilhado isso não é hipótese
     * remota — é o normal.
     *
     * Perguntado ao MySQL e não lido do config: o pacote não expõe o nome da base
     * de dados, e acrescentar-lhe um método por causa disto era mexer no CMS que
     * corre cinco sites para resolver uma coisa deste.
     */
    private static function nomeDoTranco(): string
    {
        static $nome = null;

        $nome ??= 'casadoze.agenda.' . (string)App::instance()->db()->fetchColumn('SELECT DATABASE()');

        return $nome;
    }

    /**
     * Correr algo com a agenda trancada, e destrancá-la sempre.
     *
     * O `finally` não é cerimónia: um tranco com nome é da ligação e não da
     * transacção, e uma excepção a sair daqui sem o largar deixava-o preso até a
     * ligação morrer — com as marcações seguintes todas à espera.
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    private static function comAAgendaTrancada(callable $fn): mixed
    {
        $db   = App::instance()->db();
        $nome = self::nomeDoTranco();

        // Cinco segundos. Com uma pessoa a atender, a fila nunca passa de uma —
        // e se passar de cinco segundos o problema não é a fila.
        $preso = $db->fetchColumn('SELECT GET_LOCK(:nome, 5)', ['nome' => $nome]);

        if ((int)$preso !== 1) {
            throw new \RuntimeException('A agenda está ocupada; não foi possível marcar agora.');
        }

        try {
            return $fn();
        } finally {
            $db->query('SELECT RELEASE_LOCK(:nome)', ['nome' => $nome]);
        }
    }

    public static function find(int $id): ?array
    {
        return App::instance()->db()->fetch(self::SELECT . ' WHERE b.id = :id', ['id' => $id]);
    }

    public static function findByToken(string $token): ?array
    {
        /* 64 bytes em hexadecimal e mais nada: sem isto, um token vazio ou
           esquisito ia à base de dados a cada tentativa. */
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }
        return App::instance()->db()->fetch(self::SELECT . ' WHERE b.token = :t', ['t' => $token]);
    }

    /**
     * Cancelar.
     *
     * O `WHERE` com o estado é o que faz um duplo clique não devolver os lugares
     * duas vezes: a segunda passagem actualiza zero linhas e não chega a mexer na
     * vaga.
     */
    public static function cancel(int $id): bool
    {
        return (bool)App::instance()->db()->transaction(static function () use ($id): bool {
            $b = self::find($id);
            if ($b === null || $b['status'] !== 'confirmada') {
                return false;
            }

            $n = App::instance()->db()->query(
                "UPDATE bookings
                    SET status = 'cancelada', cancelled_at = NOW()
                  WHERE id = :id AND status = 'confirmada'",
                ['id' => $id]
            )->rowCount();

            if ($n !== 1) {
                return false;
            }

            ConsultationSlot::release((int)$b['slot_id'], (int)$b['people']);
            return true;
        });
    }

    public static function markMailed(int $id): void
    {
        App::instance()->db()->update('bookings', ['mailed_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    }

    /**
     * Quem vem, num intervalo. É a lista que a Casa olha de manhã.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function between(\DateTimeImmutable $from, \DateTimeImmutable $to, bool $onlyLive = true): array
    {
        $where  = 's.starts_at >= :de AND s.starts_at < :ate';
        $params = ['de' => $from->format('Y-m-d H:i:s'), 'ate' => $to->format('Y-m-d H:i:s')];

        if ($onlyLive) {
            $where .= " AND b.status = 'confirmada'";
        }

        return App::instance()->db()->fetchAll(
            self::SELECT . " WHERE {$where} ORDER BY s.starts_at, b.created_at",
            $params
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function forSlot(int $slotId): array
    {
        return App::instance()->db()->fetchAll(
            self::SELECT . ' WHERE b.slot_id = :id ORDER BY b.created_at',
            ['id' => $slotId]
        );
    }

    /** Quantas marcações vivas há daqui para a frente. Para o painel. */
    public static function upcomingCount(): int
    {
        return (int)App::instance()->db()->fetchColumn(
            "SELECT COUNT(*) FROM bookings b
               JOIN consultation_slots s ON s.id = b.slot_id
              WHERE b.status = 'confirmada' AND s.starts_at >= NOW()"
        );
    }

    /** Quantas do mesmo e-mail nas últimas horas. Ver BookingController. */
    public static function recentFrom(string $email, int $hours = 24): int
    {
        return (int)App::instance()->db()->fetchColumn(
            'SELECT COUNT(*) FROM bookings
              WHERE email = :e AND created_at >= DATE_SUB(NOW(), INTERVAL :h HOUR)',
            ['e' => mb_strtolower(trim($email)), 'h' => max(1, min(720, $hours))]
        );
    }

    /**
     * Um código que se lê em voz alta.
     *
     * Sem I, O, 0 nem 1: ao telefone ninguém distingue um do outro, e a marcação
     * fica por encontrar. Oito caracteres com um hífen ao meio, para se ditarem em
     * dois bocados.
     */
    private static function freeCode(): string
    {
        $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $db  = App::instance()->db();

        for ($tentativa = 0; $tentativa < 12; $tentativa++) {
            $c = '';
            for ($i = 0; $i < 8; $i++) {
                if ($i === 4) { $c .= '-'; }
                $c .= $abc[random_int(0, strlen($abc) - 1)];
            }
            if ($db->fetch('SELECT id FROM bookings WHERE code = :c', ['c' => $c]) === null) {
                return $c;
            }
        }

        /* Doze colisões seguidas num alfabeto de 32^8 não acontece por acaso —
           acontece com a tabela cheia ou com o gerador partido. Deixar rebentar é
           melhor do que devolver um código repetido. */
        throw new \RuntimeException('Não foi possível gerar um código de marcação.');
    }

    private const SELECT = 'SELECT b.*, s.starts_at, s.consultation_id, s.capacity, s.seats_taken,
                                   c.name AS consultation_name, c.slug AS consultation_slug,
                                   c.duration_min, c.price_cents, c.mode AS consultation_mode
                              FROM bookings b
                              JOIN consultation_slots s ON s.id = b.slot_id
                              JOIN consultations c      ON c.id = s.consultation_id';
}
