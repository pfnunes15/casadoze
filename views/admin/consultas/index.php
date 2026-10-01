<?php
/**
 * As consultas que a Casa faz.
 *
 * Duas listas e não uma, porque são duas perguntas: **o que é que a agenda tem**
 * — as que se marcam escolhendo uma hora — e **o que é que se pede à Casa** — as
 * que começam com uma conversa, como os trabalhos espirituais. Numa lista só,
 * uma consulta sem horário parecia uma consulta a quem alguém se esqueceu de pôr
 * horas.
 *
 * Vem da lista de visitas do Barbeito, e o que lá separava as duas listas era
 * outra coisa: lá a divisão era «todas as semanas» contra «em datas certas», e
 * era deduzida de a visita ter ou não horário. Aqui é dita, numa coluna
 * (`is_bookable`), porque é uma decisão da Casa e não um acidente do horário: uma
 * leitura à qual ainda não se puseram horas não é um trabalho espiritual.
 *
 * Uma linha por consulta e não um cartão alto: o que se precisa de ver de relance
 * é o nome, quando acontece, e se alguém já marcou. O resto abre-se.
 *
 * @var array<int,array<string,mixed>> $comHora  as que se marcam por hora
 * @var array<int,array<string,mixed>> $semHora  as que começam com uma conversa
 * @var string $procura
 * @var int    $total
 */
$pageTitle = 'Consultas';

$curtos = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];

/**
 * Uma linha da lista. As duas listas desenham-se igual; só a coluna do «quando»
 * muda, e é por isso que ela entra por argumento já desenhada.
 */
$linha = static function (array $c, string $quando): void {
    $publicada = !empty($c['is_published']);
    $marcável  = !empty($c['is_bookable']);

    /* «Sem horas» só é um problema nas que se marcam por hora. Numa que começa
       com uma conversa, não ter vagas é o estado certo — e pintá-lo de vermelho
       era mandar quem gere procurar um problema que não existe. */
    $semVagas = $publicada && $marcável && (int)$c['vagas_futuras'] === 0;
    ?>
    <div class="cz-item<?= $publicada ? '' : ' is-off' ?>">
        <div class="cz-item__nome">
            <a href="/admin/consultas/<?= (int)$c['id'] ?>/edit"><?= e((string)$c['name']) ?></a>
            <?php if (!$publicada): ?>
                <span class="cz-selo is-cancelada">Fora do site</span>
            <?php endif; ?>
            <small>
                <?= e(App\Models\Consultation::linha($c)) ?>
                · <?= e(__('consultas.mode.' . (string)$c['mode'])) ?>
                <?php if ((int)$c['capacity'] > 1): ?>
                    · <?= (int)$c['capacity'] ?> lugares por hora
                <?php endif; ?>
            </small>
        </div>

        <div class="cz-item__quando"><?= $quando ?></div>

        <div class="cz-item__numeros">
            <?php if ($marcável): ?>
                <span<?= $semVagas ? ' class="is-bad"' : '' ?>>
                    <?= $semVagas
                        ? 'sem horas'
                        : (int)$c['vagas_futuras'] . ' hora' . ((int)$c['vagas_futuras'] === 1 ? '' : 's') ?>
                </span>
            <?php else: ?>
                <span>sem agenda</span>
            <?php endif; ?>
            <span>
                <?= (int)$c['marcacoes_futuras'] ?>
                marcação<?= (int)$c['marcacoes_futuras'] === 1 ? '' : 'ões' ?>
            </span>
        </div>

        <div class="cz-item__accoes">
            <a href="/admin/consultas/<?= (int)$c['id'] ?>/edit" class="btn-admin btn-admin--sm">Editar</a>

            <form class="inline-form" method="post" action="/admin/consultas/<?= (int)$c['id'] ?>/publicar">
                <?= csrf_field() ?>
                <button type="submit" class="btn-admin btn-admin--sm btn-admin--ghost">
                    <?= $publicada ? 'Esconder' : 'Publicar' ?>
                </button>
            </form>

            <?php /* Eliminar só aparece onde não há ninguém marcado. O controlador
                     recusa de qualquer maneira — é lá que a regra tem de estar —,
                     mas um botão que vai recusar é um botão a mais num ecrã já
                     cheio. */ ?>
            <?php if ((int)$c['marcacoes_futuras'] === 0): ?>
                <form class="inline-form" method="post" action="/admin/consultas/<?= (int)$c['id'] ?>"
                      data-confirm="Eliminar «<?= e((string)$c['name']) ?>», o seu horário e as suas horas?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Eliminar</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
};
?>

