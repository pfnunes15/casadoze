<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\RateLimiter;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\ConsultationSlot;

/**
 * Marcar uma consulta.
 *
 * Escolhe-se uma hora que existe e o lugar fica seguro no instante — e não se
 * manda um pedido para alguém responder em 48 horas a dizer se calha. A
 * diferença não é de comodidade: é a diferença entre a Casa saber quem vem na
 * quinta e a Casa ter de somar e-mails.
 *
 * **O lugar segura-se antes de a marcação ser escrita**, e com a agenda trancada
 * — ver Booking::create, que explica porque é um tranco com nome e não um
 * `FOR UPDATE`. É o mesmo caminho que o backoffice usa quando marca por telefone.
 *
 * **Não cobra.** Paga-se à chegada, ou combina-se na conversa. O dia em que
 * passar a cobrar, o que muda é o fim de `store`: em vez de dar a marcação por
 * confirmada, cria a encomenda e manda a pessoa ao pagamento. O lugar continua a
 * segurar-se antes, que é o que faz a agenda não vender a hora que não tem.
 *
 * **As frases estão no catálogo e não aqui.** O site é servido em cinco línguas,
 * e uma mensagem de erro escrita dentro de um controlador é uma mensagem que só
 * existe numa delas. Ver lang/pt.php e o `__()`.
 */
final class BookingController extends Controller
{
    /**
     * Quantos dias para a frente é que a página mostra.
     *
     * O calendário é gerado até 90 dias, e isso é do backoffice: quem gere quer
     * ver o trimestre. A página não — quem marca uma leitura marca-a para as
     * próximas semanas, e noventa dias de botões dão um cartão com cem datas que
     * cresce para fora do ecrã e esconde tudo o que vem a seguir.
     *
     * Quatro semanas é o que a lista aguenta sem deixar de se ler. Quem quiser
     * mais longe telefona — e é o que a Casa prefere, porque uma leitura marcada
     * para daqui a três meses é uma leitura que a pessoa esquece.
     *
     * **Pública porque o bloco das consultas a lê.** A lista do `<noscript>` é
     * montada na vista, com os dados que o servidor já tem à mão, e tem de cobrir
     * exactamente a mesma janela que o JSON — senão quem tem JavaScript vê quatro
     * semanas e quem não tem vê três meses. Escrito duas vezes, um dos dois
     * números ficava para trás.
     */
    public const DIAS_NA_PAGINA = 28;

    /** Quantas marcações do mesmo e-mail num dia antes de se desconfiar. */
    private const MAX_POR_DIA = 4;

    /* O travão de repetição, que é outra coisa: não é «quantas marcações cabem»,
       é «quantas tentativas seguidas é que são gente». */
    private const MAX_POR_EMAIL = 5;
    private const MAX_POR_IP    = 15;
    private const JANELA        = 3600;

    /**
     * As horas livres de uma consulta, em JSON.
     *
     * A maquete escolhe o dia sem recarregar a página, e por isso há aqui um
     * endereço que devolve dados em vez de HTML. GET, e sem nada que identifique
     * quem pergunta: a resposta só diz que às 21h de quinta há lugar, que é o que
     * qualquer pessoa vê na página de qualquer maneira.
     *
     * Agrupado por dia porque é assim que é escolhido — primeiro o dia, depois a
     * hora — e fazer o agrupamento no browser era mandar a mesma lista para ser
     * arrumada outra vez do outro lado.
     */
    public function slots(Request $req): Response
    {
        $consulta = Consultation::find($req->int('consulta'));

        if ($consulta === null || empty($consulta['is_published']) || empty($consulta['is_bookable'])) {
            return Response::json(['dias' => []], 404);
        }

        $dias = [];
        foreach (ConsultationSlot::bookable((int)$consulta['id'], self::DIAS_NA_PAGINA) as $vaga) {
            $quando = new \DateTimeImmutable((string)$vaga['starts_at']);
            $ymd    = $quando->format('Y-m-d');

            $dias[$ymd]['dia']    = $ymd;
            $dias[$ymd]['rotulo'] = format_date($ymd, 'j \d\e F');
            $dias[$ymd]['horas'][] = [
                'id'     => (int)$vaga['id'],
                'hora'   => $quando->format('H:i'),
                'livres' => (int)$vaga['livres'],
            ];
        }

        return Response::json([
            'consulta' => [
                'id'       => (int)$consulta['id'],
                'nome'     => (string)$consulta['name'],
                'duracao'  => (int)$consulta['duration_min'],
                'modos'    => Consultation::modos($consulta),
                'maximo'   => (int)$consulta['max_party'],
            ],
            // Sem as chaves: um array indexado é o que o JavaScript quer iterar,
            // e um objecto com datas por chave sai dele desordenado conforme o
            // motor. A ordem é a das horas, e é esta lista que a guarda.
            'dias' => array_values($dias),
        ]);
    }

