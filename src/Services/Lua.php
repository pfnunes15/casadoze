<?php
declare(strict_types=1);

namespace App\Services;

/**
 * A lua e os sabbats.
 *
 * A Casa trabalha pelo calendário lunar — os produtos dizem em que lua se
 * preparam, e a porta de entrada mostra o mês inteiro em fases. Isto é a conta
 * que o diz.
 *
 * **A fase é calculada e não guardada.** Uma tabela com as luas do ano era uma
 * tabela que alguém tinha de encher todos os anos, e que ficava errada no ano em
 * que ninguém se lembrasse. A conta é a mesma desde sempre e não precisa de
 * manutenção.
 *
 * **É a lua média, não a verdadeira.** O mês sinódico tem 29,530588853 dias em
 * média, mas a órbita não é um círculo e a lua verdadeira adianta-se e atrasa-se
 * até treze horas em relação a esta conta. Para desenhar um calendário chega: um
 * dia marcado como lua cheia é lua cheia. Para marcar um ritual à hora certa não
 * chega, e quem precisar disso vai a uma efeméride.
 */
final class Lua
{
    /** O mês sinódico médio, em dias. */
    private const MÊS = 29.530588853;

    /**
     * Uma lua nova de referência: 6 de Janeiro de 2000, 18h14 UTC.
     *
     * Qualquer lua nova servia. Esta é a que está nas tabelas toda a gente usa,
     * e por isso é a que faz a conta bater certo com as outras.
     */
    private const REFERÊNCIA = 947182440; // gmmktime(18, 14, 0, 1, 6, 2000)

    /**
     * Os oito sabbats, por mês e dia. Datas fixas e não calculadas: os quatro
     * solares mudam um dia ou dois com o solstício, e marcá-los pelo dia em que
     * a Casa os celebra é mais honesto do que uma conta astronómica que ninguém
     * veio confirmar.
     *
     * @var array<int,array{0:string,1:int,2:int}>
     */
    private const SABBATS = [
        ['Imbolc',      2,  1],
        ['Ostara',      3, 20],
        ['Beltane',     5,  1],
        ['Litha',       6, 21],
        ['Lughnasadh',  8,  1],
        ['Mabon',       9, 22],
        ['Samhain',    10, 31],
        ['Yule',       12, 21],
    ];

    /**
     * Onde está a lua, de 0 a 1.
     *
     * Zero é lua nova, 0,5 é lua cheia, e volta a zero no fim do ciclo.
     */
    public static function fase(int $instante): float
    {
        $dias = ($instante - self::REFERÊNCIA) / 86400;
        $volta = fmod(fmod($dias, self::MÊS) + self::MÊS, self::MÊS);

        return $volta / self::MÊS;
    }

    /** Quanto do disco está iluminado, em percentagem inteira. */
    public static function luzPorCento(float $fase): int
    {
        return (int)round((1 - cos(2 * M_PI * $fase)) / 2 * 100);
    }

    /**
     * Qual das oito fases com nome, de 0 a 7.
     *
     * O oitavo de volta somado antes de dividir é o que faz cada nome valer para
     * o meio da sua fatia e não para o princípio dela: sem isso, o dia da lua
     * cheia era anunciado como crescente gibosa até passar do meio-dia.
     */
    public static function nomeDaFase(float $fase): int
    {
        return (int)floor(fmod($fase + 1 / 16, 1) * 8);
    }

    /**
     * O desenho da lua, para um `path` de SVG numa grelha de 40 por 40.
     *
     * Dois arcos: a borda do disco, sempre igual, e o limite da sombra, que é
     * uma elipse cuja largura é o cosseno da fase. No quarto crescente o cosseno
     * é zero e a elipse fecha-se numa linha recta, que é exactamente o que se vê
     * no céu.
     */
    public static function desenho(float $fase): string
    {
        $k = cos(2 * M_PI * $fase);
        $rx = number_format(17 * abs($k), 2, '.', '');

        return $fase < 0.5
            ? 'M20,3 A17,17 0 0 1 20,37 A' . $rx . ',17 0 0 ' . ($k > 0 ? '0' : '1') . ' 20,3Z'
            : 'M20,3 A17,17 0 0 0 20,37 A' . $rx . ',17 0 0 ' . ($k > 0 ? '1' : '0') . ' 20,3Z';
    }

