<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;
use Admedia\Cms\Video as Endereço;

/**
 * Uma sessão do Grimório.
 *
 * As sessões do canal do Zé, com a peça da loja que foi usada em cada uma — é
 * essa ligação que faz o Grimório vender: quem vê um ritual com sálvia encontra
 * o molho de sálvia a um clique.
 *
 * **Não vem do pacote.** Um CMS partilhado tem um bloco de vídeo; o que não tem
 * é um arquivo de sessões com temas, capítulos e peças associadas.
 *
 * **Sem contagem de visualizações.** Isso vem da API do YouTube, que não está
 * ligada, e um número escrito à mão hoje é um número errado amanhã.
 */
final class Video
{
    /** @return array<int,array<string,mixed>> */
    public static function published(?string $locale = null, int $limit = 0): array
    {
        /* As mais recentes primeiro. `published_at` por escrever vai para o
           fim e não para o princípio — uma sessão sem data é uma que ainda
           não saiu, e não a mais nova de todas. */
        $sql = 'SELECT * FROM videos
                 WHERE locale = :loc AND is_published = 1
                 ORDER BY published_at IS NULL, published_at DESC, sort_order, id';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        return App::instance()->db()->fetchAll($sql, ['loc' => $locale ?? current_locale()]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(?string $locale = null): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT * FROM videos WHERE locale = :loc ORDER BY sort_order, id',
            ['loc' => $locale ?? current_locale()]
        );
    }

    public static function find(int $id): ?array
    {
        return App::instance()->db()->fetch('SELECT * FROM videos WHERE id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug, ?string $locale = null): ?array
    {
        return App::instance()->db()->fetch(
            'SELECT * FROM videos WHERE slug = :s AND locale = :loc LIMIT 1',
            ['s' => $slug, 'loc' => $locale ?? current_locale()]
        );
    }

    /**
     * Os temas que existem, pela ordem em que aparecem.
     *
     * Lidos do que está escrito e não de uma lista: um tema novo aparece no
     * filtro no dia em que alguém o escrever numa sessão.
     *
     * @return string[]
     */
    public static function temas(?string $locale = null): array
    {
        $linhas = App::instance()->db()->fetchAll(
            "SELECT DISTINCT theme FROM videos
              WHERE locale = :loc AND is_published = 1 AND theme <> ''
              ORDER BY theme",
            ['loc' => $locale ?? current_locale()]
        );

        return array_map(static fn(array $l): string => (string)$l['theme'], $linhas);
    }

    /**
     * Os capítulos de uma sessão, de volta a lista.
     *
     * Uma linha por capítulo, com o tempo à frente: «02:15 Preparar o altar».
     * O tempo é separado do título para a página o poder alinhar numa coluna —
     * e para, no dia em que houver leitor próprio, poder saltar para lá.
     *
     * @return array<int,array{tempo:string,título:string}>
     */
    public static function capítulos(array $video): array
    {
        $cru = trim((string)($video['chapters'] ?? ''));
        if ($cru === '') {
            return [];
        }

        $out = [];
        foreach (preg_split('/\R/', $cru) ?: [] as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            if (preg_match('/^((?:\d{1,2}:)?\d{1,2}:\d{2})\s+(.*)$/', $linha, $m) === 1) {
                $out[] = ['tempo' => $m[1], 'título' => trim($m[2])];
            } else {
                /* Sem tempo à frente continua a ser um capítulo: é melhor
                   mostrá-lo sem o tempo do que deitá-lo fora por estar mal
                   escrito. */
                $out[] = ['tempo' => '', 'título' => $linha];
            }
        }

        return $out;
    }

    /** As peças usadas numa sessão, publicadas. @return array<int,array<string,mixed>> */
    public static function produtos(int $videoId): array
    {
        return App::instance()->db()->fetchAll(
            'SELECT p.* FROM products p
               JOIN video_products vp ON vp.product_id = p.id
              WHERE vp.video_id = :id AND p.is_published = 1
              ORDER BY vp.sort_order, p.sort_order, p.id',
            ['id' => $videoId]
        );
    }

    /** O vídeo no YouTube, ou null se o endereço estiver por escrever. */
    public static function noYouTube(array $video): ?array
    {
        $url = trim((string)($video['url'] ?? ''));

        return $url === '' ? null : Endereço::youtube($url);
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d): int
    {
        return App::instance()->db()->insert('videos', self::colunas($d) + [
            'locale' => $d['locale'] ?? current_locale(),
        ]);
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, array $d): void
    {
        App::instance()->db()->update('videos', self::colunas($d), 'id = :id', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        App::instance()->db()->delete('videos', 'id = :id', ['id' => $id]);
    }

    public static function togglePublished(int $id): void
    {
        App::instance()->db()->query(
            'UPDATE videos SET is_published = 1 - is_published WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Que peças é que esta sessão usa. Reescreve a lista toda. */
    public static function setProdutos(int $id, array $ids): void
    {
        $db = App::instance()->db();
        $db->delete('video_products', 'video_id = :id', ['id' => $id]);

        $ordem = 0;
        foreach (array_unique(array_map('intval', $ids)) as $productId) {
            if ($productId <= 0) {
                continue;
            }
            $db->insert('video_products', [
                'video_id'   => $id,
                'product_id' => $productId,
                'sort_order' => $ordem++,
            ]);
        }
    }

    /** @param array<string,mixed> $d @return array<string,mixed> */
    private static function colunas(array $d): array
    {
        $quando = trim((string)($d['published_at'] ?? ''));

        return [
            'slug'         => slugify((string)($d['slug'] ?: $d['title'] ?? '')),
            'title'        => mb_substr(trim((string)($d['title'] ?? '')), 0, 190),
            'theme'        => mb_substr(trim((string)($d['theme'] ?? '')), 0, 120),
            'url'          => mb_substr(trim((string)($d['url'] ?? '')), 0, 500),
            'duration'     => mb_substr(trim((string)($d['duration'] ?? '')), 0, 12),
            'summary'      => mb_substr(trim((string)($d['summary'] ?? '')), 0, 500),
            'body'         => (string)($d['body'] ?? ''),
            'chapters'     => trim((string)($d['chapters'] ?? '')),
            'drawing'      => mb_substr(trim((string)($d['drawing'] ?? '')), 0, 60),
            'published_at' => $quando === '' ? null : $quando,
            'is_published' => !empty($d['is_published']) ? 1 : 0,
            'sort_order'   => (int)($d['sort_order'] ?? 0),
        ];
    }
}
