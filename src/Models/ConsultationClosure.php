<?php
declare(strict_types=1);

namespace App\Models;

use Admedia\Core\App;

/**
 * Um dia em que a Casa não atende.
 *
 * `consultation_id` a NULL fecha tudo — Natal, Ano Novo, uma semana de férias —,
 * e é isso que se quer escrever uma vez e não uma vez por consulta.
 *
 * Fechar um dia não apaga as vagas que já lá estavam: fecha-as. A diferença
 * importa quando já há gente marcada — as marcações continuam a existir, o
 * backoffice mostra-as, e alguém tem de avisar essas pessoas. Um fecho que
 * apagasse as vagas apagava também a lista de quem era preciso avisar. Ver
 * ConsultationSlot::closeDay.
 *
 * **`every_year` faz do dia uma regra.** O Natal não é uma data, é 25 de
 * Dezembro sempre; escrito como data, no dia 26 deixava de existir e alguém
 * tinha de se lembrar de o voltar a escrever em Novembro — que é a espécie de
 * coisa de que ninguém se lembra. Numa linha anual o ano guardado quer dizer
 * «desde quando é que isto vale», e o mês e o dia é que mandam.
 */
final class ConsultationClosure
{
    /**
     * Os encerramentos que ainda valem, cada um com a data em que acontece a
     * seguir.
     *
     * Um anual nunca «passa»: o Natal de 26 de Dezembro é o Natal do ano que vem.
     * Por isso a condição não é só a data — é a data **ou** ser anual — e a
     * ordenação é feita pela próxima ocorrência, que é a que quem lê tem na
     * cabeça.
     *
     * @return array<int,array<string,mixed>> com a chave extra `proxima`
     */
    public static function upcoming(int $limit = 200): array
    {
        $limit  = max(1, min(500, $limit));
        $linhas = App::instance()->db()->fetchAll(
            "SELECT f.*, c.name AS consultation_name
               FROM consultation_closures f
               LEFT JOIN consultations c ON c.id = f.consultation_id
              WHERE f.every_year = 1 OR f.on_date >= CURDATE()
              ORDER BY f.on_date LIMIT {$limit}"
        );

        $out = [];
        foreach ($linhas as $l) {
            $proxima = self::proxima($l);
            if ($proxima === null) {
                continue;
            }
            $l['proxima'] = $proxima;
            $out[] = $l;
        }

        usort($out, static fn(array $a, array $b): int => $a['proxima'] <=> $b['proxima']);

        return $out;
    }

    /**
     * A próxima vez que este encerramento acontece, ou `null` se já passou.
     *
     * Um anual olha para este ano e, se já foi, para o seguinte — mas nunca para
     * antes do ano em que foi escrito: uma casa que decide fechar ao 1 de Maio a
     * partir de 2027 não fechou o 1 de Maio de 2026.
     *
     * @param array<string,mixed> $linha
     */
    public static function proxima(array $linha, ?\DateTimeImmutable $hoje = null): ?\DateTimeImmutable
    {
        $hoje ??= new \DateTimeImmutable('today');
        $dia   = new \DateTimeImmutable((string)$linha['on_date']);

        if (empty($linha['every_year'])) {
            return $dia >= $hoje ? $dia : null;
        }

        for ($ano = max((int)$hoje->format('Y'), (int)$dia->format('Y')); $ano <= (int)$hoje->format('Y') + 8; $ano++) {
            $candidato = self::noAno($dia, $ano);
            if ($candidato !== null && $candidato >= $hoje) {
                return $candidato;
            }
        }

        return null;
    }

    /**
     * Os dias fechados num intervalo, para o gerador não gerar lá dentro.
     *
     * As linhas anuais vêm todas e são espalhadas pelos anos que o intervalo
     * atravessa — o intervalo é de noventa dias, portanto no máximo dois.
     *
     * @return array<string,true>
     */
    public static function betweenAsSet(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $linhas = App::instance()->db()->fetchAll(
            'SELECT consultation_id, on_date, every_year FROM consultation_closures
              WHERE every_year = 1 OR on_date BETWEEN :de AND :ate',
            ['de' => $from->format('Y-m-d'), 'ate' => $to->format('Y-m-d')]
        );

        /* Uma chave «dia» fecha a Casa toda; «dia|consulta» fecha só essa. Assim
           quem pergunta faz duas leituras num array em vez de uma consulta à base
           de dados por cada dia gerado. */
        $set = [];
        foreach ($linhas as $l) {
            foreach (self::ocorrencias($l, $from, $to) as $dia) {
                $set[$l['consultation_id'] === null ? $dia : $dia . '|' . (int)$l['consultation_id']] = true;
            }
        }
        return $set;
    }

    /**
     * As datas em que este encerramento cai, dentro de um intervalo.
     *
     * @param array<string,mixed> $linha
     * @return array<int,string> em Y-m-d
     */
    public static function ocorrencias(array $linha, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $dia = new \DateTimeImmutable((string)$linha['on_date']);

        if (empty($linha['every_year'])) {
            return ($dia >= $from && $dia <= $to) ? [$dia->format('Y-m-d')] : [];
        }

        $out = [];
        for ($ano = (int)$from->format('Y'); $ano <= (int)$to->format('Y'); $ano++) {
            // Nunca antes do ano em que foi escrito — ver `proxima()`.
            if ($ano < (int)$dia->format('Y')) {
                continue;
            }
            $candidato = self::noAno($dia, $ano);
            if ($candidato !== null && $candidato >= $from && $candidato <= $to) {
                $out[] = $candidato->format('Y-m-d');
            }
        }
        return $out;
    }

    /**
     * O mesmo mês e dia, noutro ano — ou `null` quando esse dia não existe lá.
     *
     * É o 29 de Fevereiro. Um encerramento anual a 29 de Fevereiro vale só nos
     * anos em que o 29 de Fevereiro existe: é o que a data quer dizer, e mudá-lo
     * para o 28 ou para o 1 de Março seria decidir por quem o escreveu.
     */
    private static function noAno(\DateTimeImmutable $dia, int $ano): ?\DateTimeImmutable
    {
        $md = $dia->format('m-d');
        $d  = \DateTimeImmutable::createFromFormat('!Y-m-d', $ano . '-' . $md);

        return ($d !== false && $d->format('m-d') === $md) ? $d : null;
    }

    /** @param array<string,true> $set */
    public static function isClosed(array $set, string $dia, int $consultationId): bool
    {
        return isset($set[$dia]) || isset($set[$dia . '|' . $consultationId]);
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d): int
    {
        $consultationId = (int)($d['consultation_id'] ?? 0);

        return App::instance()->db()->insert('consultation_closures', [
            'consultation_id' => $consultationId > 0 ? $consultationId : null,
            'on_date'         => (string)$d['on_date'],
            'every_year'      => !empty($d['every_year']) ? 1 : 0,
            'note'            => mb_substr(trim((string)($d['note'] ?? '')), 0, 190),
        ]);
    }

    public static function delete(int $id): void
    {
        App::instance()->db()->delete('consultation_closures', 'id = :id', ['id' => $id]);
    }

    public static function find(int $id): ?array
    {
        return App::instance()->db()->fetch('SELECT * FROM consultation_closures WHERE id = :id', ['id' => $id]);
    }
}
