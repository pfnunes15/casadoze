<?php
/**
 * Uma consulta, e quando é que acontece.
 *
 * As duas coisas num ecrã só, e não em dois: quem escreve «Leitura completa» está
 * a pensar em quando é que ela é, e mandá-lo a outro sítio para o dizer é fazê-lo
 * perder a linha de pensamento.
 *
 * **Uma consulta com agenda e uma consulta por conversa são a mesma coisa com uma
 * caixa diferente ticada.** Não há dois tipos a escolher no princípio, que
 * obrigariam a decidir o que acontece quando um muda: há uma caixa — «marca-se
 * escolhendo uma hora» — e tudo o que depende dela esconde-se quando ela está
 * desticada.
 *
 * Com agenda, há duas maneiras de dizer quando, e usa-se uma, a outra, ou as duas:
 *
 *   Todas as semanas   um horário — «quartas às 21h» — que enche o calendário até
 *                      três meses para a frente, sozinho.
 *   Horas à mão        as horas escritas uma a uma. É a hora extra de quem
 *                      telefonou para um dia que o horário não tem.
 *
 * As duas só aparecem depois de a consulta existir — uma hora precisa de uma
 * consulta a que se pendurar, e pedi-la antes seria pedir uma coisa que ainda não
 * se pode guardar.
 *
 * @var array|null $consulta
 * @var array      $horario
 * @var array      $horas    as horas abertas à mão, daqui para a frente
 * @var bool       $novaConsulta
 */
use Admedia\Cms\FieldSpec;
use App\Models\ConsultationSchedule;

$pageTitle = $novaConsulta ? 'Nova consulta' : (string)$consulta['name'];
$v = static fn(string $campo, $default = '') => $consulta[$campo] ?? $default;
?>

<div class="cz">

<header class="admin-header">
    <h1><?= $novaConsulta ? 'Nova consulta' : e((string)$consulta['name']) ?></h1>
    <a href="/admin/consultas" class="btn-admin btn-admin--ghost">Voltar</a>
</header>

