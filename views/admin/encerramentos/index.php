<?php
/**
 * Os dias em que não há.
 *
 * Sem consulta escolhida encerra a casa toda, que é o caso do Natal e das férias
 * e é o que se quer escrever uma vez e não uma vez por consulta. Escolhendo
 * algumas, é uma linha por consulta — assim reabre-se uma sem reabrir as outras.
 *
 * «A casa toda» não é o mesmo que marcar todas as consultas uma a uma: a primeira
 * encerra também as consultas que forem criadas amanhã, e a segunda não.
 *
 * **Um encerramento pode repetir-se todos os anos.** O Natal não é uma data, é
 * 25 de Dezembro sempre; escrito como data, no dia 26 deixava de existir e
 * alguém tinha de se lembrar de o voltar a escrever em Novembro — que é a
 * espécie de coisa de que ninguém se lembra, e o resultado é a Casa a
 * aceitar marcações para o dia de Natal.
 *
 * Encerrar um dia **fecha** as vagas que lá estiverem — não as apaga. A
 * diferença importa quando já há gente marcada: as marcações continuam a existir
 * e alguém tem de avisar essas pessoas; um encerramento que apagasse as vagas
 * apagava com elas a lista de quem era preciso avisar.
 *
 * Por mês, e dentro do mês por dia. Uma casa que encerra todas as segundas de
 * Janeiro a Março tem trinta linhas, e trinta linhas seguidas não se lêem: com
 * o mês por cima delas, procura-se o mês e não a linha.
 *
 * @var array<string,array<string,array<int,array<string,mixed>>>> $porMes
 * @var int   $quantos
 * @var array $consultas
 */
$pageTitle = 'Dias encerrados';
$dias = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];
?>

<div class="cz">

<header class="admin-header">
    <h1>Dias encerrados</h1>
</header>

