<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;

/**
 * Quem lê as cartas na Casa.
 *
 * **Isto não vem do pacote**, pela mesma razão que as consultas não vêm: um CMS
 * partilhado por oito sites não tem tarólogas.
 *
 * As especialidades, os formatos e as línguas são listas curtas guardadas uma
 * por linha, como os assuntos de um pedido em `enquiries`. Três tabelas de
 * junção para guardar «Tarot» e «Português» era um esquema a explicar-se a si
 * próprio em vez de ao problema. A tabela de junção que existe é a das
 * consultas, e existe porque aponta para linhas que existem noutro sítio.
 */
final class Practitioner
{
    /** @return array<int,array<string,mixed>> */
    public static function published(?string $locale = null): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM practitioners
              WHERE locale = :loc AND is_published = 1
              ORDER BY sort_order, id',
            ['loc' => $locale ?? current_locale()]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(?string $locale = null): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM practitioners WHERE locale = :loc ORDER BY sort_order, id',
            ['loc' => $locale ?? current_locale()]
        );
    }

    public static function find(int $id): ?array
    {
        return App::instance()->db()->fetch('SELECT * FROM practitioners WHERE id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug, ?string $locale = null): ?array
    {
        return App::instance()->db()->fetch(
            'SELECT * FROM practitioners WHERE slug = :s AND locale = :loc LIMIT 1',
            ['s' => $slug, 'loc' => $locale ?? current_locale()]
        );
    }

    /**
     * Uma das listas de uma pessoa, de volta a lista.
     *
     * Vazio devolve array vazio e não `['']`: um `explode` sobre uma string
     * vazia dá um elemento vazio, e esse elemento vazio desenhava um chip sem
     * nome na página.
     *
     * @return string[]
     */
    public static function lista(array $pessoa, string $coluna): array
    {
        $cru = trim((string)($pessoa[$coluna] ?? ''));
        if ($cru === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', $cru) ?: []),
            static fn(string $x): bool => $x !== ''
        ));
    }

    /**
     * As consultas que uma pessoa dá, publicadas, pela ordem do catálogo.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function consultas(int $id): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT c.* FROM consultations c
               JOIN practitioner_consultations pc ON pc.consultation_id = c.id
              WHERE pc.practitioner_id = :id AND c.is_published = 1
              ORDER BY pc.sort_order, c.sort_order, c.id',
            ['id' => $id]
        );
    }

    /**
     * Quem dá uma consulta. Para a ficha da consulta poder dizer com quem é.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function daConsulta(int $consultationId): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT p.* FROM practitioners p
               JOIN practitioner_consultations pc ON pc.practitioner_id = p.id
              WHERE pc.consultation_id = :id AND p.is_published = 1
              ORDER BY p.sort_order, p.id',
            ['id' => $consultationId]
        );
    }

    /** Particles that carry no initial: "Rogério de Exu" is RE, not RD. */
    private const PARTICULAS = ['de', 'da', 'do', 'das', 'dos', 'e', 'di', 'du', 'del', 'van', 'von'];

    /**
     * The initials, for where there is no portrait.
     *
     * Square brackets around a name nobody has written yet — `[Helena]` — are
     * stripped first, so the result is an H and not a `[`.
     */
    public static function iniciais(array $pessoa): string
    {
        $nome = trim(str_replace(['[', ']'], '', (string)($pessoa['name'] ?? '')));
        $out  = '';

        foreach (preg_split('/\s+/u', $nome, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $palavra) {
            if (in_array(mb_strtolower($palavra), self::PARTICULAS, true)) {
                continue;
            }

            /* An apostrophe carries a name behind it — D'Exu — and the letter
               that matters is the one in front of it, which is already first. */
            $out .= mb_substr($palavra, 0, 1);
            if (mb_strlen($out) >= 2) {
                break;
            }
        }

        return mb_strtoupper($out);
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d): int
    {
        return App::instance()->db()->insert('practitioners', self::colunas($d) + [
            'locale' => $d['locale'] ?? current_locale(),
        ]);
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, array $d): void
    {
        App::instance()->db()->update('practitioners', self::colunas($d), 'id = :id', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        App::instance()->db()->delete('practitioners', 'id = :id', ['id' => $id]);
    }

    public static function togglePublished(int $id): void
    {
        App::instance()->db()->query(
            'UPDATE practitioners SET is_published = 1 - is_published WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Que consultas é que esta pessoa dá. Reescreve a lista toda. */
    public static function setConsultas(int $id, array $ids): void
    {
        $db = App::instance()->db();
        $db->delete('practitioner_consultations', 'practitioner_id = :id', ['id' => $id]);

        $ordem = 0;
        foreach (array_unique(array_map('intval', $ids)) as $consultationId) {
            if ($consultationId <= 0) {
                continue;
            }
            $db->insert('practitioner_consultations', [
                'practitioner_id' => $id,
                'consultation_id' => $consultationId,
                'sort_order'      => $ordem++,
            ]);
        }
    }

    /** @param array<string,mixed> $d @return array<string,mixed> */
    private static function colunas(array $d): array
    {
        return [
            'slug'         => slugify((string)($d['slug'] ?: $d['name'] ?? '')),
            'name'         => mb_substr(trim((string)($d['name'] ?? '')), 0, 190),
            'role'         => mb_substr(trim((string)($d['role'] ?? '')), 0, 190),
            'quote'        => mb_substr(trim((string)($d['quote'] ?? '')), 0, 500),
            'bio'          => (string)($d['bio'] ?? ''),
            'deck'         => mb_substr(trim((string)($d['deck'] ?? '')), 0, 190),
            'sign'         => mb_substr(trim((string)($d['sign'] ?? '')), 0, 60),
            'years'        => max(0, (int)($d['years'] ?? 0)),
            'image'        => mb_substr(trim((string)($d['image'] ?? '')), 0, 255),
            'image_alt'    => mb_substr(trim((string)($d['image_alt'] ?? '')), 0, 255),
            'specialties'  => trim((string)($d['specialties'] ?? '')),
            'formats'      => trim((string)($d['formats'] ?? '')),
            'languages'    => trim((string)($d['languages'] ?? '')),
            'is_published' => !empty($d['is_published']) ? 1 : 0,
            'sort_order'   => (int)($d['sort_order'] ?? 0),
        ];
    }
}