    public function store(Request $req): Response
    {
        $back = $this->voltarPara($req);

        /* O alçapão. Um campo escondido que uma pessoa nunca vê e que um robô
           preenche por o encontrar no documento. Respondido com o mesmo obrigado
           que uma pessoa recebe: dizer a um robô que foi apanhado só o ensina a
           não cair da próxima vez. */
        if (trim($req->string('website')) !== '') {
            error_log('[casadoze][marcacao] alçapão preenchido a partir de ' . $req->ip());
            $this->flash('marcacao.ok', __('marcacao.thanks'));
            return $this->redirect($back);
        }

        $vaga = ConsultationSlot::find($req->int('slot_id'));
        if ($vaga === null) {
            $this->flash('marcacao.bad', __('marcacao.pick'));
            $this->keepInput($req);
            return $this->redirect($back);
        }

        $dados = $this->check($req, $vaga);
        if ($dados === null) {
            $this->flash('marcacao.bad', __('marcacao.missing'));
            $this->keepInput($req);
            return $this->redirect($back);
        }

        /* O mesmo e-mail a marcar quatro vezes num dia é ou um engano ou um
           guião. Nos dois casos, o que se faz é o mesmo: parar e mandar falar com
           a Casa. */
        if (Booking::recentFrom($dados['email'], 24) >= self::MAX_POR_DIA) {
            $this->flash('marcacao.bad', __('marcacao.too_many_same_email'));
            $this->keepInput($req);
            return $this->redirect($back);
        }

        $limiter = new RateLimiter($this->db);
        $wait = max(
            $limiter->retryAfter('marcacao', 'identifier', $dados['email'], self::MAX_POR_EMAIL, self::JANELA),
            $limiter->retryAfter('marcacao', 'ip', $req->ip(), self::MAX_POR_IP, self::JANELA),
        );
        if ($wait > 0) {
            $this->flash('marcacao.bad', __('marcacao.slow_down', ['wait' => RateLimiter::describeWait($wait)]));
            $this->keepInput($req);
            return $this->redirect($back);
        }
        $limiter->hit('marcacao', $req->ip(), $dados['email']);

        try {
            $marcacao = Booking::create([
                'slot_id'    => (int)$vaga['id'],
                'name'       => $dados['name'],
                'email'      => $dados['email'],
                'phone'      => $dados['phone'],
                'people'     => $dados['people'],
                'mode'       => $dados['mode'],
                'note'       => $dados['note'],
                'ip'         => $req->ip(),
                'user_agent' => $req->userAgent(),
            ]);
        } catch (\RuntimeException $e) {
            /* A agenda não se deixou trancar em cinco segundos. Não é a hora
               cheia — é o sistema ocupado —, e dizer «essa hora já foi» seria
               mentir sobre o que aconteceu. */
            error_log('[casadoze][marcacao] ' . $e->getMessage());
            $this->flash('marcacao.bad', __('marcacao.busy'));
            $this->keepInput($req);
            return $this->redirect($back);
        }

        /* `null` quer dizer que a hora deixou de estar livre entre carregar a
           página e carregar no botão — ou porque encheu, ou porque outra consulta
           que se cruza com ela ficou marcada. É a única maneira honesta de o
           dizer, porque só se sabe ao tentar, e o que se oferece a seguir é
           escolher outra hora, que é o que a pessoa quer fazer. */
        if ($marcacao === null) {
            $this->flash('marcacao.bad', __('marcacao.taken'));
            $this->keepInput($req);
            return $this->redirect($back);
        }

        clear_old();

        /* O endereço leva o token e não o código: o código diz-se ao telefone e é
           curto de propósito, e um endereço adivinhável dava a qualquer pessoa a
           marcação de outra — com o nome, o telefone e a razão por que vem. */
        return $this->redirect(locale_url('/marcacao/' . $marcacao['token']));
    }

    /** A marcação feita, e o que se pode fazer com ela. */
    public function show(Request $req, array $params): Response
    {
        $marcacao = Booking::findByToken((string)$params['token']);
        if ($marcacao === null) {
            $this->notFound(__('marcacao.not_found'));
        }

        return $this->render('consultas/feita', [
            'marcacao' => $marcacao,
            'consulta' => Consultation::find((int)$marcacao['consultation_id']),
            'acabou'   => new \DateTimeImmutable((string)$marcacao['starts_at']) < new \DateTimeImmutable(),
        ], 'layouts/public');
    }

