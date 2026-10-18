<?php
/**
 * Onde há lugar: a semana, ou o mês.
 *
 * É a primeira das duas vistas das Marcações, e a que serve para marcar. Duas
 * escalas da mesma grelha, e as duas existem por razões diferentes.
 *
 * A **semana** é para quem gere. Cabem os cartões com a barra de ocupação, e
 * vê-se de relance como está a encher.
 *
 * O **mês** é para quem atende o telefone. Alguém liga a perguntar «têm lugar
 * para quatro em Outubro?», e essa resposta não se dá a folhear cinco semanas
 * uma a uma. Por isso o mês diz **lugares livres** e não lugares tomados — é o
 * número da pergunta que está a ser feita — e há uma caixa onde se escreve o
 * tamanho do grupo, que apaga tudo o que não chega para ele.
 *
 * Clicar numa vaga abre o ecrã dela, que é onde se marca e onde se mexe na
 * lotação. Aqui só se olha.
 *
 * @var bool               $mensal
 * @var array<string,array{data:\DateTimeImmutable,doMes:bool,vagas:array}> $dias
 * @var \DateTimeImmutable $pedido
 * @var \DateTimeImmutable $de
 * @var \DateTimeImmutable $ate
 * @var \DateTimeImmutable $anterior
 * @var \DateTimeImmutable $seguinte
 * @var array              $consultas
 * @var int                $consultaId
 * @var int                $grupo
 * @var array{vagas:int,lugares:int,tomados:int} $resumo
 * @var \DateTimeImmutable|null $horizonte  até quando o calendário está cheio
 */
$pageTitle = 'Marcações';

$curtos = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
$meses  = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
           7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
$hoje   = (new DateTimeImmutable('today'))->format('Y-m-d');

/* O que a barra de navegação leva consigo. Mudar de semana não pode perder a
   consulta filtrada nem o tamanho do grupo — quem está ao telefone estava a meio
   de uma pergunta. */
$leva = static function (DateTimeImmutable $d) use ($mensal, $consultaId, $grupo): string {
    return '/admin/marcacoes?de=' . $d->format('Y-m-d')
         . ($mensal ? '&vista=mes' : '')
         . ($consultaId > 0 ? '&consulta=' . $consultaId : '')
         . ($grupo > 0 ? '&pessoas=' . $grupo : '');
};

if ($mensal) {
    $titulo = $meses[(int)$pedido->format('n')] . ' de ' . $pedido->format('Y');
} else {
    $fim    = $de->modify('+6 days');
    $titulo = $de->format('j') . ($de->format('n') === $fim->format('n') ? '' : ' de ' . $meses[(int)$de->format('n')])
            . ' – ' . $fim->format('j') . ' de ' . $meses[(int)$fim->format('n')] . ' de ' . $fim->format('Y');
}

$livres = max(0, $resumo['lugares'] - $resumo['tomados']);

/**
 * O estado de uma vaga: livre, a encher, cheia, fechada — e «não chega» quando
 * há um grupo escrito e ela não o leva.
 *
 * Três estados e não uma escala: quem olha para a grelha quer saber onde é que
 * ainda cabe gente, e uma percentagem exacta não responde a isso mais depressa.
 */
$estadoDe = static function (array $s) use ($grupo): array {
    $cap    = max(1, (int)$s['capacity']);
    $tomado = (int)$s['seats_taken'];
    $vagos  = max(0, $cap - $tomado);
    $aberta = !empty($s['is_open']);
    $pct    = min(100, (int)round($tomado / $cap * 100));

    $classe = !$aberta ? 'is-fechada' : ($vagos === 0 ? 'is-cheia' : ($pct >= 70 ? 'is-quase' : ''));
    if ($aberta && $grupo > 0 && $vagos < $grupo) {
        $classe .= ' is-curta';
    }

    return ['classe' => trim($classe), 'vagos' => $vagos, 'tomado' => $tomado, 'cap' => $cap, 'pct' => $pct, 'aberta' => $aberta];
};
?>

<div class="cz">

<?php partial('admin/marcacoes/topo', ['vista' => 'calendario', 'porVir' => $porVir]); ?>

