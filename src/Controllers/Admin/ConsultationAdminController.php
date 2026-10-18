<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Admedia\Cms\FieldSpec;
use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Consultation;
use App\Models\ConsultationClosure;
use App\Models\ConsultationSchedule;
use App\Models\ConsultationSlot;
use App\Services\GeradorDeVagas;

/**
 * As consultas, e quando é que cada uma acontece.
 *
 * Um ecrã só para a consulta e para as suas horas, e não dois: quem escreve
 * «Leitura completa» está a pensar em quando é que ela acontece, e mandá-lo a
 * outro sítio para o dizer é fazê-lo perder a linha de pensamento.
 *
 * **Uma consulta com horário e uma consulta sem horário são a mesma coisa com
 * horas diferentes**, e por isso não há duas tabelas nem uma coluna a dizer qual é
 * qual. Uma com horário semanal gera as vagas até ao horizonte. Uma sem horário
 * tem as horas que alguém escreveu, uma a uma. Uma com horário também pode ter
 * horas escritas à mão — quem telefonou para um dia que o horário não tem —, e é o
 * mesmo mecanismo.
 *
 * A isso junta-se uma coisa que o enoturismo de onde este ecrã vem não tinha: uma
 * consulta pode não se marcar por hora nenhuma. São os trabalhos espirituais, que
 * começam com uma conversa — e é o `is_bookable` que o diz. Uma consulta assim não
 * tem horário nem vagas, e o ecrã esconde-lhe a secção do «quando» em vez de a
 * mostrar vazia.
 *
 * Os dias encerrados ficam num ecrã seu, porque quase sempre valem para a Casa
 * toda e não para uma consulta.
 *
 * **Guardar o horário volta a encher o calendário.** É o que se espera: quem
 * acrescenta as quintas-feiras quer ver as quintas-feiras no site, não quer saber
 * que existe um gerador. Correr é barato e não mexe no que já lá está — ver
 * App\Services\GeradorDeVagas.
 */
final class ConsultationAdminController extends Controller
{
    /**
     * A lista.
     *
     * Separada em duas: as que se marcam por hora e as que começam com uma
     * conversa. São perguntas diferentes — «o que é que a agenda tem» e «o que é
     * que se pede à Casa» — e juntas numa lista só, uma consulta sem horário
     * parecia uma consulta a quem alguém se esqueceu de pôr horas.
     */
    public function index(Request $req): Response
    {
        $procura = trim($req->string('q'));

        $todas    = Consultation::all();
        $comHora  = [];
        $semHora  = [];

        foreach ($todas as $c) {
            $id = (int)$c['id'];

            $c['vagas_futuras'] = (int)$this->db->fetchColumn(
                'SELECT COUNT(*) FROM consultation_slots
                  WHERE consultation_id = :id AND starts_at >= NOW() AND is_open = 1',
                ['id' => $id]
            );
            $c['marcacoes_futuras'] = Consultation::liveBookings($id);
            $c['horario']           = ConsultationSchedule::forConsultation($id);
            $c['horas_avulsas']     = ConsultationSlot::datedFor($id);

            if ($procura !== '' && mb_stripos((string)$c['name'], $procura) === false) {
                continue;
            }

            if (empty($c['is_bookable'])) {
                $semHora[] = $c;
            } else {
                $comHora[] = $c;
            }
        }

        return $this->render('admin/consultas/index', [
            'comHora' => $comHora,
            'semHora' => $semHora,
            'procura' => $procura,
            'total'   => count($todas),
        ], 'layouts/admin');
    }

    public function create(Request $req): Response
    {
        return $this->render('admin/consultas/edit', [
            'consulta'     => null,
            'horario'      => [],
            'horas'        => [],
            'novaConsulta' => true,
        ], 'layouts/admin');
    }

