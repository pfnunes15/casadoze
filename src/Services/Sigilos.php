<?php
declare(strict_types=1);

namespace App\Services;

use Admedia\Core\App;
use Admedia\Core\Locales;

/**
 * Os símbolos das intenções, a traço, numa grelha de 60 por 60.
 *
 * Cinco `path` e mais nada. Vivem aqui e não numa folha de estilo nem num
 * ficheiro de imagem por produto: são cinco riscos, e um SVG por símbolo eram
 * cinco pedidos ao servidor para desenhar cinco riscos.
 *
 * **Estavam escritos dentro de `views/sections/intencoes.php`**, e passaram a
 * precisar de dois sítios no dia em que a loja também quis a fila de chips do
 * «Procuro». Dois arrays iguais em duas vistas é um que fica desactualizado.
 *
 * Qual é o símbolo de cada intenção **é escolha da Casa e não desta classe**:
 * está no bloco «Comprar por intenção», num campo por item, que é onde quem
 * edita o site o vê ao lado do nome. Daí o paraEtiquetas(), que vai lá ler o
 * que a Casa escolheu em vez de o adivinhar por uma tabela escondida no
 * código. Sem o bloco — numa instalação que o tenha apagado — fica sem
 * símbolos e com os nomes, que é pior do que o desenho e melhor do que uma
 * página partida.
 */
final class Sigilos
{
    /** @var array<string,string> */
    private const TRAÇOS = [
        'olho' => 'M10 30 Q30 12 50 30 Q30 48 10 30Z M30 24 a6 6 0 1 0 0.01 0 M30 6 a24 24 0 1 0 0.01 0',
        'laco' => 'M24 18 a12 12 0 1 0 0.01 0 M36 18 a12 12 0 1 0 0.01 0',
        'sol'  => 'M30 20 a10 10 0 1 0 0.01 0 M30 6V12 M30 48V54 M6 30H12 M48 30H54 '
                . 'M13 13L17 17 M43 43L47 47 M47 13L43 17 M17 43L13 47',
        'fumo' => 'M22 54 C14 44 30 38 22 28 C16 20 26 14 22 6 M34 54 C26 44 42 38 34 28 '
                . 'C28 20 38 14 34 6 M46 50 C40 42 50 38 46 30',
        'lua'  => 'M36 10 A20 20 0 1 0 50 40 A16 16 0 1 1 36 10Z '
                . 'M46 14 L47.5 18 L51.5 19.5 L47.5 21 L46 25 L44.5 21 L40.5 19.5 L44.5 18Z '
                . 'M52 30 L52.8 32 L54.8 32.8 L52.8 33.6 L52 35.6 L51.2 33.6 L49.2 32.8 L51.2 32Z',
    ];

    /** O traço de um símbolo pelo nome, ou vazio se não houver. */
    public static function traço(string $nome): string
    {
        return self::TRAÇOS[$nome] ?? '';
    }

    /**
     * O símbolo de cada etiqueta, como a Casa o escolheu no bloco.
     *
     * Lido uma vez por pedido: a loja desenha a fila dos chips uma vez, e
     * perguntar duas vezes a mesma coisa à base de dados numa página é uma
     * viagem para nada.
     *
     * @return array<string,string> slug da etiqueta => traço do símbolo
     */
    public static function paraEtiquetas(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        /* Pela língua que se está a ler: os itens do bloco existem por idioma,
           e o símbolo de «Proteção» está na linha portuguesa. */
        $linhas = App::instance()->db()->fetchAll(
            "SELECT i.subtitle, i.caption
               FROM page_section_items i
               JOIN page_sections s ON s.id = i.section_id
              WHERE s.type = 'intencoes' AND s.locale = :loc AND i.is_published = 1
              ORDER BY i.sort_order, i.id",
            ['loc' => Locales::current()]
        );

        $out = [];
        foreach ($linhas as $item) {
            $slug  = trim((string)($item['subtitle'] ?? ''));
            $traço = self::traço(trim((string)($item['caption'] ?? '')));
            if ($slug !== '' && $traço !== '') {
                $out[$slug] = $traço;
            }
        }

        return $cache = $out;
    }
}
