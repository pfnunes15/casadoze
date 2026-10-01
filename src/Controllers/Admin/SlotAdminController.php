<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\ConsultationSlot;

/**
 * Uma vaga: o dia e a hora em que uma consulta acontece.
 *
 * Não tem ecrã de lista próprio — o calendário das vagas é a vista «Calendário»
 * das Marcações, que é onde ele serve para alguma coisa. O que aqui vive é o ecrã
 * de **uma** vaga e o que se lhe faz: mudar a lotação daquela hora, fechá-la sem
 * fechar o dia, abrir uma hora que o horário não tinha, e marcar por telefone
 * quem ligou.
 *
 * As acções estão aqui e não na grelha porque na grelha estavam repetidas
 * cinquenta vezes — um campo de lotação e dois botões por linha — e uma parede de
 * campos não se lê. Aqui há uma vaga só, e ao lado está a lista de quem fica sem
 * consulta se ela for fechada.
 */
final class SlotAdminController extends Controller
{
    /**
     * A lista das vagas é o calendário das marcações.
     *
     * O endereço fica de pé porque há-de acabar em marcadores e em ligações
     * antigas, e um endereço que deixa de existir é um erro que ninguém percebe.
     */
    public function index(Request $req): Response
    {
        $query = [];
        foreach (['de', 'vista', 'consulta', 'pessoas'] as $k) {
            if ($req->string($k) !== '') {
                $query[$k] = $req->string($k);
            }
        }

        return $this->redirect('/admin/marcacoes' . ($query ? '?' . http_build_query($query) : ''));
    }

    /**
     * Uma hora certa: a que o horário não tem, aberta porque alguém a pediu.
     *
     * Nasce igual às outras e vive igual às outras. O que a distingue é a origem,
     * e a origem só serve para o gerador saber que não é dele — e para o ecrã da
     * consulta saber quais são as horas que alguém escreveu à mão.
     */
    public function store(Request $req): Response
    {
        $consulta = Consultation::find($req->int('consultation_id'));
        if (!$consulta) {
            $this->flash('error', 'Escolha a consulta.');
            return $this->back($req, '/admin/consultas');
        }

        $voltar = '/admin/consultas/' . (int)$consulta['id'] . '/edit#quando';

        $dia  = trim($req->string('on_date'));
        $hora = trim($req->string('start_time'));
        $d    = \DateTimeImmutable::createFromFormat('!Y-m-d', $dia);

        if ($d === false || $d->format('Y-m-d') !== $dia
            || preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora) !== 1) {
            $this->flash('error', 'Essa data ou essa hora não existem.');
            return $this->redirect($voltar);
        }

        $capacidade = $req->int('capacity');
        if ($capacidade < 1) {
            $capacidade = (int)$consulta['capacity'];
        }

        try {
            ConsultationSlot::create([
                'consultation_id' => (int)$consulta['id'],
                'starts_at'       => $dia . ' ' . $hora . ':00',
                'capacity'        => $capacidade,
                'source'          => 'avulsa',
                'note'            => $req->string('note'),
            ]);
            $this->flash('success', 'Hora acrescentada: ' . $d->format('d/m/Y') . ' às ' . $hora . '.');
        } catch (\PDOException $e) {
            /* A chave única é consulta mais hora: já lá está uma. Dizê-lo é melhor
               do que criar uma segunda que ninguém saberia distinguir. */
            $this->flash('error', 'Essa consulta já tem uma hora marcada nesse instante.');
        }

        return $this->redirect($voltar);
    }

    public function update(Request $req, array $params): Response
    {
        $vaga = ConsultationSlot::find((int)$params['id']);
        if (!$vaga) {
            $this->notFound('Essa vaga não existe.');
        }

        $capacidade = $req->int('capacity');
        ConsultationSlot::setCapacity((int)$vaga['id'], $capacidade);

        $depois = ConsultationSlot::find((int)$vaga['id']);
        if ($depois !== null && (int)$depois['capacity'] > $capacidade) {
            /* setCapacity nunca desce abaixo do que já está marcado: baixar a
               lotação não desmarca ninguém, e uma vaga que dissesse um com duas
               pessoas lá dentro estaria a mentir. */
            $this->flash('error', 'A lotação ficou em ' . (int)$depois['capacity']
                . ': é o que já está marcado. Para descer mais, cancele marcações primeiro.');
        } else {
            $this->flash('success', 'Lotação guardada.');
        }

        return $this->back($req, '/admin/vagas/' . (int)$vaga['id']);
    }

    public function toggle(Request $req, array $params): Response
    {
        $vaga = ConsultationSlot::find((int)$params['id']);
        if (!$vaga) {
            $this->notFound('Essa vaga não existe.');
        }

        $abrir = empty($vaga['is_open']);
        ConsultationSlot::setOpen((int)$vaga['id'], $abrir);

        $marcadas = ConsultationSlot::liveBookings((int)$vaga['id']);

        $this->flash($abrir || $marcadas === 0 ? 'success' : 'error',
            $abrir
                ? 'Hora aberta.'
                : ($marcadas > 0
                    ? 'Hora fechada — mas tem ' . $marcadas . ' marcação'
                      . ($marcadas === 1 ? '' : 'ões') . ' de pé. Fechar impede novas; '
                      . 'as que já existem continuam, e é preciso avisar essas pessoas.'
                    : 'Hora fechada.'));

        return $this->back($req, '/admin/vagas/' . (int)$vaga['id']);
    }

    /** Apagar uma vaga com gente marcada não se faz: cancelam-se as marcações. */
    public function destroy(Request $req, array $params): Response
    {
        $vaga = ConsultationSlot::find((int)$params['id']);
        if (!$vaga) {
            $this->notFound('Essa vaga não existe.');
        }

        $marcadas = ConsultationSlot::liveBookings((int)$vaga['id']);
        if ($marcadas > 0) {
            $this->flash('error', 'Essa hora tem ' . $marcadas . ' marcação'
                . ($marcadas === 1 ? '' : 'ões') . '. Cancele-as primeiro, ou feche a hora — '
                . 'fechar impede novas sem apagar as que existem.');
            return $this->back($req, '/admin/vagas/' . (int)$vaga['id']);
        }

        $dia = substr((string)$vaga['starts_at'], 0, 10);
        ConsultationSlot::delete((int)$vaga['id']);
        $this->flash('success', 'Hora eliminada.');

        // A vaga deixou de existir, por isso o regresso é ao calendário, no dia dela.
        return $this->redirect('/admin/marcacoes?de=' . $dia);
    }

    /** Quem vem a esta hora, e o que se lhe pode fazer. Abre do calendário. */
    public function show(Request $req, array $params): Response
    {
        $vaga = ConsultationSlot::find((int)$params['id']);
        if (!$vaga) {
            $this->notFound('Essa vaga não existe.');
        }

        return $this->render('admin/vagas/show', [
            'vaga'      => $vaga,
            'marcacoes' => Booking::forSlot((int)$vaga['id']),
            /* Com o que se cruza, se se cruzar com alguma coisa. É a informação que
               falta a quem vai marcar por telefone: a vaga pode ter lugar e a hora
               estar tomada por outra consulta — uma pessoa, uma mesa. Mostrado aqui
               para quem atende saber antes de prometer, e não depois de o
               formulário recusar. */
            'choque'    => ConsultationSlot::conflict((int)$vaga['id']),
            'modos'     => Consultation::modos($vaga),
        ], 'layouts/admin');
    }
}
