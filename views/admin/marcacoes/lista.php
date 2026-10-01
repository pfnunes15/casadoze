<?php
/**
 * Quem vem, e quando. É a folha que se imprime de manhã.
 *
 * A segunda das duas vistas das Marcações. Por dia, e dentro do dia por vaga:
 * às dez chegam estes, às três chegam aqueles. Numa tabela plana a hora
 * repetia-se em cada linha e o grupo das dez ficava por reagrupar na cabeça de
 * quem lê.
 *
 * As canceladas aparecem, riscadas: quem olha para o dia quer saber que a mesa
 * das quatro se desmarcou, e não que ela nunca existiu.
 *
 * Marcar não se faz aqui — faz-se no ecrã da vaga, que é onde se vê se há
 * lugar. A vista «Calendário» é o caminho até lá.
 *
 * @var array<string,array<int,array{vaga:array,marcacoes:array,pessoas:int}>> $agenda
 * @var \DateTimeImmutable $de
 * @var \DateTimeImmutable $ate
 * @var int                $porVir
 * @var int                $pessoas
 */
$pageTitle = 'Marcações';
$dias  = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];
$hoje  = (new DateTimeImmutable('today'))->format('Y-m-d');
?>

<div class="cz">

<?php partial('admin/marcacoes/topo', ['vista' => 'lista', 'porVir' => $porVir]); ?>

<div class="cz-barra">
    <div class="cz-barra__quando">
        <?= e($de->format('d/m/Y')) ?> – <?= e($de->modify('+13 days')->format('d/m/Y')) ?>
        <small>catorze dias</small>
    </div>

    <a class="btn-admin btn-admin--sm btn-admin--ghost"
       href="/admin/marcacoes?vista=lista&amp;de=<?= e($de->modify('-14 days')->format('Y-m-d')) ?>">←</a>
    <a class="btn-admin btn-admin--sm btn-admin--ghost"
       href="/admin/marcacoes?vista=lista&amp;de=<?= e($hoje) ?>">Hoje</a>
    <a class="btn-admin btn-admin--sm btn-admin--ghost"
       href="/admin/marcacoes?vista=lista&amp;de=<?= e($ate->format('Y-m-d')) ?>">→</a>

    <form method="get" action="/admin/marcacoes" class="inline-form">
        <input type="hidden" name="vista" value="lista">
        <input type="date" name="de" value="<?= e($de->format('Y-m-d')) ?>" aria-label="A partir de">
        <button type="submit" class="btn-admin btn-admin--sm">Ver</button>
    </form>
</div>

<?php if (!$agenda): ?>
    <p class="cz-nada">
        Não há marcações entre <?= e($de->format('d/m/Y')) ?> e <?= e($ate->format('d/m/Y')) ?>.
        Para marcar uma, <a href="/admin/marcacoes">abra o calendário</a> e escolha a vaga.
    </p>
<?php else: ?>

    <div class="cz-resumo">
        <div><strong><?= $pessoas ?></strong><span>Pessoas nestes 14 dias</span></div>
        <div><strong><?= count($agenda) ?></strong><span>Dias com consultas</span></div>
    </div>

    <div class="cz-agenda">
        <?php foreach ($agenda as $dia => $vagas): ?>
            <?php
            $d = new DateTimeImmutable($dia);
            $doDia = 0;
            foreach ($vagas as $g) { $doDia += $g['pessoas']; }
            ?>
            <section class="cz-agenda__dia">
                <h2>
                    <?= e($dias[(int)$d->format('N')]) ?>, <?= e($d->format('d/m')) ?>
                    <?php if ($dia === $hoje): ?><small>· hoje</small><?php endif; ?>
                    <small>— <?= $doDia ?> pessoa<?= $doDia === 1 ? '' : 's' ?></small>
                </h2>

                <?php foreach ($vagas as $g): ?>
                    <?php $v = $g['vaga']; ?>
                    <article class="cz-slot">
                        <div class="cz-slot__cabeca">
                            <span class="cz-slot__hora"><?= e(substr((string)$v['starts_at'], 11, 5)) ?></span>
                            <span class="cz-slot__consulta"><?= e((string)$v['consultation_name']) ?></span>
                            <span class="cz-slot__conta"><?= (int)$v['seats_taken'] ?>/<?= (int)$v['capacity'] ?> lugares</span>
                            <a class="btn-admin btn-admin--sm btn-admin--ghost"
                               href="/admin/vagas/<?= (int)$v['slot_id'] ?>">Abrir</a>
                        </div>

                        <ul class="cz-pessoas">
                            <?php foreach ($g['marcacoes'] as $r): ?>
                                <li class="cz-pessoa<?= $r['status'] === 'cancelada' ? ' is-cancelada' : '' ?>">
                                    <span class="cz-pessoa__pax"><?= (int)$r['people'] ?></span>
                                    <span class="cz-pessoa__nome">
                                        <a href="/admin/marcacoes/<?= (int)$r['id'] ?>"><?= e((string)$r['name']) ?></a>
                                        <small><?= e((string)$r['code']) ?><?= $r['status'] === 'cancelada' ? ' · cancelada' : '' ?></small>
                                    </span>
                                    <span class="cz-pessoa__contacto">
                                        <a href="mailto:<?= e((string)$r['email']) ?>"><?= e((string)$r['email']) ?></a>
                                        <?php if (trim((string)$r['phone']) !== ''): ?>
                                            · <a href="tel:<?= e((string)$r['phone']) ?>"><?= e((string)$r['phone']) ?></a>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($r['status'] !== 'cancelada'): ?>
                                        <form class="inline-form" method="post"
                                              action="/admin/marcacoes/<?= (int)$r['id'] ?>/cancelar"
                                              data-confirm="Cancelar a marcação de <?= e((string)$r['name']) ?>? Os lugares voltam à vaga.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (trim((string)($r['note'] ?? '')) !== ''): ?>
                                        <span class="cz-pessoa__nota"><?= nl2br(e((string)$r['note'])) ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>