    /**
     * Criar, com o quando já dentro.
     *
     * A pergunta «quando é que isto acontece» faz-se no mesmo fôlego em que se
     * escreve o nome, e não num segundo ecrã depois de gravar: quem está a criar
     * «Leitura completa às quartas» já sabe que é às quartas. Sem isto, a consulta
     * nascia sem hora nenhuma e ficava à espera de uma segunda visita ao backoffice
     * que muita gente não faz — e uma consulta publicada sem vagas é o caso em que
     * o site a mostra e não deixa marcar nada.
     *
     * Os dias da semana chegam em caixas, e cada um vira uma linha de horário com a
     * mesma hora. São linhas separadas porque a seguir mudam separadamente:
     * acrescenta-se uma hora à terça sem tocar na quinta.
     */
    public function store(Request $req): Response
    {
        $campos = $this->fields($req);
        $id     = Consultation::create($campos);

        /* Uma consulta sem agenda não leva horário, mesmo que alguém tenha ticado
           os dias: o que manda é o `is_bookable`, e gerar vagas para uma consulta
           que a página mostra sem horas era enchê-la de linhas que ninguém vê. */
        if (empty($campos['is_bookable'])) {
            $this->flash('success', 'Criada. Esta começa com uma conversa — não leva horas.');
            return $this->redirect('/admin/consultas/' . $id . '/edit');
        }

        $dias = array_values(array_unique(array_filter(
            array_map('intval', (array)$req->input('weekdays', [])),
            static fn(int $n): bool => isset(ConsultationSchedule::DIAS[$n])
        )));
        sort($dias);

        foreach ($dias as $dia) {
            ConsultationSchedule::create([
                'consultation_id' => $id,
                'weekday'         => $dia,
                'start_time'      => $req->string('start_time'),
                'capacity'        => '',
                'starts_on'       => $req->string('starts_on'),
                'ends_on'         => $req->string('ends_on'),
                'is_active'       => true,
            ]);
        }

        if ($dias === []) {
            $this->flash('success', 'Criada. Agora diga a que horas acontece.');
            return $this->redirect('/admin/consultas/' . $id . '/edit#quando');
        }

        $criadas = GeradorDeVagas::correr($id);

        $this->flash('success', 'Criada, ' . $this->enumerarDias($dias) . '. '
            . ($criadas > 0
                ? $criadas . ' hora' . ($criadas === 1 ? '' : 's') . ' no calendário.'
                : 'Ainda sem horas — verifique as datas.'));

        return $this->redirect('/admin/consultas/' . $id . '/edit#quando');
    }

    /**
     * «às terças», «às terças e quintas», «aos sábados e domingos».
     *
     * A preposição vem do primeiro dia porque é dele que ela concorda: sábado e
     * domingo são masculinos e pedem «aos», os outros cinco pedem «às». Uma
     * mensagem que diga «às domingos» faz quem a lê desconfiar do resto.
     *
     * @param array<int,int> $dias números ISO, já ordenados
     */
    private function enumerarDias(array $dias): string
    {
        $nomes = array_map(
            static fn(int $n): string => mb_strtolower(ConsultationSchedule::DIAS[$n]) . 's',
            $dias
        );
        if ($nomes === []) {
            return '';
        }

        // 6 é sábado e 7 é domingo — ver ConsultationSchedule::DIAS.
        $prep = $dias[0] >= 6 ? 'aos' : 'às';

        if (count($nomes) === 1) {
            return $prep . ' ' . $nomes[0];
        }

        $ultimo = array_pop($nomes);
        return $prep . ' ' . implode(', ', $nomes) . ' e ' . $ultimo;
    }

    public function edit(Request $req, array $params): Response
    {
        $consulta = Consultation::find((int)$params['id']);
        if (!$consulta) {
            $this->notFound('Essa consulta não existe.');
        }

        return $this->render('admin/consultas/edit', [
            'consulta'     => $consulta,
            'horario'      => ConsultationSchedule::forConsultation((int)$consulta['id']),
            'horas'        => ConsultationSlot::datedFor((int)$consulta['id']),
            'novaConsulta' => false,
        ], 'layouts/admin');
    }