<form method="post" action="<?= $novaConsulta ? '/admin/consultas' : '/admin/consultas/' . (int)$consulta['id'] ?>">
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Nome da consulta</label>
        <input id="name" name="name" type="text" required maxlength="190"
               value="<?= e((string)old('name', $v('name'))) ?>" placeholder="Tarot · Leitura completa">
    </div>

    <div class="field">
        <label for="body">O que é</label>
        <textarea id="body" name="body" rows="4"><?= e((string)old('body', $v('body'))) ?></textarea>
    </div>

    <div class="field">
        <label for="image">Fotografia</label>
        <input id="image" name="image" type="text" maxlength="255"
               value="<?= e((string)old('image', $v('image'))) ?>" placeholder="/assets/img/consultas/leitura.webp">
    </div>

    <div class="field">
        <label for="image_alt">Descrição da fotografia</label>
        <input id="image_alt" name="image_alt" type="text" maxlength="255"
               value="<?= e((string)old('image_alt', $v('image_alt'))) ?>">
    </div>

    <?php /* **Não há campo de morada.** O Barbeito tem um — lá uma visita pode ser
             na adega ou no canteiro, e são sítios diferentes —, e aqui a Casa é uma só: a morada está nas
             Definições, a mesma do rodapé. Uma cópia por consulta deixava duas
             verdades sobre o mesmo sítio, e no dia em que a Casa mudasse uma
             ficava velha e ninguém saberia qual.

             O que varia por consulta é se ela é presencial, online, ou as duas —
             e isso é o campo a seguir. */ ?>

    <div class="field">
        <label for="summary">Uma linha, para o cartão</label>
        <p class="field-help">
            O que a consulta é, em meia dúzia de palavras. É o que se lê no cartão
            da página, debaixo do nome, antes de alguém decidir abrir o texto todo.
        </p>
        <input id="summary" name="summary" type="text" maxlength="255"
               value="<?= e((string)old('summary', $v('summary'))) ?>"
               placeholder="Passado, presente e caminho, para uma pergunta concreta">
    </div>

    <div class="field">
        <label for="mode">Onde acontece</label>
        <p class="field-help">
            «As duas» faz o site perguntar a quem marca, e é o que decide se o
            e-mail de confirmação leva a morada ou a promessa da ligação.
        </p>
        <select id="mode" name="mode">
            <?php foreach (['ambos' => 'As duas — quem marca escolhe',
                            'presencial' => 'Só presencial',
                            'online' => 'Só online'] as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>"
                        <?= (string)old('mode', $v('mode', 'ambos')) === $valor ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php /* A caixa que divide o ecrã em dois.

             Ticada, a consulta marca-se escolhendo uma hora e tudo o que está
             debaixo disto faz sentido — a duração, a lotação, a antecedência, o
             horário. Desticada, é um trabalho que começa com uma conversa: não tem
             horas, e o cartão do site mostra «Falar com o Zé».

             Está aqui em cima e não no fim porque é a pergunta que decide se o
             resto do formulário importa. */ ?>
    <div class="field">
        <label class="field-check">
            <input type="checkbox" name="is_bookable" value="1"
                   <?= !empty(old('is_bookable', $v('is_bookable', 1))) ? 'checked' : '' ?>>
            Marca-se escolhendo uma hora
        </label>
        <p class="field-help">
            Desligue nas consultas que começam com uma conversa — os trabalhos
            espirituais. Essas não têm horário nem horas, e o site mostra-lhes um
            botão de contacto em vez da agenda. Desligar <strong>não</strong> apaga
            as horas que já existam: deixa de se gerar mais, e as que lá estiverem
            fecham-se em Marcações.
        </p>
    </div>

    <?php /* A duração e o preço são duas caixas e não uma linha escrita à mão.
             Os widgets são os do CMS — as mesmas persianas e a mesma caixa de
             euros que os blocos usam —, e é o FieldSpec que converte o que elas
             mandam para minutos e cêntimos. A linha que o site mostra
             («30 min · 35,00 €») é feita à saída, em Consultation::linha. */ ?>
    <?php admin_field(
        FieldSpec::normalise('duration_min', [
            'type'  => 'duration',
            'label' => 'Duração',
            'help'  => 'Quanto tempo dura a consulta. Conta para a hora a que ela acaba.',
            'hours' => 6,
        ], 'consulta'),
        'duration_min',
        (int)old('duration_min', $v('duration_min', 30))
    ); ?>

    <?php admin_field(
        FieldSpec::normalise('price_cents', [
            'type'  => 'money',
            'label' => 'Preço por pessoa',
            'help'  => 'Deixe vazio se não quiser mostrar preço.',
            'placeholder' => '25,00',
        ], 'consulta'),
        'price_cents',
        (string)old('price_cents', $v('price_cents', ''))
    ); ?>

    <div class="field-row">
        <div class="field">
            <label for="capacity">Lugares por hora</label>
            <input id="capacity" name="capacity" type="number" min="1" max="999"
                   value="<?= e((string)old('capacity', $v('capacity', 1))) ?>">
            <p class="field-help">
                Um, numa leitura: é uma pessoa de cada vez. A coluna existe à mesma
                porque um círculo ou um workshop é a mesma mesa com mais cadeiras —
                e isso muda-se aqui, sem tocar no código.
            </p>
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="max_party">Máximo por marcação</label>
            <input id="max_party" name="max_party" type="number" min="1" max="999"
                   value="<?= e((string)old('max_party', $v('max_party', 1))) ?>">
            <p class="field-help">
                Quantos lugares uma marcação pode levar de uma vez. Acima disto
                fala-se com a Casa. Nunca passa dos lugares por hora.
            </p>
        </div>
        <div class="field">
            <label for="notice_hours">Antecedência mínima (horas)</label>
            <input id="notice_hours" name="notice_hours" type="number" min="0" max="720"
                   value="<?= e((string)old('notice_hours', $v('notice_hours', 24))) ?>">
            <p class="field-help">Duas horas antes não dá para pôr mais um lugar à mesa.</p>
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="sort_order">Ordem</label>
            <input id="sort_order" name="sort_order" type="number"
                   value="<?= e((string)old('sort_order', $v('sort_order', 0))) ?>">
        </div>
        <?php if (!$novaConsulta): ?>
            <div class="field">
                <label for="slug">Endereço</label>
                <input id="slug" name="slug" type="text" maxlength="160"
                       value="<?= e((string)old('slug', $v('slug'))) ?>">
            </div>
        <?php endif; ?>
    </div>

    <div class="field-checkbox">
        <label>
            <input type="checkbox" name="is_published" value="1"
                   <?= !empty(old('is_published', $v('is_published', 1))) ? 'checked' : '' ?>>
            Mostrar no site
        </label>
    </div>

    <?php if ($novaConsulta): ?>
        <?php /* O quando pergunta-se no mesmo fôlego em que se escreve o nome:
                 quem está a criar «Leitura completa às quartas» já sabe que é às
                 quartas. Sem isto, a consulta nascia sem hora nenhuma e
                 ficava à espera de uma segunda ida ao backoffice que muita gente
                 não faz — e uma consulta publicada sem horas é o caso em que o
                 site a mostra e não deixa marcar nada.

                 Depois de existir, isto é substituído pelo ecrã completo lá
                 em baixo, com o horário linha a linha e as datas certas. */ ?>
        <h2 class="cz-sub" style="margin-top:2.5rem">Quando é que acontece</h2>
        <p class="cz-sub__nota">
            Marque os dias em que se repete. Não marque nenhum se for um evento
            de datas certas — a seguir a gravar escreve-as uma a uma.
        </p>

        <div class="cz-forma cz-forma--larga">
            <label>Começa a
                <input type="date" name="starts_on"
                       value="<?= e((string)old('starts_on', date('Y-m-d'))) ?>">
            </label>
            <label>Acaba a
                <input type="date" name="ends_on" value="<?= e((string)old('ends_on')) ?>">
            </label>

            <?php /* Vazio é o caso normal e não uma omissão: a maior parte das
                     consultas de uma casa não tem fim marcado. Dizê-lo aqui poupa
                     a quem preenche a dúvida de inventar uma data longínqua. */ ?>
            <p class="cz-forma__nota">
                <strong>Sem data de fim, continua sempre.</strong> Termina no dia
                em que alguém a desactivar ou lhe puser uma data aqui.
            </p>

            <fieldset class="cz-quais">
                <legend>Dias da semana</legend>
                <?php $marcados = (array)old('weekdays', []); ?>
                <?php foreach (ConsultationSchedule::DIAS as $n => $nome): ?>
                    <label>
                        <input type="checkbox" name="weekdays[]" value="<?= $n ?>"
                               <?= in_array((string)$n, array_map('strval', $marcados), true) ? 'checked' : '' ?>>
                        <?= e($nome) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <label>Hora
                <input type="time" name="start_time" value="<?= e((string)old('start_time', '10:00')) ?>">
            </label>
        </div>
    <?php endif; ?>

    <button type="submit" class="btn-admin btn-admin--primary">
        <?= $novaConsulta ? 'Criar' : 'Guardar' ?>
    </button>
</form>

<?php if (!$novaConsulta): ?>
    <section id="quando" class="cz-seccao">
        <h2>Quando é que acontece</h2>
        <p>
            Duas maneiras de o dizer, e usa-se uma, a outra, ou as duas. Uma
            <strong>consulta regular</strong> tem horário; um <strong>evento
            pontual</strong> tem só datas.
        </p>

        <?php
        /* O aviso que interessa: publicada e sem data nenhuma, a consulta aparece
           no site e não deixa marcar nada. É o caso que ninguém repara e toda
           a gente sente. */
        $semQuando = !$horario && !$horas;
        ?>
        <?php if ($semQuando): ?>
            <p class="cz-nada">
                <strong>Esta consulta ainda não acontece em dia nenhum.</strong>
                Dê-lhe um horário semanal, ou escreva as datas em que acontece —
                sem uma das duas coisas não tem vagas, e ninguém a consegue
                marcar no site.
            </p>
        <?php endif; ?>

        <h3 class="cz-sub">Todas as semanas</h3>
        <p class="cz-sub__nota">
            Uma linha por dia da semana e hora, cada uma com o período em que
            vale. <strong>Sem data em «Até», a linha continua sempre</strong> —
            termina no dia em que alguém a desactivar ou lhe puser uma data. O
            calendário enche-se a partir daqui, até três meses para a frente;
            guardar volta a enchê-lo, e o que já lá está — com as marcações que
            tiver — fica como está.
        </p>

        <?php /* Uma lista de formulários e não uma tabela: um <form> não pode
                 ser filho de um <tr>, e o browser tira-o de lá — o que dava era
                 uma tabela bonita com botões que não guardavam nada.

                 Os campos em fila e não empilhados: são cinco coisas curtas —
                 terça, 10h, 12 — e empilhadas gastavam um ecrã por linha. */ ?>
        <?php if ($horario): ?>
            <div class="cz-linhas">
            <?php foreach ($horario as $h): ?>
                <div class="cz-linha<?= empty($h['is_active']) ? ' is-off' : '' ?>">
                    <form method="post" action="/admin/horario/<?= (int)$h['id'] ?>" class="cz-forma">
                        <?= csrf_field() ?>
                        <label>Dia
                            <select name="weekday">
                                <?php foreach (ConsultationSchedule::DIAS as $n => $nome): ?>
                                    <option value="<?= $n ?>" <?= (int)$h['weekday'] === $n ? 'selected' : '' ?>><?= e($nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Hora
                            <input type="time" name="start_time" value="<?= e(substr((string)$h['start_time'], 0, 5)) ?>">
                        </label>
                        <label>Lotação
                            <input type="number" name="capacity" min="1" max="999"
                                   placeholder="<?= (int)$consulta['capacity'] ?>"
                                   value="<?= $h['capacity'] === null ? '' : (int)$h['capacity'] ?>">
                        </label>
                        <label>De
                            <input type="date" name="starts_on" value="<?= e((string)($h['starts_on'] ?? '')) ?>">
                        </label>
                        <label>Até
                            <input type="date" name="ends_on" value="<?= e((string)($h['ends_on'] ?? '')) ?>">
                        </label>
                        <label class="cz-forma__check">
                            <input type="checkbox" name="is_active" value="1" <?= !empty($h['is_active']) ? 'checked' : '' ?>>
                            Activa
                        </label>
                        <span class="cz-linhas__fim">
                            <button type="submit" class="btn-admin btn-admin--sm">Guardar</button>
                        </span>
                    </form>
                    <form method="post" action="/admin/horario/<?= (int)$h['id'] ?>"
                          data-confirm="Eliminar esta linha do horário? As vagas que ela já criou ficam.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Eliminar</button>
                    </form>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/consultas/<?= (int)$consulta['id'] ?>/horario" class="cz-forma">
            <?= csrf_field() ?>
            <label>Dia
                <select name="weekday">
                    <?php foreach (ConsultationSchedule::DIAS as $n => $nome): ?>
                        <option value="<?= $n ?>"><?= e($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Hora
                <input type="time" name="start_time" value="10:00" required>
            </label>
            <label>Lotação
                <input type="number" name="capacity" min="1" max="999"
                       placeholder="<?= (int)$consulta['capacity'] ?>">
            </label>
            <button type="submit" class="btn-admin">Acrescentar ao horário</button>
        </form>

        <h3 class="cz-sub">Horas à mão</h3>
        <p class="cz-sub__nota">
            A hora extra: quem telefonou para um dia que o horário não tem, ou um
            domingo em que a Casa abriu uma vez. Nasce igual às outras e vive igual
            às outras — o que a distingue é só o gerador saber que não é dele. Só se
            mostram as horas de hoje em diante.
        </p>

        <?php if ($horas): ?>
            <?php $diasCurtos = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom']; ?>
            <div class="cz-linhas">
                <?php foreach ($horas as $d): ?>
                    <?php
                    $quando = new DateTimeImmutable((string)$d['starts_at']);
                    $cheia  = (int)$d['seats_taken'] >= (int)$d['capacity'];
                    ?>
                    <div class="cz-data<?= empty($d['is_open']) ? ' is-off' : '' ?>">
                        <span class="cz-data__quando">
                            <strong><?= e($quando->format('d/m/Y')) ?></strong>
                            <?= e($diasCurtos[(int)$quando->format('N')]) ?>
                            · <?= e($quando->format('H:i')) ?>
                        </span>
                        <span class="cz-data__conta<?= $cheia ? ' is-bad' : '' ?>">
                            <?= (int)$d['seats_taken'] ?>/<?= (int)$d['capacity'] ?> lugares
                        </span>
                        <?php if (trim((string)($d['note'] ?? '')) !== ''): ?>
                            <span class="cz-data__nota"><?= e((string)$d['note']) ?></span>
                        <?php endif; ?>
                        <?php if (empty($d['is_open'])): ?>
                            <span class="cz-selo is-cancelada">Fechada</span>
                        <?php endif; ?>
                        <?php /* Mudar a lotação, fechar, apagar e ver quem vem é tudo no
                                 ecrã da data — aqui repetido por linha, era outra vez a
                                 parede de campos que se desmontou. */ ?>
                        <a class="btn-admin btn-admin--sm btn-admin--ghost"
                           href="/admin/vagas/<?= (int)$d['id'] ?>">Abrir</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/vagas" class="cz-forma cz-forma--larga">
            <?= csrf_field() ?>
            <input type="hidden" name="consultation_id" value="<?= (int)$consulta['id'] ?>">
            <label>Dia
                <input name="on_date" type="date" required min="<?= e(date('Y-m-d')) ?>">
            </label>
            <label>Hora
                <input name="start_time" type="time" required value="10:00">
            </label>
            <label>Lotação
                <input name="capacity" type="number" min="1" max="999" placeholder="<?= (int)$consulta['capacity'] ?>">
            </label>
            <label>Nota
                <input name="note" type="text" maxlength="190" placeholder="Grupo da agência X">
            </label>
            <button type="submit" class="btn-admin">Acrescentar a data</button>
        </form>
    </section>
<?php endif; ?>

</div>
