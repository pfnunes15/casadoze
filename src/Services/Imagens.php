<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Serving a photograph as AVIF and WebP, never as the original.
 *
 * The hero is a CSS background and not an `<img>` — it carries a radial mask,
 * and a mask on an `<img>` leaves the alt text with nowhere to live. So the
 * usual `srcset` is out, and `image-set()` is what does the same job for a
 * background: the browser picks the format it understands and the size that
 * fits its screen density.
 *
 * Falls back to the plain path when no variants were generated, so a photograph
 * an editor uploads through the backoffice still shows. Generating variants for
 * uploads is a server-side job and is not done yet — see the TODO below.
 *
 * TODO: run uploads through bin/variantes-de-imagem.sh on upload. Not done now
 * because it needs the encoders installed on the server, and staging has
 * neither cwebp nor avifenc.
 */
final class Imagens
{
    /**
     * Every width bin/variantes-de-imagem.sh has been asked for, across the
     * site. Only the ones that exist on disk for a given photograph are used,
     * so listing a width nothing was generated at costs nothing.
     */
    private const LARGURAS = [480, 640, 720, 960, 1254];

    /**
     * A `background-image` value for a photograph, as wide as the screen needs.
     *
     * @param string $base the path without extension, e.g. '/assets/img/abertura'
     */
    public static function fundo(string $base): string
    {
        $variantes = self::variantes($base);

        if ($variantes === []) {
            return "url('" . $base . "')";
        }

        /* AVIF first: a browser takes the first type it knows, and AVIF is
           around 30% smaller than WebP at the same quality. */
        $fontes = [];
        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $tipo) {
            foreach ($variantes[$ext] ?? [] as $largura => $caminho) {
                $fontes[] = sprintf("url('%s') type('%s') %dw", $caminho, $tipo, $largura);
            }
        }

        if ($fontes === []) {
            return "url('" . $base . "')";
        }

        /* The bare url() before image-set() is what a browser that has never
           heard of image-set() uses. It has to be a format everyone reads, so
           it is the smallest WebP — supported everywhere that matters since
           2020, and still lighter than the JPEG it replaces. */
        $reserva = self::menor($variantes['webp'] ?? []);

        return "url('" . $reserva . "'); background-image: image-set(" . implode(', ', $fontes) . ")";
    }

    /**
     * An `<img>`'s sources, as `[srcset, src]`, or null when there are none.
     *
     * WebP and not AVIF: an `<img>` takes one srcset, and offering both formats
     * means a `<picture>` with two `<source>` elements. Worth it for the hero,
     * which is the one the visitor waits for; not for a portrait in a list,
     * where the markup cost outweighs the thirty per cent.
     *
     * `src` is the smallest, for anything that ignores srcset.
     *
     * @return array{0:string,1:string}|null
     */
    public static function srcset(string $caminho): ?array
    {
        $webp = self::variantes($caminho)['webp'] ?? [];

        if ($webp === []) {
            return null;
        }

        ksort($webp);
        $srcset = [];
        foreach ($webp as $largura => $url) {
            $srcset[] = $url . ' ' . $largura . 'w';
        }

        return [implode(', ', $srcset), reset($webp)];
    }

    /**
     * What to preload for the hero, as `[srcset, type]`, or null.
     *
     * The preload has to ask for exactly what the stylesheet will pick, or the
     * browser fetches the photograph twice — once for the preload it was told
     * about and once for the background it actually paints. Hence the same
     * widths and the same order, and AVIF alone: offering both types in one
     * preload makes the browser choose again, and it may not choose the same.
     *
     * @return array{0:string,1:string}|null
     */
    public static function precarga(string $base): ?array
    {
        $variantes = self::variantes($base);
        $melhor    = $variantes['avif'] ?? $variantes['webp'] ?? [];

        if ($melhor === []) {
            return null;
        }

        ksort($melhor);
        $srcset = [];
        foreach ($melhor as $largura => $caminho) {
            $srcset[] = $caminho . ' ' . $largura . 'w';
        }

        return [implode(', ', $srcset), isset($variantes['avif']) ? 'image/avif' : 'image/webp'];
    }

    /**
     * The variants that exist on disk, by extension and width.
     *
     * The path comes from the backoffice with its extension on it —
     * `/assets/img/abertura.jpg` — and the variants are named off the stem, so
     * the extension is dropped before anything is looked for.
     *
     * @return array<string,array<int,string>>
     */
    private static function variantes(string $caminhoDado): array
    {
        $raiz = dirname(__DIR__, 2) . '/public';
        $base = preg_replace('/\.(jpe?g|png|gif|webp|avif)$/i', '', $caminhoDado);
        $out  = [];

        foreach (['avif', 'webp'] as $ext) {
            foreach (self::LARGURAS as $largura) {
                $caminho = $base . '-' . $largura . '.' . $ext;
                if (is_file($raiz . $caminho)) {
                    /* asset() wants the path relative to public/assets and adds
                       the cache-busting stamp. */
                    $out[$ext][$largura] = asset(preg_replace('#^/?assets/#', '', $caminho));
                }
            }
        }

        return $out;
    }

    /** @param array<int,string> $porLargura */
    private static function menor(array $porLargura): string
    {
        if ($porLargura === []) {
            return '';
        }

        ksort($porLargura);

        return reset($porLargura);
    }
}