    public function update(Request $req, array $params): Response
    {
        $consulta = Consultation::find((int)$params['id']);
        if (!$consulta) {
            $this->notFound('Essa consulta não existe.');
        }

        $campos = $this->fields($req);
        Consultation::update((int)$consulta['id'], $campos + ['slug' => $req->string('slug')]);

        /* Se passou a marcar-se por hora, o calendário enche-se já. Se deixou de o
           ser, as vagas que existem ficam: podem ter gente marcada, e apagá-las
           apagava com elas a lista de quem era preciso avisar. O que muda é que o
           gerador deixa de lhe acrescentar — ver ConsultationSchedule::activeOn. */
        if (!empty($campos['is_bookable'])) {
            GeradorDeVagas::correr((int)$consulta['id']);
        }

        $deixou = !empty($consulta['is_bookable']) && empty($campos['is_bookable']);
        $vagas  = $deixou
            ? (int)$this->db->fetchColumn(
                'SELECT COUNT(*) FROM consultation_slots
                  WHERE consultation_id = :id AND starts_at >= NOW() AND is_open = 1',
                ['id' => (int)$consulta['id']]
              )
            : 0;

        $this->flash($vagas > 0 ? 'error' : 'success', $vagas > 0
            ? 'Consulta guardada — mas tem ' . $vagas . ' hora' . ($vagas === 1 ? '' : 's')
              . ' aberta' . ($vagas === 1 ? '' : 's') . ' no calendário, que o site deixa de '
              . 'mostrar. Feche-as em Marcações se não as quiser.'
            : 'Consulta guardada.');

        return $this->redirect('/admin/consultas/' . (int)$consulta['id'] . '/edit');
    }

    /**
     * Apagar uma consulta leva tudo o que está pendurado nela — horário, horas e as
     * marcações dessas horas. Por isso pergunta-se primeiro quantas pessoas ficam
     * sem consulta, e recusa-se enquanto houver: apagar quem já marcou não é uma
     * coisa que se faça com um clique e um aviso.
     */
    public function destroy(Request $req, array $params): Response
    {
        $consulta = Consultation::find((int)$params['id']);
        if (!$consulta) {
            $this->notFound('Essa consulta não existe.');
        }

        $marcadas = Consultation::liveBookings((int)$consulta['id']);
        if ($marcadas > 0) {
            $this->flash('error', 'Essa consulta tem ' . $marcadas . ' marcação'
                . ($marcadas === 1 ? '' : 'ões') . ' por acontecer. Cancele-as primeiro — '
                . 'ou despublique a consulta, que a tira do site sem apagar nada.');
            return $this->redirect('/admin/consultas');
        }

        Consultation::delete((int)$consulta['id']);
        $this->flash('success', 'Consulta eliminada.');
        return $this->redirect('/admin/consultas');
    }

    /** Tirar do site sem apagar. É o que quase sempre se quer. */
    public function togglePublish(Request $req, array $params): Response
    {
        $consulta = Consultation::find((int)$params['id']);
        if (!$consulta) {
            $this->notFound('Essa consulta não existe.');
        }

        $novo = empty($consulta['is_published']);
        $this->db->update('consultations', ['is_published' => $novo ? 1 : 0],
            'id = :id', ['id' => (int)$consulta['id']]);

        if ($novo && !empty($consulta['is_bookable'])) {
            GeradorDeVagas::correr((int)$consulta['id']);
        }

        $this->flash('success', $novo
            ? 'Consulta publicada.'
            : 'Consulta tirada do site. As marcações ficam.');

        return $this->redirect('/admin/consultas');
    }

    // ---------------------------------------------------------------- horário

