<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Consultation;
use App\Models\ConsultationClosure;
use App\Models\ConsultationSlot;
use App\Services\Lua;

/**
 * As consultas, do lado de fora: a lista e a ficha de cada uma.
 *
 * **Isto não existia.** A marcação pública saiu quando o site foi reduzido a
 * uma página, e a razão escrita na altura era que a maquete não tinha agenda na
 * porta de entrada. O desenho novo tem — `docs/maquete/Consultas.dc.html`, com
 * calendário, vagas e remarcação — e por isso volta.
 *
 * Marcar em si continua a ser do BookingController, que também voltou do
 * histórico sem lhe tocar: o lugar segura-se antes de a marcação ser escrita, e
 * é o mesmo caminho que o backoffice usa quando marca por telefone. Uma segunda
 * via de escrita acabaria a divergir da primeira.
 */
final class ConsultationController extends Controller
{
    /**
     * Quantos meses de calendário se podem folhear.
     *
     * As vagas são geradas até 90 dias — isso é do backoffice, que quer ver o
     * trimestre. Aqui a pessoa folheia enquanto houver vagas e nem um mês mais:
     * um calendário que avança para sempre sobre meses vazios é um calendário
     * que ensina que não há nada.
     */
    private const MESES = 4;

    /** A lista. */
    public function index(Request $req): Response
    {
        $consultas = Consultation::published();

        /* A próxima vaga de cada uma, para o cartão a poder dizer. Uma pergunta
           por consulta; são quatro. Uma consulta que se marca por conversa — os
           trabalhos espirituais — não tem vagas e não diz nenhuma. */
        $próximas = [];
        foreach ($consultas as $c) {
            if (empty($c['is_bookable'])) {
                continue;
            }
            $vagas = ConsultationSlot::bookable((int)$c['id'], 90);
            if ($vagas !== []) {
                $próximas[(int)$c['id']] = (string)$vagas[0]['starts_at'];
            }
        }

        return $this->render('consultas/index', [
            'consultas' => $consultas,
            'próximas'  => $próximas,
        ]);
    }

    /** Uma consulta, com a agenda. */
    public function show(Request $req, array $params): Response
    {
        $consulta = Consultation::findBySlug((string)($params['slug'] ?? ''));

        /* O mesmo 404 de uma página que não existe. Uma consulta despublicada
           não deve ser distinguível de uma que nunca existiu. */
        if ($consulta === null || empty($consulta['is_published'])) {
            $this->notFound();
        }

        $id = (int)$consulta['id'];

        /* As vagas, agrupadas por dia. É a mesma janela que o `/consultas/vagas`
           devolve em JSON — ver BookingController::DIAS_NA_PAGINA —, porque quem
           tem JavaScript e quem não tem têm de ver a mesma agenda. */
        $dias = [];
        if (!empty($consulta['is_bookable'])) {
            foreach (ConsultationSlot::bookable($id, self::MESES * 31) as $vaga) {
                $quando = new \DateTimeImmutable((string)$vaga['starts_at']);
                $dias[$quando->format('Y-m-d')][] = [
                    'id'     => (int)$vaga['id'],
                    'hora'   => $quando->format('H:i'),
                    'livres' => (int)$vaga['livres'],
                ];
            }
        }

        /* Os dias fechados, para o calendário os poder marcar em vez de os
           deixar simplesmente sem horas. «Fechado» e «sem vagas» são duas
           coisas diferentes para quem está a olhar.

           Um conjunto e não uma lista: a chave é o dia, ou «dia|consulta» para
           um fecho que só é desta. Ver ConsultationClosure::isClosed. */
        $meses = $this->meses(array_keys($dias));
        $fechados = ConsultationClosure::betweenAsSet(
            $meses[0],
            end($meses)->modify('last day of this month')
        );

        /* As outras, para o fim da página. */
        $outras = array_values(array_filter(
            Consultation::published(),
            static fn(array $c): bool => (int)$c['id'] !== $id
        ));

        return $this->render('consultas/show', [
            'consulta' => $consulta,
            'dias'     => $dias,
            'fechados' => $fechados,
            'meses'    => $meses,
            'outras'   => $outras,
        ]);
    }

    /**
     * Os meses a desenhar, do corrente ao último com vagas.
     *
     * Sempre pelo menos o corrente: uma consulta sem vaga nenhuma mostra o mês
     * em que se está, vazio, e isso diz mais do que não mostrar calendário
     * nenhum — diz que há agenda e que ela está cheia.
     *
     * @param  string[] $diasComVagas
     * @return \DateTimeImmutable[] o primeiro dia de cada mês
     */
    private function meses(array $diasComVagas): array
    {
        $hoje = new \DateTimeImmutable('first day of this month', new \DateTimeZone(date_default_timezone_get()));
        $último = $hoje;

        foreach ($diasComVagas as $dia) {
            $mês = new \DateTimeImmutable($dia . ' 00:00:00');
            $mês = $mês->modify('first day of this month')->setTime(0, 0);
            if ($mês > $último) {
                $último = $mês;
            }
        }

        $fim = $hoje->modify('+' . (self::MESES - 1) . ' months');
        if ($último > $fim) {
            $último = $fim;
        }

        $out = [];
        for ($m = $hoje; $m <= $último; $m = $m->modify('+1 month')) {
            $out[] = $m;
        }

        return $out;
    }
}
