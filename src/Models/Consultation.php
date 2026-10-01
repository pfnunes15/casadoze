<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;

/**
 * Uma consulta da Casa.
 *
 * Na maquete eram quatro cartões escritos à mão dentro de um bloco de conteúdo.
 * Um item de bloco não tem por onde uma vaga lhe pegar — não tem identidade, e
 * duas páginas com a mesma consulta escrita eram duas consultas diferentes para
 * quem contasse horas. Passaram a registos, e o bloco das consultas continua a
 * ser o editor a decidir onde a lista aparece: o que mudou é que a lista vem
 * daqui.
 *
 * **`is_bookable` é o que distingue uma leitura de um trabalho.** Uma leitura
 * tem horas; um trabalho espiritual começa com uma conversa e prepara-se depois.
 * Os dois são consultas — têm nome, texto, preço e lugar na lista — e o que
 * muda é o botão: «Marcar» leva à agenda, e sem agenda leva à conversa.
 */
final class Consultation
{
    /** @return array<int,array<string,mixed>> As publicadas, pela ordem do editor. */
    public static function published(?string $locale = null): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM consultations
              WHERE locale = :locale AND is_published = 1
              ORDER BY sort_order, name',
            ['locale' => $locale ?? current_locale()]
        );
    }

    /** @return array<int,array<string,mixed>> Todas, para o backoffice. */
    public static function all(?string $locale = null): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM consultations WHERE locale = :locale ORDER BY sort_order, name',
            ['locale' => $locale ?? current_locale()]
        );
    }

    /**
     * As que se marcam escolhendo uma hora.
     *
     * É a lista que o selector de agenda mostra. Uma consulta sem agenda no
     * selector era uma escolha que não levava a horas nenhumas, e quem a fizesse
     * ficava à espera de uma lista que nunca aparecia.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function bookable(?string $locale = null): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM consultations
              WHERE locale = :locale AND is_published = 1 AND is_bookable = 1
              ORDER BY sort_order, name',
            ['locale' => $locale ?? current_locale()]
        );
    }

    public static function find(int $id): ?array
    {
        return App::instance()->db()->fetch('SELECT * FROM consultations WHERE id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug, ?string $locale = null): ?array
    {
        return App::instance()->db()->fetch(
            'SELECT * FROM consultations WHERE slug = :slug AND locale = :locale',
            ['slug' => $slug, 'locale' => $locale ?? current_locale()]
        );
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d): int
    {
        return App::instance()->db()->insert('consultations', self::columns($d) + [
            'locale' => (string)($d['locale'] ?? current_locale()),
            'slug'   => self::freeSlug((string)($d['slug'] ?? ''), (string)$d['name']),
        ]);
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, array $d): void
    {
        $data = self::columns($d);
        if (isset($d['slug'])) {
            $data['slug'] = self::freeSlug((string)$d['slug'], (string)($d['name'] ?? ''), $id);
        }
        App::instance()->db()->update('consultations', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Apagar uma consulta apaga com ela o horário, os fechos e as vagas — é o
     * que as chaves estrangeiras dizem. E apaga as marcações que estavam nessas
     * vagas, que é uma coisa que não se faz sem saber: o backoffice conta-as
     * primeiro e recusa se houver alguma por acontecer.
     */
    public static function delete(int $id): void
    {
        App::instance()->db()->delete('consultations', 'id = :id', ['id' => $id]);
    }

    /** Quantas marcações vivas ficariam sem pé se esta consulta fosse apagada. */
    public static function liveBookings(int $id): int
    {
        return (int)App::instance()->db()->fetchColumn(
            "SELECT COUNT(*) FROM bookings b
               JOIN consultation_slots s ON s.id = b.slot_id
              WHERE s.consultation_id = :id AND b.status = 'confirmada' AND s.starts_at >= NOW()",
            ['id' => $id]
        );
    }

    /**
     * Só o que é coluna, e cada uma dentro dos seus limites.
     *
     * Os mínimos não são decoração: uma consulta com lotação zero desaparecia do
     * site sem dizer porquê, e uma com `max_party` maior do que a lotação deixava
     * pedir lugares que a vaga nunca teria.
     *
     * @param array<string,mixed> $d
     * @return array<string,mixed>
     */
    private static function columns(array $d): array
    {
        $capacity   = max(1, (int)($d['capacity'] ?? 1));
        $isBookable = !empty($d['is_bookable']);

        return [
            'name'      => mb_substr(trim((string)($d['name'] ?? '')), 0, 190),
            'summary'   => mb_substr(trim((string)($d['summary'] ?? '')), 0, 255),
            'body'      => (string)($d['body'] ?? ''),
            'image'     => mb_substr(trim((string)($d['image'] ?? '')), 0, 255),
            'image_alt' => mb_substr(trim((string)($d['image_alt'] ?? '')), 0, 255),

            /* A duração pode ser zero, e só numa consulta sem agenda: um trabalho
               espiritual não dura meia hora nem duas, dura o que tiver de durar.
               Numa que se marca, zero punha duas marcações à mesma hora a dizer
               que não se sobrepõem — daí o mínimo de quinze minutos. */
            'duration_min' => $isBookable
                ? max(15, min(600, (int)($d['duration_min'] ?? 30)))
                : max(0, min(600, (int)($d['duration_min'] ?? 0))),

            // Vazio fica NULL e não zero: um preço por decidir não é uma consulta
            // gratuita, e quem lê o cartão tem de poder distinguir os dois.
            'price_cents'  => self::precoOuNada($d['price_cents'] ?? null),
            'mode'         => self::modo((string)($d['mode'] ?? 'ambos')),
            'is_bookable'  => $isBookable ? 1 : 0,
            'capacity'     => $capacity,
            'max_party'    => max(1, min($capacity, (int)($d['max_party'] ?? 1))),
            'notice_hours' => max(0, min(720, (int)($d['notice_hours'] ?? 24))),
            'is_published' => !empty($d['is_published']) ? 1 : 0,
            'sort_order'   => (int)($d['sort_order'] ?? 0),
        ];
    }

    /** Os três valores do ENUM, e «ambos» para o que não for nenhum deles. */
    private static function modo(string $raw): string
    {
        return in_array($raw, ['presencial', 'online', 'ambos'], true) ? $raw : 'ambos';
    }

    /**
     * Onde se pode escolher fazer esta consulta.
     *
     * Uma consulta «ambos» pergunta; as outras não têm nada a perguntar, e
     * mostrar um selector de uma opção só é pedir uma decisão que já está
     * tomada.
     *
     * @param array<string,mixed> $consulta
     * @return array<int,string>
     */
    public static function modos(array $consulta): array
    {
        $mode = (string)($consulta['mode'] ?? 'ambos');
        return $mode === 'ambos' ? ['presencial', 'online'] : [$mode];
    }

    /** Cêntimos, ou NULL quando a caixa do preço veio vazia. */
    private static function precoOuNada(mixed $raw): ?int
    {
        $s = trim((string)($raw ?? ''));
        return $s === '' ? null : max(0, (int)$s);
    }

    /**
     * A linha que se lê sob o nome: «30 min · 35,00 €».
     *
     * Feita à saída e não guardada. A maquete tinha-a escrita à mão ao lado dos
     * números que dizem o mesmo, e duas verdades sobre a mesma coisa acabam
     * sempre a discordar.
     *
     * Sem preço, diz «Sob consulta» — que é o que a maquete já dizia nos
     * trabalhos espirituais, e é a frase certa para o que ainda não tem número.
     *
     * @param array<string,mixed> $consulta
     */
    public static function linha(array $consulta): string
    {
        $partes = [];

        if ((int)($consulta['duration_min'] ?? 0) > 0) {
            $partes[] = duration_text((int)$consulta['duration_min']);
        }

        $partes[] = ($consulta['price_cents'] ?? null) !== null
            ? money((int)$consulta['price_cents'])
            : __('consultas.on_request');

        return implode(' · ', $partes);
    }

    /**
     * Um endereço que ainda não está tomado.
     *
     * Acrescenta-se um número em vez de recusar: recusar obriga a pensar num
     * nome, e o que a pessoa quer naquele momento é guardar.
     */
    private static function freeSlug(string $wanted, string $fallback, ?int $ignore = null): string
    {
        $base = slugify($wanted !== '' ? $wanted : $fallback);
        if ($base === '') { $base = 'consulta'; }
        $base = mb_substr($base, 0, 150);

        $db   = App::instance()->db();
        $slug = $base;
        $n    = 1;

        while (true) {
            $row = $db->fetch(
                'SELECT id FROM consultations WHERE slug = :slug AND locale = :locale',
                ['slug' => $slug, 'locale' => current_locale()]
            );
            if ($row === null || (int)$row['id'] === $ignore) {
                return $slug;
            }
            $slug = $base . '-' . (++$n);
        }
    }
}
