<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Datas por extenso, na língua de quem lê.
 *
 * **O `format_date()` do pacote não chega a este site.** Usa o `date()` do PHP
 * e depois troca os nomes ingleses pelos portugueses, com uma tabela que só tem
 * português: em francês, alemão e espanhol devolve «October» e «Monday». Para
 * um site numa língua está certo; este serve cinco.
 *
 * Quem sabe dizer «octobre» e «Oktober» é o IntlDateFormatter, e é ele que aqui
 * se usa. Sem a extensão `intl` instalada cai para o formato do PHP, que diz o
 * mês em inglês — feio, e melhor do que uma página em branco.
 *
 * Estava escrito três vezes, em três vistas — o mês do calendário lunar, as
 * datas dos signos e o calendário das consultas —, e três cópias da mesma
 * função são duas que ficam para trás.
 */
final class Datas
{
    /**
     * Uma data por um padrão do ICU, com um de reserva para o PHP.
     *
     * Os dois padrões são diferentes de propósito: `LLLL 'de' yyyy` é ICU e
     * `F Y` é PHP, e não há tradução automática entre eles que valha a pena
     * escrever para cinco usos.
     */
    public static function porExtenso(\DateTimeInterface $quando, string $padrão, string $reserva): string
    {
        if (!class_exists(\IntlDateFormatter::class)) {
            return $quando->format($reserva);
        }

        $f = new \IntlDateFormatter(
            str_replace('-', '_', current_locale()),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            $padrão
        );

        return $f->format($quando) ?: $quando->format($reserva);
    }

    /**
     * «Outubro de 2026», e não «outubro 2026».
     *
     * Em português o mês vem ligado ao ano por «de», e a primeira letra sobe
     * porque num cabeçalho o mês abre uma linha — o IntlDateFormatter devolve-o
     * em minúscula, que é o correcto no meio de uma frase e não no princípio
     * dela. Outras línguas não levam a preposição, e o padrão de cada uma é o
     * que o ICU já sabe: `LLLL y`.
     */
    public static function mêsEAno(\DateTimeInterface $quando): string
    {
        $padrão = current_locale() === 'pt' ? "LLLL 'de' yyyy" : 'LLLL y';
        $texto  = self::porExtenso($quando, $padrão, 'F Y');

        return self::comMaiúscula($texto);
    }

    /** «Quinta-feira, 22 de Outubro». */
    public static function diaPorExtenso(\DateTimeInterface $quando): string
    {
        $padrão = current_locale() === 'pt' ? "EEEE, d 'de' LLLL" : 'EEEE, d MMMM';

        return self::comMaiúscula(self::porExtenso($quando, $padrão, 'l, j F'));
    }

    /** «22 de Outubro». */
    public static function diaCurto(\DateTimeInterface $quando): string
    {
        $padrão = current_locale() === 'pt' ? "d 'de' LLLL" : 'd MMMM';

        return self::porExtenso($quando, $padrão, 'j F');
    }

    /** «22 Out» — curto, sem ano. Para as filas estreitas, como a dos signos. */
    public static function diaCurtoSemAno(\DateTimeInterface $quando): string
    {
        return self::porExtenso($quando, 'd MMM', 'j M');
    }

    /** «Quinta-feira», com a inicial em maiúscula. */
    public static function diaDaSemana(\DateTimeInterface $quando): string
    {
        return self::comMaiúscula(self::porExtenso($quando, 'EEEE', 'l'));
    }

    /** As sete iniciais dos dias da semana, de segunda a domingo. */
    public static function iniciais(): array
    {
        /* 2026-10-19 é uma segunda-feira. A semana começa à segunda, como em
           Portugal e como na maquete, e não ao domingo. */
        $out = [];
        for ($d = 0; $d < 7; $d++) {
            $dia = new \DateTimeImmutable('2026-10-' . (19 + $d));
            $out[] = mb_strtoupper(mb_substr(self::porExtenso($dia, 'EEEEE', 'D'), 0, 1));
        }

        return $out;
    }

    private static function comMaiúscula(string $texto): string
    {
        return $texto === ''
            ? ''
            : mb_convert_case(mb_substr($texto, 0, 1), MB_CASE_UPPER) . mb_substr($texto, 1);
    }
}