<div class="cz">

<header class="admin-header">
    <h1>Consultas</h1>
    <a href="/admin/consultas/nova" class="btn-admin btn-admin--primary">Nova consulta</a>
</header>

<?php if ($total > 0): ?>
    <div class="cz-barra">
        <div class="cz-barra__quando">
            <?= count($comHora) ?> com agenda,
            <?= count($semHora) ?> por conversa
            <?php if ($procura !== ''): ?>
                <small>a mostrar só o que tem «<?= e($procura) ?>»</small>
            <?php endif; ?>
        </div>

        <form method="get" action="/admin/consultas" class="cz-procura">
            <input type="search" name="q" value="<?= e($procura) ?>"
                   placeholder="Procurar pelo nome" aria-label="Procurar pelo nome">
            <button type="submit" class="btn-admin btn-admin--sm">Procurar</button>
            <?php if ($procura !== ''): ?>
                <a class="btn-admin btn-admin--sm btn-admin--ghost" href="/admin/consultas">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
<?php endif; ?>

<?php if ($total === 0): ?>
    <p class="cz-nada">
        Ainda não há consultas. Crie a primeira e diga a que horas acontece — ou
        diga que começa com uma conversa, e então não leva horas nenhumas.
    </p>
<?php elseif (!$comHora && !$semHora): ?>
    <p class="cz-nada">
        Nada com «<?= e($procura) ?>» no nome.
        <a href="/admin/consultas">Ver tudo</a>.
    </p>
<?php endif; ?>

<?php if ($comHora): ?>
    <section class="cz-seccao" style="margin-top:0">
        <h2>Com agenda</h2>
        <p>Marcam-se escolhendo uma hora. O horário enche o calendário sozinho.</p>

        <div class="cz-itens">
            <?php foreach ($comHora as $c): ?>
                <?php
                /* O horário em fichas, dentro da linha: «Ter 21:00 · Qui 21:00».
                   É a resposta a «quando é que ela é», e sem isto era preciso
                   abrir o ecrã de edição para a ver. */
                ob_start(); ?>
                <div class="cz-fichas">
                    <?php foreach ($c['horario'] as $h): ?>
                        <span class="cz-ficha<?= empty($h['is_active']) ? ' is-off' : '' ?>">
                            <?= e($curtos[(int)$h['weekday']] ?? '?') ?>
                            <?= e(substr((string)$h['start_time'], 0, 5)) ?>
                        </span>
                    <?php endforeach; ?>

                    <?php if ($c['horario'] === []): ?>
                        <span class="cz-ficha is-off">sem horário</span>
                    <?php endif; ?>

                    <?php if ($c['horas_avulsas']): ?>
                        <?php /* As horas abertas à mão. Contadas e não listadas: são
                                 as excepções, e a lista delas está no ecrã da
                                 consulta. */ ?>
                        <span class="cz-ficha">
                            +<?= count($c['horas_avulsas']) ?>
                            hora<?= count($c['horas_avulsas']) === 1 ? '' : 's' ?> à mão
                        </span>
                    <?php endif; ?>
                </div>
                <?php $linha($c, (string)ob_get_clean()); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($semHora): ?>
    <section class="cz-seccao">
        <h2>Por conversa</h2>
        <p>
            Não têm horas. No site, o cartão destas mostra «Falar com o Zé» em vez
            de uma agenda — e o endereço desse botão escreve-se em
            <a href="/admin/settings">Definições &rsaquo; Consultas</a>.
        </p>

        <div class="cz-itens">
            <?php foreach ($semHora as $c): ?>
                <?php $linha($c, '<span class="cz-item__data is-passado">começa com uma conversa</span>'); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

</div>
