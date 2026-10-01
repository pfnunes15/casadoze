<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\ConsultationSlot;
use App\Services\GeradorDeVagas;
use App\Services\GrelhaDeVagas;

/**
 * As marcações: o calendário e a lista.
 *
 * Um ecrã só, com duas vistas, porque são as duas metades da mesma pergunta. O
 * **calendário** é onde se marca — alguém liga, quer vir na quinta, e o que é
 * preciso ver é onde há hora. A **lista** é quem vem: a folha que se olha de
 * manhã, com os nomes e os contactos.
 *
 * Eram dois sítios na barra lateral, «Vagas» e «Marcações», e a divisão era do
 * programa e não de quem o usa: uma vaga só existe para ser marcada, e quem a
 * fosse gerir estava sempre a saltar de um ecrã para o outro a meio de uma
 * chamada.
 *
 * **Não se editam marcações.** Uma marcação é o registo do que alguém marcou, e
 * um registo que se reescreve não é um registo — cancela-se, e marca-se outra. O
 * que se pode fazer é cancelar, e cancelar devolve a hora no mesmo instante.
 */
final class BookingAdminController extends Controller
{
    private const DIAS_NA_LISTA = 14;

    public function index(Request $req): Response
    {
        return $req->string('vista') === 'lista'
            ? $this->lista($req)
            : $this->calendario($req);
    }

    /**
     * Onde há hora.
     *
     * A semana é para quem gere — cabem os cartões e vê-se de relance como está
     * a encher. O mês é para quem atende o telefone: «tem alguma coisa em
     * Outubro?» não se responde a folhear cinco semanas uma a uma.
     */
    private function calendario(Request $req): Response
    {
        /* O calendário enche-se a si próprio.

           Em vez de um botão «Encher o calendário», que era a pergunta errada a
           fazer a quem gere: ninguém que atende o telefone tem de saber que
           existe um gerador, nem de se lembrar de o correr antes de o horizonte
           acabar. A pergunta é barata — a vaga mais distante que existe — e a
           resposta é quase sempre «não há nada a fazer»: só se gera quando o
           calendário está a menos de duas semanas do fim.

           Sim, é um GET a escrever. É de propósito e é seguro: a inserção é
           `INSERT IGNORE` sobre uma chave única, portanto correr duas vezes ao
           mesmo tempo dá o mesmo que correr uma. O que se ganha é que ninguém
           chega a ver um calendário vazio por esquecimento. Num site com muito
           movimento isto passa para o cron — ver bin/gerar-vagas.php. */
        GeradorDeVagas::correrSePreciso();

        $mensal = $req->string('vista') === 'mes';
        $pedido = GrelhaDeVagas::dia($req->string('de'));
        $consultaId = $req->int('consulta');

        $grelha = GrelhaDeVagas::montar($mensal, $pedido, $consultaId > 0 ? $consultaId : null);

        return $this->render('admin/marcacoes/calendario', $grelha + [
            'mensal'     => $mensal,
            'pedido'     => $pedido,
            'consultas'  => Consultation::all(),
            'consultaId' => $consultaId,
            /* Quantos lugares uma pessoa precisa. Numa Casa que atende uma pessoa
               de cada vez isto é quase sempre um — mas a lotação de uma hora pode
               ser mais do que um, que é como um círculo ou um workshop se marcam,
               e então a pergunta «cabem quatro?» volta a fazer-se. Com este número
               a grelha responde-lhe: o que não chega para o grupo apaga-se. */
            'grupo'      => max(0, min(99, $req->int('pessoas'))),
            'porVir'     => Booking::upcomingCount(),
            /* Até quando é que o calendário vai. Um mês vazio lá à frente não é
               uma avaria — é o horizonte —, e dizê-lo poupa a quem o vê a
               procurar o que está partido. */
            'horizonte'  => ConsultationSlot::horizonte(),
        ], 'layouts/admin');
    }

    /**
     * Quem vem, dia a dia e dentro do dia por hora.
     *
     * As canceladas aparecem, marcadas: quem olha para o dia quer saber que a das
     * nove se desmarcou, e não que ela nunca existiu.
     */
    private function lista(Request $req): Response
    {
        $de  = GrelhaDeVagas::dia($req->string('de'));
        $ate = $de->modify('+' . self::DIAS_NA_LISTA . ' days');

        $marcacoes = Booking::between($de, $ate, false);

        /* Por dia, e dentro do dia por vaga: às nove chega esta, às dez aquela.
           Agrupada só por dia, a hora repetia-se em cada linha.

           Duas consultas podem começar à mesma hora, por isso a chave é a vaga e
           não a hora. As linhas já vêm ordenadas por `starts_at`, e o PHP guarda
           a ordem de inserção — os grupos saem pela ordem certa. */
        $agenda  = [];
        $pessoas = 0;
        foreach ($marcacoes as $m) {
            $dia  = substr((string)$m['starts_at'], 0, 10);
            $vaga = (int)$m['slot_id'];

            if (!isset($agenda[$dia][$vaga])) {
                $agenda[$dia][$vaga] = ['vaga' => $m, 'marcacoes' => [], 'pessoas' => 0];
            }
            $agenda[$dia][$vaga]['marcacoes'][] = $m;

            if ($m['status'] !== 'cancelada') {
                $agenda[$dia][$vaga]['pessoas'] += (int)$m['people'];
                $pessoas += (int)$m['people'];
            }
        }

        return $this->render('admin/marcacoes/lista', [
            'agenda'  => $agenda,
            'de'      => $de,
            'ate'     => $ate,
            'porVir'  => Booking::upcomingCount(),
            'pessoas' => $pessoas,
        ], 'layouts/admin');
    }

