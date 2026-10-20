<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\ConsultationSlot;
use App\Models\Practitioner;

/**
 * A Sala: quem lê as cartas na Casa.
 *
 * A lista e o perfil de cada pessoa. Ver `docs/maquete/Sala.dc.html`.
 *
 * **Marcar continua a ser com a consulta e não com a pessoa.** O perfil diz que
 * leituras é que ela dá e leva à ficha de cada uma, onde está a agenda. Dar
 * agenda própria a cada taróloga é acrescentar `practitioner_id` às vagas, ao
 * gerador e ao backoffice das marcações — mexer no motor que já trabalha —, e
 * fica para quando a equipa existir a sério: hoje três dos quatro nomes ainda
 * estão entre parênteses rectos.
 */
final class SalaController extends Controller
{
    public function index(Request $req): Response
    {
        $pessoas = Practitioner::published();

        $consultas = [];
        foreach ($pessoas as $p) {
            $consultas[(int)$p['id']] = Practitioner::consultas((int)$p['id']);
        }

        return $this->render('sala/index', [
            'pessoas'   => $pessoas,
            'consultas' => $consultas,
        ]);
    }

    public function show(Request $req, array $params): Response
    {
        $pessoa = Practitioner::findBySlug((string)($params['slug'] ?? ''));

        if ($pessoa === null || empty($pessoa['is_published'])) {
            $this->notFound();
        }

        $id = (int)$pessoa['id'];
        $consultas = Practitioner::consultas($id);

        /* A próxima vaga de cada leitura que esta pessoa dá. É a vaga da
           consulta e não dela — ver a nota no topo desta classe. */
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

        $outras = array_values(array_filter(
            Practitioner::published(),
            static fn(array $p): bool => (int)$p['id'] !== $id
        ));

        return $this->render('sala/show', [
            'pessoa'    => $pessoa,
            'consultas' => $consultas,
            'próximas'  => $próximas,
            'outras'    => $outras,
        ]);
    }
}
