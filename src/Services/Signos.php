<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Os doze signos: a roda, a constelação e a semana.
 *
 * **A previsão não é adivinhada aqui.** O texto de cada signo está escrito no
 * catálogo de frases, um por signo, e é o mesmo todas as semanas. O que muda com
 * a semana são os três medidores e os três números da sorte, e esses saem de um
 * acaso **com semente**: a semente é o signo mais a segunda-feira dessa semana,
 * e por isso dois visitantes no mesmo dia vêem o mesmo, e na segunda-feira
 * seguinte vêem outra coisa.
 *
 * Isso é de propósito e vale a pena dizê-lo: um horóscopo que mudasse a cada
 * recarga da página era um horóscopo que se apanhava a mentir em dez segundos.
 *
 * As constelações são as da maquete — estrelas numa grelha de 100 por 100 e os
 * traços entre elas.
 */
final class Signos
{
    /** Os símbolos. O `\u{FE0E}` pede o desenho de texto e não o emoji a cores. */
    private const SÍMBOLOS = [
        "\u{2648}\u{FE0E}", "\u{2649}\u{FE0E}", "\u{264A}\u{FE0E}", "\u{264B}\u{FE0E}",
        "\u{264C}\u{FE0E}", "\u{264D}\u{FE0E}", "\u{264E}\u{FE0E}", "\u{264F}\u{FE0E}",
        "\u{2650}\u{FE0E}", "\u{2651}\u{FE0E}", "\u{2652}\u{FE0E}", "\u{2653}\u{FE0E}",
    ];

    /** Em que mês e dia começa cada signo. Mês de 1 a 12. */
    private const INÍCIOS = [
        [3, 21], [4, 20], [5, 21], [6, 21], [7, 23], [8, 23],
        [9, 23], [10, 23], [11, 22], [12, 22], [1, 20], [2, 19],
    ];

    /** O elemento de cada signo: 0 fogo, 1 terra, 2 ar, 3 água. */
    private const ELEMENTOS = [0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3];

    /** A cor de cada elemento: a viva, e a mesma esbatida para o fundo. */
    private const CORES = [
        ['#e0863c', 'rgba(224, 134, 60, .22)'],
        ['#8fae74', 'rgba(143, 174, 116, .2)'],
        ['#b48ff0', 'rgba(180, 143, 240, .22)'],
        ['#5fb0c9', 'rgba(95, 176, 201, .22)'],
    ];