    /**
     * Desmarcar.
     *
     * Devolve o lugar no mesmo instante — é o que faz a hora voltar a estar livre
     * para outra pessoa em vez de ficar guardada para alguém que já disse que não
     * vem.
     */
    public function cancel(Request $req, array $params): Response
    {
        $marcacao = Booking::findByToken((string)$params['token']);
        if ($marcacao === null) {
            $this->notFound(__('marcacao.not_found'));
        }

        $quando = new \DateTimeImmutable((string)$marcacao['starts_at']);
        if ($quando < new \DateTimeImmutable()) {
            $this->flash('marcacao.bad', __('marcacao.already_happened'));
            return $this->redirect(locale_url('/marcacao/' . $marcacao['token']));
        }

        /* Uma chamada só. Chamar duas vezes para escolher a mensagem devolvia
           sempre `false` à segunda — é essa a razão de `cancel` ter um `WHERE`
           com o estado — e a mensagem dizia o contrário do que tinha acontecido. */
        if (Booking::cancel((int)$marcacao['id'])) {
            $this->flash('marcacao.ok', __('marcacao.cancelled'));
        } else {
            $this->flash('marcacao.bad', __('marcacao.was_cancelled'));
        }

        return $this->redirect(locale_url('/marcacao/' . $marcacao['token']));
    }

    /**
     * O que veio do formulário, lido.
     *
     * @param array<string,mixed> $vaga
     * @return array<string,mixed>|null
     */
    private function check(Request $req, array $vaga): ?array
    {
        $name   = trim($req->string('name'));
        $email  = trim($req->string('email'));
        $phone  = trim($req->string('phone'));
        $note   = trim($req->string('note'));
        $mode   = trim($req->string('mode'));
        $people = $req->int('people', 1);
        $errors = [];

        if ($name === '') {
            $errors['name'][] = __('marcacao.err.name');
        } elseif (mb_strlen($name) > 190) {
            $errors['name'][] = __('marcacao.err.name_long');
        }

        if ($email === '') {
            $errors['email'][] = __('marcacao.err.email');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'][] = __('marcacao.err.email_bad');
        }

        if (mb_strlen($phone) > 60) {
            $errors['phone'][] = __('marcacao.err.phone_long');
        }

        /* Onde é a consulta. Numa que é só presencial ou só online não há nada a
           perguntar e o que vier do formulário é ignorado — o que manda é a
           consulta. Numa que é as duas, a escolha é obrigatória: adivinhá-la dava
           uma pessoa à porta de uma consulta que era por vídeo, ou o contrário. */
        $modos = Consultation::modos($vaga);
        if (count($modos) === 1) {
            $mode = $modos[0];
        } elseif (!in_array($mode, $modos, true)) {
            $errors['mode'][] = __('marcacao.err.mode');
        }

        /* Dois tectos, e os dois valem: o que a consulta deixa marcar de uma vez,
           e o que resta na hora. O primeiro é uma regra da Casa, o segundo é
           físico. O `hold` volta a verificar o segundo no instante da escrita,
           porque entre isto e essa linha cabe outra marcação. */
        $livres = max(0, (int)$vaga['capacity'] - (int)$vaga['seats_taken']);
        $tecto  = min((int)$vaga['max_party'], $livres);

        if ($people < 1) {
            $errors['people'][] = __('marcacao.err.people');
        } elseif ($people > $tecto) {
            $errors['people'][] = $livres < (int)$vaga['max_party']
                ? __('marcacao.err.only_left', ['count' => $livres])
                : __('marcacao.err.call_us', ['max' => (int)$vaga['max_party']]);
        }

        if (mb_strlen($note) > 500) {
            $errors['note'][] = __('marcacao.err.note_long');
        }

        if (!$req->string('consent')) {
            $errors['consent'][] = __('marcacao.err.consent');
        }

        if ($errors) {
            $this->keepInput($req);
            $this->session->set('_errors', $errors);
            return null;
        }

        return [
            'name'   => $name,
            'email'  => $email,
            'phone'  => $phone,
            'people' => $people,
            'mode'   => $mode,
            'note'   => $note,
        ];
    }

    /** A página de onde o formulário veio, para o erro aparecer ao pé dele. */
    private function voltarPara(Request $req): string
    {
        $de = trim($req->string('source'));
        return $de !== '' && str_starts_with($de, '/') ? $de : locale_url('/');
    }
}