    /**
     * O mês inteiro, dia a dia.
     *
     * Cada dia leva a fase ao meio-dia — e não à meia-noite, que apanharia a
     * fase do dia anterior tanto quanto a deste —, o desenho, a luz, e se é um
     * dos quatro dias marcados do ciclo.
     *
     * @return array<int,array{dia:int,fase:float,desenho:string,luz:int,marco:bool,hoje:bool}>
     */
    public static function mes(\DateTimeImmutable $quando): array
    {
        $ano = (int)$quando->format('Y');
        $mês = (int)$quando->format('n');
        $hoje = (int)$quando->format('j');
        $quantos = (int)$quando->format('t');

        /* As fases à meia-noite de cada dia, mais a do primeiro dia do mês
           seguinte: é preciso o dia a seguir ao último para saber se o ciclo
           vira dentro dele. */
        $àsZero = [];
        for ($d = 1; $d <= $quantos + 1; $d++) {
            $àsZero[$d] = self::fase(mktime(0, 0, 0, $mês, $d, $ano) ?: 0);
        }

        $dias = [];
        for ($d = 1; $d <= $quantos; $d++) {
            $fase = self::fase(mktime(12, 0, 0, $mês, $d, $ano) ?: 0);

            $dias[] = [
                'dia'     => $d,
                'fase'    => $fase,
                'desenho' => self::desenho($fase),
                'luz'     => self::luzPorCento($fase),
                'marco'   => self::viraNesteDia($àsZero[$d], $àsZero[$d + 1]),
                'hoje'    => $d === $hoje,
            ];
        }

        return $dias;
    }

    /**
     * Se um dos quatro marcos do ciclo — nova, quarto crescente, cheia, quarto
     * minguante — cai entre estas duas meias-noites.
     *
     * Comparado em distâncias a partir do início do dia, e não por «está entre
     * a e b»: o ciclo volta a zero a meio do mês, e um dia que começasse em 0,98
     * e acabasse em 0,01 fazia essa comparação dizer que não, quando é
     * exactamente o dia da lua nova.
     */
    private static function viraNesteDia(float $início, float $fim): bool
    {
        foreach ([0.0, 0.25, 0.5, 0.75] as $marco) {
            $atéAoMarco = fmod($marco - $início + 1, 1);
            $atéAoFim   = fmod($fim - $início + 1, 1);
            if ($atéAoMarco < $atéAoFim) {
                return true;
            }
        }

        return false;
    }

    /**
     * O próximo sabbat, e quantos dias faltam.
     *
     * Procura no ano a decorrer e, se já passaram todos, no seguinte — em
     * Dezembro depois do Yule, o próximo é o Imbolc de Fevereiro.
     *
     * @return array{nome:string,quando:\DateTimeImmutable,faltam:int}
     */
    public static function próximoSabbat(\DateTimeImmutable $quando): array
    {
        $hoje = $quando->setTime(0, 0);
        $ano  = (int)$hoje->format('Y');

        foreach ([$ano, $ano + 1] as $y) {
            foreach (self::SABBATS as [$nome, $mês, $dia]) {
                $data = $hoje->setDate($y, $mês, $dia);
                if ($data >= $hoje) {
                    return [
                        'nome'   => $nome,
                        'quando' => $data,
                        'faltam' => (int)$hoje->diff($data)->days,
                    ];
                }
            }
        }

        // Inalcançável: o ano seguinte tem sempre um sabbat à frente de hoje.
        // Escrito à mesma porque uma função que promete devolver uma coisa tem
        // de a devolver em todos os caminhos.
        [$nome, $mês, $dia] = self::SABBATS[0];

        return [
            'nome'   => $nome,
            'quando' => $hoje->setDate($ano + 1, $mês, $dia),
            'faltam' => 0,
        ];
    }
}