    public function storeSchedule(Request $req, array $params): Response
    {
        $consulta = Consultation::find((int)$params['id']);
        if (!$consulta) {
            $this->notFound('Essa consulta não existe.');
        }

        ConsultationSchedule::create([
            'consultation_id' => (int)$consulta['id'],
            'weekday'         => $req->string('weekday'),
            'start_time'      => $req->string('start_time'),
            'capacity'        => $req->string('capacity'),
            'starts_on'       => $req->string('starts_on'),
            'ends_on'         => $req->string('ends_on'),
            'is_active'       => true,
        ]);

        $criadas = GeradorDeVagas::correr((int)$consulta['id']);
        $this->flash('success', $criadas > 0
            ? 'Horário guardado — ' . $criadas . ' hora' . ($criadas === 1 ? '' : 's') . ' no calendário.'
            : 'Horário guardado.');

        return $this->redirect('/admin/consultas/' . (int)$consulta['id'] . '/edit#quando');
    }

    public function updateSchedule(Request $req, array $params): Response
    {
        $linha = $this->db->fetch(
            'SELECT * FROM consultation_schedules WHERE id = :id',
            ['id' => (int)$params['scheduleId']]
        );
        if (!$linha) {
            $this->notFound('Essa linha do horário não existe.');
        }

        ConsultationSchedule::update((int)$linha['id'], [
            'weekday'    => $req->string('weekday'),
            'start_time' => $req->string('start_time'),
            'capacity'   => $req->string('capacity'),
            'starts_on'  => $req->string('starts_on'),
            'ends_on'    => $req->string('ends_on'),
            'is_active'  => $req->string('is_active') !== '',
        ]);

        GeradorDeVagas::correr((int)$linha['consultation_id']);
        $this->flash('success', 'Horário guardado. As horas que já existiam ficam como estavam.');

        return $this->redirect('/admin/consultas/' . (int)$linha['consultation_id'] . '/edit#quando');
    }

    /**
     * Tirar uma linha do horário não tira as horas que ela já gerou — podem ter
     * gente marcada. O que se diz a seguir é quantas ficaram, e onde se mexe nelas.
     */
    public function destroySchedule(Request $req, array $params): Response
    {
        $linha = $this->db->fetch(
            'SELECT * FROM consultation_schedules WHERE id = :id',
            ['id' => (int)$params['scheduleId']]
        );
        if (!$linha) {
            $this->notFound('Essa linha do horário não existe.');
        }

        $orfas = ConsultationSchedule::futureSlots((int)$linha['id']);
        ConsultationSchedule::delete((int)$linha['id']);

        $this->flash('success', $orfas > 0
            ? 'Linha do horário eliminada. As ' . $orfas . ' horas que ela já tinha criado '
              . 'continuam no calendário — feche-as em Marcações se não as quiser.'
            : 'Linha do horário eliminada.');

        return $this->redirect('/admin/consultas/' . (int)$linha['consultation_id'] . '/edit#quando');
    }

    // ---------------------------------------------------------- encerramentos

    public function closures(Request $req): Response
    {
        /* Por mês, e dentro do mês por dia.

           Por dia porque um 25 de Dezembro fechado para três consultas são três
           linhas na tabela e **um** dia no calendário, e é o dia que a pessoa tem
           na cabeça quando vem aqui.

           Por mês porque uma Casa que encerra todas as segundas de Janeiro a Março
           tem trinta linhas, e trinta linhas seguidas não se lêem: com o mês por
           cima delas, procura-se o mês e não a linha. */
        $porMes  = [];
        $quantos = 0;

        foreach (ConsultationClosure::upcoming() as $f) {
            /* Pela próxima ocorrência e não pela data guardada: um Natal anual
               escrito em 2026 está a falar do Natal deste ano, e é nesse mês que
               quem lê o espera encontrar. */
            $dia = $f['proxima']->format('Y-m-d');
            $porMes[substr($dia, 0, 7)][$dia][] = $f;
            $quantos++;
        }

        return $this->render('admin/encerramentos/index', [
            'porMes'    => $porMes,
            'quantos'   => $quantos,
            'consultas' => Consultation::all(),
        ], 'layouts/admin');
    }