    public function show(Request $req, array $params): Response
    {
        $marcacao = Booking::find((int)$params['id']);
        if (!$marcacao) {
            $this->notFound('Essa marcação não existe.');
        }

        return $this->render('admin/marcacoes/show', ['marcacao' => $marcacao], 'layouts/admin');
    }

    /**
     * Marcar por telefone.
     *
     * O formulário vive no ecrã de uma vaga, que é onde se está a olhar quando o
     * telefone toca: já se escolheu o dia e a hora, e o que falta é escrever o
     * nome de quem ligou.
     *
     * **Passa pelo mesmo Booking::create que o site**, e não por uma inserção
     * própria. É o que faz uma marcação feita ao telefone descontar a hora, não
     * se cruzar com outra consulta e receber um código — tudo o que uma marcação
     * do site recebe. Uma segunda via de escrita acabaria a divergir da primeira,
     * e a que divergisse seria esta, que ninguém testa tantas vezes.
     *
     * O que é diferente é o que se exige: aqui não há caixa de consentimento nem
     * travão de repetição. Quem está a escrever é a Casa, ao telefone com a
     * pessoa, e o consentimento foi dado em voz alta — e um travão de tentativas
     * numa chamada é um travão contra quem atende.
     */
    public function store(Request $req): Response
    {
        $vaga = ConsultationSlot::find($req->int('slot_id'));
        if (!$vaga) {
            $this->flash('error', 'Escolha a hora.');
            return $this->back($req, '/admin/marcacoes');
        }

        $voltar = '/admin/vagas/' . (int)$vaga['id'];

        $nome  = trim($req->string('name'));
        $email = trim($req->string('email'));

        if ($nome === '') {
            $this->flash('error', 'Escreva o nome de quem vem.');
            $this->keepInput($req);
            return $this->redirect($voltar);
        }

        /* O e-mail é obrigatório no site e não aqui. Quem telefona pode não o
           querer dar, e recusar a marcação por causa disso era recusar a
           marcação — o que a Casa precisa é do nome e do telefone. Quando é
           escrito, tem de ser um endereço: guardar «não tem» no campo do e-mail
           é guardar lixo numa coluna que depois se usa para escrever a alguém. */
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Esse e-mail não parece um endereço.');
            $this->keepInput($req);
            return $this->redirect($voltar);
        }

        $modos = Consultation::modos($vaga);
        $modo  = $req->string('mode');
        if (!in_array($modo, $modos, true)) {
            $modo = $modos[0];
        }

        try {
            $marcacao = Booking::create([
                'slot_id'    => (int)$vaga['id'],
                'name'       => $nome,
                'email'      => $email,
                'phone'      => $req->string('phone'),
                'people'     => max(1, $req->int('people', 1)),
                'mode'       => $modo,
                'note'       => $req->string('note'),
                'ip'         => $req->ip(),
                'user_agent' => 'backoffice',
            ]);
        } catch (\RuntimeException $e) {
            error_log('[casadoze][marcacao/admin] ' . $e->getMessage());
            $this->flash('error', 'A agenda está ocupada neste instante. Tente outra vez.');
            $this->keepInput($req);
            return $this->redirect($voltar);
        }

        if ($marcacao === null) {
            /* Ou a hora encheu, ou choca com outra consulta já marcada. As duas
               coisas dizem-se juntas porque a saída é a mesma — escolher outra
               hora — e porque quem está ao telefone não ganha nada com a
               distinção. O ecrã da vaga mostra ao lado o que lá está. */
            $this->flash('error', 'Essa hora já não está livre — ou encheu, ou cruza-se com '
                . 'outra consulta já marcada. Escolha outra.');
            $this->keepInput($req);
            return $this->redirect($voltar);
        }

        clear_old();
        $this->flash('success', 'Marcado: ' . $marcacao['name'] . ', código ' . $marcacao['code'] . '.');

        return $this->redirect($voltar);
    }

    /**
     * Cancelar.
     *
     * Devolve a hora e deixa a marcação na lista, marcada. O e-mail a avisar quem
     * marcou **não** sai daqui: quem cancela do lado da Casa costuma ter uma razão
     * que quer explicar, e um e-mail automático a dizer «a sua consulta foi
     * cancelada» sem dizer porquê é pior do que telefonema nenhum. O endereço e o
     * número ficam à vista, ao lado do botão.
     */
    public function cancel(Request $req, array $params): Response
    {
        $marcacao = Booking::find((int)$params['id']);
        if (!$marcacao) {
            $this->notFound('Essa marcação não existe.');
        }

        if (Booking::cancel((int)$marcacao['id'])) {
            $contacto = trim((string)$marcacao['email']) !== ''
                ? (string)$marcacao['email']
                : (trim((string)$marcacao['phone']) !== '' ? (string)$marcacao['phone'] : '');

            $this->flash('success', 'Marcação de ' . $marcacao['name'] . ' cancelada — a hora '
                . 'voltou a ficar livre.' . ($contacto !== '' ? ' Avise ' . $contacto . '.' : ''));
        } else {
            $this->flash('error', 'Essa marcação já estava cancelada.');
        }

        return $this->back($req, '/admin/marcacoes');
    }
}