    /**
     * As constelações, por signo. `estrelas` são pontos [x, y, raio] numa grelha
     * de 100 por 100; `linhas` são pares de índices dessas estrelas.
     *
     * @var array<int,array{estrelas:array<int,array<int,float>>,linhas:array<int,array<int,int>>}>
     */
    private const CONSTELAÇÕES = [
        [
            'estrelas' => [[20, 40, 1.7], [18, 25, 1.05], [28, 15, 1.05], [40, 20, 1.05], [50, 40, 1.5], [50, 86, 1.7], [60, 20, 1.05], [72, 15, 1.05], [82, 25, 1.05], [80, 40, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 4], [4, 5], [4, 6], [6, 7], [7, 8], [8, 9]],
        ],
        [
            'estrelas' => [[50, 42, 1.05], [64, 48, 1.5], [70, 62, 1.05], [64, 76, 1.05], [50, 82, 1.05], [36, 76, 1.05], [30, 62, 1.05], [36, 48, 1.5], [16, 16, 1.7], [28, 36, 1.05], [84, 16, 1.7], [72, 36, 1.05]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 4], [4, 5], [5, 6], [6, 7], [7, 0], [8, 9], [9, 7], [10, 11], [11, 1]],
        ],
        [
            'estrelas' => [[20, 18, 1.7], [38, 18, 1.5], [62, 18, 1.5], [80, 18, 1.7], [20, 82, 1.7], [38, 82, 1.5], [62, 82, 1.5], [80, 82, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [4, 5], [5, 6], [6, 7], [1, 5], [2, 6]],
        ],
        [
            'estrelas' => [[22, 38, 1.05], [30, 30, 1.5], [38, 38, 1.05], [30, 46, 1.05], [46, 22, 1.05], [64, 22, 1.05], [82, 34, 1.7], [62, 62, 1.05], [70, 54, 1.05], [78, 62, 1.05], [70, 70, 1.5], [54, 78, 1.05], [36, 78, 1.05], [18, 66, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 0], [1, 4], [4, 5], [5, 6], [7, 8], [8, 9], [9, 10], [10, 7], [10, 11], [11, 12], [12, 13]],
        ],
        [
            'estrelas' => [[28, 50, 1.5], [40, 62, 1.05], [28, 74, 1.05], [16, 62, 1.05], [36, 32, 1.05], [50, 16, 1.05], [68, 20, 1.05], [72, 38, 1.05], [62, 60, 1.05], [68, 80, 1.05], [84, 82, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 0], [0, 4], [4, 5], [5, 6], [6, 7], [7, 8], [8, 9], [9, 10]],
        ],
        [
            'estrelas' => [[14, 24, 1.7], [24, 32, 1.5], [24, 80, 1.7], [34, 22, 1.05], [42, 32, 1.5], [42, 80, 1.7], [52, 22, 1.05], [60, 32, 1.05], [60, 66, 1.05], [76, 50, 1.05], [84, 62, 1.05], [74, 82, 1.05], [58, 86, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [1, 3], [3, 4], [4, 5], [4, 6], [6, 7], [7, 8], [8, 9], [9, 10], [10, 11], [11, 12]],
        ],
        [
            'estrelas' => [[14, 80, 1.7], [86, 80, 1.7], [14, 62, 1.7], [34, 62, 1.05], [34, 44, 1.05], [50, 32, 1.05], [66, 44, 1.05], [66, 62, 1.05], [86, 62, 1.7]],
            'linhas'   => [[0, 1], [2, 3], [3, 4], [4, 5], [5, 6], [6, 7], [7, 8]],
        ],
        [
            'estrelas' => [[14, 24, 1.7], [24, 32, 1.5], [24, 78, 1.7], [34, 22, 1.05], [42, 32, 1.5], [42, 78, 1.7], [52, 22, 1.05], [60, 32, 1.05], [60, 72, 1.05], [70, 82, 1.05], [86, 80, 1.5], [78, 72, 1.7], [78, 90, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [1, 3], [3, 4], [4, 5], [4, 6], [6, 7], [7, 8], [8, 9], [9, 10], [10, 11], [10, 12]],
        ],
        [
            'estrelas' => [[18, 82, 1.7], [82, 18, 1.5], [56, 18, 1.7], [82, 44, 1.7], [30, 46, 1.7], [54, 70, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [1, 3], [4, 5]],
        ],
        [
            'estrelas' => [[14, 24, 1.7], [30, 62, 1.05], [44, 22, 1.05], [56, 58, 1.5], [74, 52, 1.05], [80, 66, 1.05], [70, 80, 1.05], [56, 72, 1.5], [42, 88, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 4], [4, 5], [5, 6], [6, 7], [7, 3], [7, 8]],
        ],
        [
            'estrelas' => [[14, 40, 1.7], [26, 30, 1.05], [38, 40, 1.05], [50, 30, 1.05], [62, 40, 1.05], [74, 30, 1.05], [86, 40, 1.7], [14, 66, 1.7], [26, 56, 1.05], [38, 66, 1.05], [50, 56, 1.05], [62, 66, 1.05], [74, 56, 1.05], [86, 66, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 4], [4, 5], [5, 6], [7, 8], [8, 9], [9, 10], [10, 11], [11, 12], [12, 13]],
        ],
        [
            'estrelas' => [[24, 14, 1.7], [36, 32, 1.05], [38, 50, 1.5], [36, 68, 1.05], [24, 86, 1.7], [76, 14, 1.7], [64, 32, 1.05], [62, 50, 1.5], [64, 68, 1.05], [76, 86, 1.7]],
            'linhas'   => [[0, 1], [1, 2], [2, 3], [3, 4], [5, 6], [6, 7], [7, 8], [8, 9], [2, 7]],
        ],
    ];

    /** Quantos signos há. Escrito uma vez para não haver doze espalhados. */
    public const QUANTOS = 12;

    /** O símbolo de um signo. */
    public static function símbolo(int $i): string
    {
        return self::SÍMBOLOS[$i] ?? '';
    }

    /** O elemento de um signo, de 0 a 3. */
    public static function elemento(int $i): int
    {
        return self::ELEMENTOS[$i] ?? 0;
    }

    /** As duas cores do elemento de um signo: a viva e a esbatida. */
    public static function cores(int $i): array
    {
        return self::CORES[self::elemento($i)];
    }

    /** A constelação de um signo. */
    public static function constelação(int $i): array
    {
        return self::CONSTELAÇÕES[$i] ?? ['estrelas' => [], 'linhas' => []];
    }

    /** Em que dia começa e acaba um signo, como [mês, dia]. */
    public static function periodo(int $i): array
    {
        $começa = self::INÍCIOS[$i];
        $seguinte = self::INÍCIOS[($i + 1) % self::QUANTOS];

        // O dia antes do início do seguinte. Com o dia 1, recua para o mês
        // anterior — é por isso que isto passa por uma data e não por «-1».
        $fim = (new \DateTimeImmutable())
            ->setDate(2001, $seguinte[0], $seguinte[1])
            ->modify('-1 day');

        return [
            'começa' => ['mês' => $começa[0], 'dia' => $começa[1]],
            'acaba'  => ['mês' => (int)$fim->format('n'), 'dia' => (int)$fim->format('j')],
        ];
    }

    /**
     * Em que signo anda o sol hoje.
     *
     * Percorre os doze e devolve aquele cujo intervalo contém a data. O
     * Capricórnio atravessa a passagem do ano, e é por isso que a conta olha
     * para o início deste e para o início do seguinte em vez de comparar
     * intervalos fechados.
     */
    public static function doDia(\DateTimeImmutable $quando): int
    {
        $mês = (int)$quando->format('n');
        $dia = (int)$quando->format('j');

        for ($i = 0; $i < self::QUANTOS; $i++) {
            [$mi, $di] = self::INÍCIOS[$i];
            [$ms, $ds] = self::INÍCIOS[($i + 1) % self::QUANTOS];

            if (($mês === $mi && $dia >= $di) || ($mês === $ms && $dia < $ds)) {
                return $i;
            }
        }

        return 0;
    }

    /**
     * A segunda-feira da semana de uma data. É ela que semeia o acaso: a
     * previsão é a mesma de segunda a domingo e muda na segunda seguinte.
     */
    public static function segundaFeira(\DateTimeImmutable $quando): \DateTimeImmutable
    {
        $diaDaSemana = (int)$quando->format('N'); // 1 segunda … 7 domingo

        return $quando->setTime(0, 0)->modify('-' . ($diaDaSemana - 1) . ' days');
    }

    /**
     * O que muda com a semana: os três medidores e os três números da sorte.
     *
     * O acaso é o gerador congruente de Lehmer — o mesmo da maquete, e o mesmo
     * que está nos livros como «minimal standard». Semeado com o signo e com a
     * segunda-feira, dá sempre a mesma semana a toda a gente.
     *
     * @return array{medidores:array<int,int>,numero:int,diaDaSorte:int}
     */
    public static function semana(int $signo, \DateTimeImmutable $segunda): array
    {
        $semente = ($signo + 1) * 97
                 + (int)$segunda->format('j') * 13
                 + ((int)$segunda->format('n') - 1) * 31;

        $acaso = static function () use (&$semente): float {
            $semente = ($semente * 16807) % 2147483647;
            return $semente / 2147483647;
        };

        $medidores = [];
        for ($i = 0; $i < 3; $i++) {
            $medidores[] = 2 + (int)floor($acaso() * 4);   // de 2 a 5
        }

        /* A ordem destes dois conta, e é a da maquete: primeiro o dia, depois o
           número. Trocá-los dá outros valores — é o mesmo acaso, mas cada
           chamada avança a semente, e quem pede primeiro leva o primeiro. */
        $diaDaSorte = (int)floor($acaso() * 7);   // dias a somar à segunda
        $numero     = 1 + (int)floor($acaso() * 33);

        return [
            'medidores'  => $medidores,
            'numero'     => $numero,
            'diaDaSorte' => $diaDaSorte,
        ];
    }
}
