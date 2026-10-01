<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ConsultationSlot;

/**
 * O calendário das vagas, semana ou mês, pronto a desenhar.
 *
 * Está num serviço e não num controlador porque a mesma grelha responde a duas
 * perguntas diferentes e é pedida de dois sítios: «como está a semana» e «onde é
 * que ainda há hora». Escrita duas vezes, acabaria com duas maneiras de contar a
 * ocupação, e a segunda a não ser corrigida.
 *
 * A semana começa sempre à segunda, e uma data pedida a meio recua até lá — uma
 * grelha de sete dias que comece à quarta obriga a contar pelos dedos. O mês, pela
 * mesma razão, é desenhado em semanas inteiras: os dias emprestados aos meses
 * vizinhos aparecem, esbatidos, para as colunas quererem dizer sempre o mesmo dia
 * da semana.
 */
final class GrelhaDeVagas
{
    /**
     * @return array{
     *   de: \DateTimeImmutable,
     *   ate: \DateTimeImmutable,
     *   anterior: \DateTimeImmutable,
     *   seguinte: \DateTimeImmutable,
     *   dias: array<string,array{data:\DateTimeImmutable,doMes:bool,vagas:array}>,
     *   resumo: array{vagas:int,lugares:int,tomados:int}
     * }
     */
    public static function montar(bool $mensal, \DateTimeImmutable $pedido, ?int $consultationId = null): array
    {
        /* O que a grelha desenha e o que se vai buscar à base de dados são a
           mesma coisa: da primeira segunda à última noite. No mês isso é mais do
           que o mês — são as semanas inteiras que o contêm. */
        [$de, $ate] = $mensal
            ? self::pontasDoMes($pedido)
            : [self::segundaDe($pedido), self::segundaDe($pedido)->modify('+7 days')];

        $vagas = ConsultationSlot::between($de, $ate, $consultationId);

        /* Todos os dias, e os vazios também: um dia sem vagas é informação — é o
           dia em que a Casa não atende — e desaparecido da grelha não se vê. */
        $mes  = (int)$pedido->format('n');
        $dias = [];
        for ($d = $de; $d < $ate; $d = $d->modify('+1 day')) {
            $dias[$d->format('Y-m-d')] = [
                'data'  => $d,
                'doMes' => !$mensal || (int)$d->format('n') === $mes,
                'vagas' => [],
            ];
        }
        foreach ($vagas as $v) {
            $dias[substr((string)$v['starts_at'], 0, 10)]['vagas'][] = $v;
        }

        /* O resumo conta o período que se está a ver e não a grelha: num mês, os
           dias emprestados às semanas das pontas são de outro mês, e somá-los dava
           uma ocupação que não é a de mês nenhum. */
        $lugares  = 0;
        $tomados  = 0;
        $contadas = 0;
        foreach ($vagas as $v) {
            $dia = substr((string)$v['starts_at'], 0, 10);
            if (empty($v['is_open']) || !$dias[$dia]['doMes']) {
                continue;
            }
            $lugares += (int)$v['capacity'];
            $tomados += (int)$v['seats_taken'];
            $contadas++;
        }

        return [
            'de'       => $de,
            'ate'      => $ate,
            'anterior' => $mensal ? $pedido->modify('first day of last month') : $de->modify('-7 days'),
            'seguinte' => $mensal ? $pedido->modify('first day of next month') : $ate,
            'dias'     => $dias,
            'resumo'   => ['vagas' => $contadas, 'lugares' => $lugares, 'tomados' => $tomados],
        ];
    }

    /** O dia pedido, ou hoje. */
    public static function dia(string $raw): \DateTimeImmutable
    {
        $raw = trim($raw);
        $d   = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);

        return ($d !== false && $d->format('Y-m-d') === $raw)
            ? $d
            : new \DateTimeImmutable('today');
    }

    /** A segunda-feira da semana de um dia. `N` é 1 à segunda, daí o N-1. */
    public static function segundaDe(\DateTimeImmutable $d): \DateTimeImmutable
    {
        return $d->modify('-' . ((int)$d->format('N') - 1) . ' days');
    }

    /**
     * Da segunda que abre o mês ao dia a seguir ao domingo que o fecha.
     *
     * @return array{0:\DateTimeImmutable,1:\DateTimeImmutable}
     */
    private static function pontasDoMes(\DateTimeImmutable $d): array
    {
        return [
            self::segundaDe($d->modify('first day of this month')),
            // O intervalo é aberto à direita, daí o dia a mais.
            self::segundaDe($d->modify('last day of this month'))->modify('+7 days'),
        ];
    }
}