    /**
     * Encerrar um dia — para a Casa toda, ou só para algumas consultas.
     *
     * «Algumas» é uma linha por consulta e não uma lista numa coluna: assim
     * reabre-se uma sem reabrir as outras, e a chave estrangeira continua a apontar
     * para uma consulta só. O que o formulário faz é poupar a quem preenche a
     * repetição de escrever o mesmo dia três vezes.
     *
     * Fechar um dia **fecha** as horas que lá estiverem — não as apaga. A diferença
     * importa quando já há gente marcada: as marcações continuam a existir e alguém
     * tem de avisar essas pessoas. Um fecho que apagasse as horas apagava com elas
     * a lista de quem era preciso avisar, e por isso a mensagem diz quantas são.
     */
    public function storeClosure(Request $req): Response
    {
        $dia = trim($req->string('on_date'));
        $d   = \DateTimeImmutable::createFromFormat('!Y-m-d', $dia);
        if ($d === false || $d->format('Y-m-d') !== $dia) {
            $this->flash('error', 'Essa data não existe.');
            return $this->redirect('/admin/encerramentos');
        }

        $todosOsAnos = $req->string('every_year') !== '';

        /* Vazio, ou com o zero lá dentro, quer dizer a Casa toda — uma linha só, com
           a consulta a NULL. É diferente de marcar todas as consultas uma a uma:
           essa não fecharia uma consulta criada amanhã. */
        $pedidas = array_map('intval', (array)$req->input('consultation_ids', []));
        $escolhidas = array_values(array_filter($pedidas, static fn(int $id): bool => $id > 0));
        $casaToda   = $escolhidas === [] || in_array(0, $pedidas, true);

        $alvos    = $casaToda ? [0] : $escolhidas;
        $marcadas = 0;
        $nomes    = [];

        foreach ($alvos as $consultationId) {
            if ($consultationId > 0 && Consultation::find($consultationId) === null) {
                continue;
            }

            $id = ConsultationClosure::create([
                'consultation_id' => $consultationId,
                'on_date'         => $dia,
                'every_year'      => $todosOsAnos,
                'note'            => $req->string('note'),
            ]);

            /* As horas que já estão no calendário não se fecham sozinhas: o gerador
               só decide sobre as que ainda não existem. Um encerramento anual pode
               apanhar mais do que uma data dentro do horizonte, por isso fecham-se
               todas as ocorrências que lá estejam. */
            $marcadas += $this->fecharVagas(ConsultationClosure::find($id) ?? [], $consultationId);

            if ($consultationId > 0) {
                $c = Consultation::find($consultationId);
                $nomes[] = (string)($c['name'] ?? '');
            }
        }

        $oQue = $casaToda
            ? 'A Casa fecha'
            : implode(', ', $nomes) . (count($nomes) === 1 ? ' não acontece' : ' não acontecem');
        $quando = $todosOsAnos
            ? 'a ' . $d->format('d/m') . ', todos os anos'
            : 'a ' . $d->format('d/m/Y');

        $this->flash($marcadas > 0 ? 'error' : 'success', $marcadas > 0
            ? $oQue . ' ' . $quando . ' — mas há ' . $marcadas . ' marcação'
              . ($marcadas === 1 ? '' : 'ões') . ' para esse dia. As horas ficaram fechadas e as '
              . 'marcações ficaram de pé: avise essas pessoas em Marcações.'
            : $oQue . ' ' . $quando . '.');

        return $this->redirect('/admin/encerramentos');
    }