<section class="cz-seccao" style="margin-top:0">
    <h2>Encerrar um dia</h2>
    <p>
        Deixe «a Casa toda» para o Natal e para as férias. Escolha consultas quando só
        essas não acontecem — o resto do dia continua a poder marcar-se.
        Marque «todos os anos» para os feriados: o ano que escrever passa a
        querer dizer <em>a partir de quando</em>.
    </p>

    <form method="post" action="/admin/encerramentos" class="cz-forma cz-forma--larga">
        <?= csrf_field() ?>

        <label>Dia
            <input name="on_date" type="date" required min="<?= e(date('Y-m-d')) ?>">
        </label>

        <label>Porquê
            <input name="note" type="text" maxlength="190" placeholder="Vindima">
        </label>

        <?php /* O ano que se escreve em cima passa a querer dizer «desde
                 quando», e o mês e o dia é que mandam. */ ?>
        <label class="cz-forma__check">
            <input type="checkbox" name="every_year" value="1">
            Repete-se todos os anos
        </label>

        <?php /* Caixas e não uma persiana: a pergunta é «quais», e uma persiana
                 só sabe responder «uma». A da casa toda vem marcada, que é o
                 caso de longe mais comum. */ ?>
        <fieldset class="cz-quais">
            <legend>O que encerra</legend>

            <label class="cz-quais__todas">
                <input type="checkbox" name="consultation_ids[]" value="0" checked
                       data-cz-todas>
                A casa toda
            </label>

            <?php foreach ($consultas as $v): ?>
                <label>
                    <input type="checkbox" name="consultation_ids[]" value="<?= (int)$v['id'] ?>" data-cz-uma>
                    <?= e((string)$v['name']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <button type="submit" class="btn-admin btn-admin--primary">Encerrar</button>
    </form>
</section>

<section class="cz-seccao">
    <h2>Marcados<?= $quantos > 0 ? ' <span class="cz-conta">' . $quantos . '</span>' : '' ?></h2>

    <?php if (!$porMes): ?>
        <p class="cz-nada">Não há dias encerrados daqui para a frente.</p>
    <?php else: ?>
        <?php
        $meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                  7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
        ?>
        <?php foreach ($porMes as $mes => $porDia): ?>
            <?php $m = new DateTimeImmutable($mes . '-01'); ?>
            <h3 class="cz-sub"><?= e($meses[(int)$m->format('n')]) ?> de <?= e($m->format('Y')) ?></h3>

            <div class="cz-itens">
                <?php foreach ($porDia as $dia => $fechos): ?>
                    <?php
                    $d = new DateTimeImmutable($dia);
                    $casaToda = false;
                    foreach ($fechos as $f) { if ($f['consultation_id'] === null) { $casaToda = true; } }

                    /* O porquê escreve-se uma vez por dia, mesmo quando são
                       três linhas: é quase sempre o mesmo motivo. */
                    $porques = [];
                    foreach ($fechos as $f) {
                        $n = trim((string)$f['note']);
                        if ($n !== '' && !in_array($n, $porques, true)) { $porques[] = $n; }
                    }
                    ?>
                    <?php
                    /* Anual quando qualquer das linhas do dia o for. Na prática
                       são todas ou nenhuma — escrevem-se juntas —, mas o dia é
                       a soma delas e não pode dizer menos do que elas dizem. */
                    $anual = false;
                    foreach ($fechos as $f) { if (!empty($f['every_year'])) { $anual = true; } }
                    ?>
                    <div class="cz-item cz-item--fecho">
                        <div class="cz-item__nome">
                            <strong><?= e($d->format('d/m')) ?></strong>
                            <?= e($dias[(int)$d->format('N')]) ?>
                            <?php if ($anual): ?>
                                <span class="cz-selo cz-selo--anual">todos os anos</span>
                            <?php endif; ?>
                            <?php if ($porques): ?><small><?= e(implode(' · ', $porques)) ?></small><?php endif; ?>
                        </div>

                        <?php /* Uma ficha por consulta, cada uma com o seu × — encerrou-se
                                 em conjunto, mas reabre-se uma de cada vez. */ ?>
                        <div class="cz-fichas">
                            <?php if ($casaToda): ?>
                                <?php foreach ($fechos as $f): if ($f['consultation_id'] !== null) { continue; } ?>
                                    <span class="cz-ficha cz-ficha--tudo">
                                        A casa toda
                                        <form class="inline-form" method="post"
                                              action="/admin/encerramentos/<?= (int)$f['id'] ?>"
                                              data-confirm="<?= $anual
                                                  ? 'Deixar de encerrar a casa a ' . e($d->format('d/m')) . ' todos os anos?'
                                                  : 'Reabrir ' . e($d->format('d/m/Y')) . '? As vagas voltam ao calendário com o que já tinham.' ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="cz-ficha__x" aria-label="Reabrir">×</button>
                                        </form>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($fechos as $f): ?>
                                    <span class="cz-ficha">
                                        <?= e((string)$f['consultation_name']) ?>
                                        <form class="inline-form" method="post"
                                              action="/admin/encerramentos/<?= (int)$f['id'] ?>"
                                              data-confirm="<?= $anual
                                                  ? 'Deixar de encerrar «' . e((string)$f['consultation_name']) . '» a ' . e($d->format('d/m')) . ' todos os anos?'
                                                  : 'Reabrir «' . e((string)$f['consultation_name']) . '» a ' . e($d->format('d/m/Y')) . '?' ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="cz-ficha__x" aria-label="Reabrir">×</button>
                                        </form>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

</div>

<script>
/* «A casa toda» e «esta consulta» excluem-se: marcar uma desmarca a outra. Sem
   isto, marcar as duas guardava uma linha a mais que não fazia nada — e o
   servidor trata na mesma o caso, porque um formulário pode chegar de qualquer
   lado. Ver VisitAdminController::storeClosure. */
(function () {
    var todas = document.querySelector('[data-cz-todas]');
    var umas  = document.querySelectorAll('[data-cz-uma]');
    if (!todas) { return; }

    todas.addEventListener('change', function () {
        if (todas.checked) { umas.forEach(function (c) { c.checked = false; }); }
    });
    umas.forEach(function (c) {
        c.addEventListener('change', function () {
            if (c.checked) { todas.checked = false; }
        });
    });
})();
</script>