<div class="cz-barra">
    <div class="cz-barra__quando">
        <?= e($titulo) ?>
        <small>
            <?= $resumo['vagas'] ?> vaga<?= $resumo['vagas'] === 1 ? '' : 's' ?>
            <?= $mensal ? 'neste mês' : 'nesta semana' ?><?php if ($grupo > 0): ?>,
                a apagar o que não leva <?= $grupo ?> pessoa<?= $grupo === 1 ? '' : 's' ?><?php endif; ?>
        </small>
    </div>

    <?php /* Semana e mês são duas maneiras de olhar para o mesmo, e por isso são
             um par de ligações e não uma persiana escondida num canto. */ ?>
    <div class="cz-vistas">
        <a class="<?= $mensal ? '' : 'is-on' ?>"
           href="/admin/marcacoes?de=<?= e($pedido->format('Y-m-d')) ?><?= $consultaId > 0 ? '&amp;consulta=' . $consultaId : '' ?><?= $grupo > 0 ? '&amp;pessoas=' . $grupo : '' ?>">Semana</a>
        <a class="<?= $mensal ? 'is-on' : '' ?>"
           href="/admin/marcacoes?de=<?= e($pedido->format('Y-m-d')) ?>&amp;vista=mes<?= $consultaId > 0 ? '&amp;consulta=' . $consultaId : '' ?><?= $grupo > 0 ? '&amp;pessoas=' . $grupo : '' ?>">Mês</a>
    </div>

    <a class="btn-admin btn-admin--sm btn-admin--ghost" href="<?= e($leva($anterior)) ?>">←</a>
    <a class="btn-admin btn-admin--sm btn-admin--ghost" href="<?= e($leva(new DateTimeImmutable('today'))) ?>">Hoje</a>
    <a class="btn-admin btn-admin--sm btn-admin--ghost" href="<?= e($leva($seguinte)) ?>">→</a>

    <form method="get" action="/admin/marcacoes" class="cz-procura">
        <input type="hidden" name="de" value="<?= e($pedido->format('Y-m-d')) ?>">
        <?php if ($mensal): ?><input type="hidden" name="vista" value="mes"><?php endif; ?>

        <select name="consulta" aria-label="Filtrar por consulta">
            <option value="0">Todas as consultas</option>
            <?php foreach ($consultas as $v): ?>
                <option value="<?= (int)$v['id'] ?>" <?= $consultaId === (int)$v['id'] ? 'selected' : '' ?>>
                    <?= e((string)$v['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="cz-procura__grupo">
            Lugar para
            <input type="number" name="pessoas" min="0" max="99" inputmode="numeric"
                   value="<?= $grupo > 0 ? $grupo : '' ?>" placeholder="—">
        </label>

        <button type="submit" class="btn-admin btn-admin--sm">Ver</button>
    </form>
</div>

<?php if ($resumo['lugares'] > 0): ?>
    <div class="cz-resumo">
        <div><strong><?= $livres ?></strong><span>Lugares por vender</span></div>
        <div><strong><?= $resumo['tomados'] ?></strong><span>Marcaçãodos</span></div>
        <div><strong><?= (int)round($resumo['tomados'] / $resumo['lugares'] * 100) ?>%</strong><span>Ocupação</span></div>
    </div>
<?php endif; ?>

<?php if ($mensal): ?>

    <?php /* No mês, os nomes dos dias ficam numa fila acima da grelha e não em
             cada célula: são sempre os mesmos sete, e repeti-los trinta vezes
             gasta a linha onde as horas se lêem. */ ?>
    <div class="cz-mes-rola">
    <div class="cz-mes__dias" aria-hidden="true">
        <?php foreach ($curtos as $nome): ?><span><?= e($nome) ?></span><?php endforeach; ?>
    </div>

    <div class="cz-mes">
        <?php foreach ($dias as $dia => $d): ?>
            <?php
            $classes = 'cz-mdia';
            if (!$d['doMes'])      { $classes .= ' is-fora'; }
            if ($dia === $hoje)    { $classes .= ' is-hoje'; }
            elseif ($dia < $hoje)  { $classes .= ' is-passado'; }

            /* Os lugares livres do dia inteiro. É o número que responde ao
               telefone antes de se olhar para as horas. */
            $livresDoDia = 0;
            foreach ($d['vagas'] as $s) {
                if (!empty($s['is_open'])) {
                    $livresDoDia += max(0, (int)$s['capacity'] - (int)$s['seats_taken']);
                }
            }
            ?>
            <div class="<?= $classes ?>">
                <div class="cz-mdia__cabeca">
                    <span class="cz-mdia__numero"><?= e($d['data']->format('j')) ?></span>
                    <?php if ($livresDoDia > 0): ?>
                        <span class="cz-mdia__livres"><?= $livresDoDia ?> livre<?= $livresDoDia === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                </div>

                <?php foreach ($d['vagas'] as $s): ?>
                    <?php $e = $estadoDe($s); ?>
                    <a class="cz-fita <?= $e['classe'] ?>" href="/admin/vagas/<?= (int)$s['id'] ?>"
                       title="<?= e((string)$s['consultation_name']) ?> — <?= $e['tomado'] ?> de <?= $e['cap'] ?> lugares">
                        <span class="cz-fita__hora"><?= e(substr((string)$s['starts_at'], 11, 5)) ?></span>
                        <span class="cz-fita__estado">
                            <?php if (!$e['aberta']): ?>fechada
                            <?php elseif ($e['vagos'] === 0): ?>cheia
                            <?php else: ?><?= $e['vagos'] ?> livre<?= $e['vagos'] === 1 ? '' : 's' ?>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    </div>

<?php else: ?>

    <div class="cz-semana">
        <?php foreach ($dias as $dia => $d): ?>
            <div class="cz-dia<?= $dia === $hoje ? ' is-hoje' : ($dia < $hoje ? ' is-passado' : '') ?>">
                <div class="cz-dia__cabeca">
                    <span class="cz-dia__nome"><?= e($curtos[(int)$d['data']->format('N')]) ?></span>
                    <span class="cz-dia__numero"><?= e($d['data']->format('j')) ?></span>
                </div>

                <?php if (!$d['vagas']): ?>
                    <p class="cz-dia__vazio">—</p>
                <?php endif; ?>

                <?php foreach ($d['vagas'] as $s): ?>
                    <?php $e = $estadoDe($s); ?>
                    <a class="cz-vaga <?= $e['classe'] ?>" href="/admin/vagas/<?= (int)$s['id'] ?>">
                        <span class="cz-vaga__hora"><?= e(substr((string)$s['starts_at'], 11, 5)) ?></span>
                        <span class="cz-vaga__consulta"><?= e((string)$s['consultation_name']) ?></span>
                        <span class="cz-barrinha"><i style="width: <?= $e['pct'] ?>%"></i></span>
                        <span class="cz-vaga__conta">
                            <span><?= $e['tomado'] ?>/<?= $e['cap'] ?></span>
                            <?php if (!$e['aberta']): ?>
                                <span class="cz-vaga__marca">fechada</span>
                            <?php elseif ($e['vagos'] === 0): ?>
                                <span class="cz-vaga__marca">cheia</span>
                            <?php elseif ($s['source'] === 'avulsa'): ?>
                                <span class="cz-vaga__marca">data extra</span>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php if ($resumo['vagas'] === 0): ?>
    <?php /* Um período vazio tem duas causas muito diferentes, e dizer a errada
             manda alguém à procura do que está partido. Depois do horizonte não
             está partido nada: as vagas ainda não foram geradas, e vão sendo à
             medida que o tempo passa. */ ?>
    <?php $alemDoHorizonte = $horizonte !== null && $de > $horizonte; ?>
    <p class="cz-nada" style="margin-top:1rem">
        <?php if ($alemDoHorizonte): ?>
            Ainda não há vagas <?= $mensal ? 'neste mês' : 'nesta semana' ?>.
            O calendário vai até <strong><?= e($horizonte->format('d/m/Y')) ?></strong> —
            cerca de três meses — e vai andando sozinho: as datas seguintes
            aparecem à medida que estas passam.
        <?php else: ?>
            Nenhuma vaga <?= $mensal ? 'neste mês' : 'nesta semana' ?>. Ou não há
            horário que chegue até aqui, ou estes dias estão encerrados —
            <a href="/admin/consultas">veja as consultas</a> ou
            <a href="/admin/encerramentos">os dias encerrados</a>.
        <?php endif; ?>
    </p>
<?php endif; ?>

</div>