    /**
     * Fecha as horas que este encerramento apanha e diz quantas marcações ficam de
     * pé.
     *
     * O intervalo é o horizonte do gerador mais uma folga de um ano: além dele não
     * há horas para fechar, e as que forem geradas depois já nascem a saber deste
     * encerramento. A folga é pelos anuais, que podem cair fora do horizonte deste
     * ano e dentro do do próximo.
     *
     * @param array<string,mixed> $fecho
     */
    private function fecharVagas(array $fecho, int $consultationId): int
    {
        if ($fecho === []) {
            return 0;
        }

        $de  = new \DateTimeImmutable('today');
        $ate = $de->modify('+' . (GeradorDeVagas::HORIZONTE_DIAS + 366) . ' days');

        $marcadas = 0;
        foreach (ConsultationClosure::ocorrencias($fecho, $de, $ate) as $quando) {
            /* Contar **antes** de fechar. Depois de fechadas, as horas continuam a
               ter as marcações — fechar não desmarca ninguém —, mas contá-las aqui
               é o que faz a mensagem poder dizer a quem é preciso telefonar. */
            $marcadas += (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM bookings b
                   JOIN consultation_slots s ON s.id = b.slot_id
                  WHERE DATE(s.starts_at) = :dia AND b.status = 'confirmada'"
                  . ($consultationId > 0 ? ' AND s.consultation_id = :cid' : ''),
                ['dia' => $quando] + ($consultationId > 0 ? ['cid' => $consultationId] : [])
            );

            ConsultationSlot::closeDay($quando, $consultationId > 0 ? $consultationId : null);
        }

        return $marcadas;
    }

    /** Reabrir devolve as horas ao calendário, com o que já tinham. */
    public function destroyClosure(Request $req, array $params): Response
    {
        $fecho = ConsultationClosure::find((int)$params['closureId']);
        if (!$fecho) {
            $this->notFound('Esse encerramento não existe.');
        }

        $consultationId = $fecho['consultation_id'] !== null ? (int)$fecho['consultation_id'] : 0;

        /* As ocorrências lêem-se **antes** de apagar a linha: depois de apagada já
           não há de onde as tirar, e as horas ficavam fechadas para sempre — um dia
           que ninguém conseguiria reabrir sem ir à base de dados. */
        $de   = new \DateTimeImmutable('today');
        $ate  = $de->modify('+' . (GeradorDeVagas::HORIZONTE_DIAS + 366) . ' days');
        $dias = ConsultationClosure::ocorrencias($fecho, $de, $ate);

        ConsultationClosure::delete((int)$fecho['id']);

        foreach ($dias as $quando) {
            $this->db->query(
                'UPDATE consultation_slots SET is_open = 1 WHERE DATE(starts_at) = :dia'
                . ($consultationId > 0 ? ' AND consultation_id = :cid' : ''),
                ['dia' => $quando] + ($consultationId > 0 ? ['cid' => $consultationId] : [])
            );
        }

        GeradorDeVagas::correr($consultationId > 0 ? $consultationId : null);

        $this->flash('success', count($dias) > 1
            ? 'Reaberto — ' . count($dias) . ' dias voltaram ao calendário.'
            : 'Dia reaberto.');

        return $this->redirect('/admin/encerramentos');
    }

    /** @return array<string,mixed> */
    private function fields(Request $req): array
    {
        return [
            'name'        => $req->string('name'),
            'summary'     => $req->string('summary'),
            'body'        => $req->string('body'),
            'image'       => $req->string('image'),
            'image_alt'   => $req->string('image_alt'),
            'mode'        => $req->string('mode'),
            'is_bookable' => $req->string('is_bookable') !== '',

            /* A duração chega em duas persianas e o preço escrito em euros; os dois
               passam por FieldSpec::canonical, que é a mesma conversão que o
               formulário dos blocos usa. Escrever a conversão aqui outra vez era
               ter duas maneiras de ler «1h30» e «35,00». */
            'duration_min' => FieldSpec::canonical(
                ['input' => 'duration'],
                $req->input('duration_min', [])
            ),
            'price_cents' => FieldSpec::canonical(
                ['input' => 'money'],
                $req->string('price_cents')
            ),

            'capacity'     => $req->string('capacity'),
            'max_party'    => $req->string('max_party'),
            'notice_hours' => $req->string('notice_hours'),
            'is_published' => $req->string('is_published') !== '',
            'sort_order'   => $req->string('sort_order'),
        ];
    }
}
