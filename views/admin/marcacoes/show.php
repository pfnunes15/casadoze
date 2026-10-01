<?php
/**
 * Uma marcação.
 *
 * Não se edita. Uma marcação é o registo do que alguém marcou, e um registo que
 * se reescreve não é um registo — cancela-se, e a pessoa marca outra vez.
 *
 * @var array $marcacao
 */
$pageTitle = 'Marcação ' . (string)$marcacao['code'];
$inicio = new DateTimeImmutable((string)$marcacao['starts_at']);
$fim    = $inicio->modify('+' . (int)$marcacao['duration_min'] . ' minutes');
?>

<div class="cz">

<header class="admin-header">
    <h1>Marcação <?= e((string)$marcacao['code']) ?></h1>
    <a href="/admin/marcacoes?de=<?= e($inicio->format('Y-m-d')) ?>" class="btn-admin btn-admin--ghost">Voltar</a>
</header>

<dl class="cz-dl">
    <?php $linha = App\Models\Visit::linha($marcacao); ?>
    <dt>Consulta</dt><dd><?= e((string)$marcacao['consultation_name']) ?><?php if ($linha !== ''): ?> — <?= e($linha) ?><?php endif; ?></dd>
    <dt>Quando</dt><dd><?= e($inicio->format('d/m/Y')) ?>, das <?= e($inicio->format('H:i')) ?> às <?= e($fim->format('H:i')) ?></dd>
    <dt>Pessoas</dt><dd><?= (int)$marcacao['people'] ?></dd>
    <dt>Quem</dt><dd><?= e((string)$marcacao['name']) ?></dd>
    <dt>E-mail</dt><dd><a href="mailto:<?= e((string)$marcacao['email']) ?>"><?= e((string)$marcacao['email']) ?></a></dd>
    <?php if (trim((string)$marcacao['phone']) !== ''): ?>
        <dt>Telefone</dt><dd><a href="tel:<?= e((string)$marcacao['phone']) ?>"><?= e((string)$marcacao['phone']) ?></a></dd>
    <?php endif; ?>
    <?php if (trim((string)($marcacao['note'] ?? '')) !== ''): ?>
        <dt>Nota de quem marcou</dt><dd><?= nl2br(e((string)$marcacao['note'])) ?></dd>
    <?php endif; ?>
    <dt>Estado</dt><dd>
        <?php if ($marcacao['status'] === 'cancelada'): ?>
            <span class="cz-selo is-cancelada">Cancelada</span>
            em <?= e(date('d/m/Y H:i', strtotime((string)$marcacao['cancelled_at']))) ?>
        <?php else: ?>
            <span class="cz-selo">Confirmada</span>
        <?php endif; ?>
    </dd>
    <dt>Marcada em</dt><dd><?= e(date('d/m/Y H:i', strtotime((string)$marcacao['created_at']))) ?></dd>
    <dt>Aviso por e-mail</dt><dd><?= $marcacao['mailed_at'] === null ? 'não saiu' : 'enviado em ' . e(date('d/m/Y H:i', strtotime((string)$marcacao['mailed_at']))) ?></dd>
</dl>

<?php if ($marcacao['status'] !== 'cancelada'): ?>
    <p>Cancelar devolve os <?= (int)$marcacao['people'] ?> lugares à vaga. O aviso a
       quem marcou não sai daqui — quem cancela do lado da casa costuma ter uma
       razão que quer explicar, e um e-mail automático sem essa razão é pior do
       que telefonema nenhum.</p>
    <form method="post" action="/admin/marcacoes/<?= (int)$marcacao['id'] ?>/cancelar"
          data-confirm="Cancelar a marcação de <?= e((string)$marcacao['name']) ?>?">
        <?= csrf_field() ?>
        <button type="submit" class="btn-admin btn-admin--danger">Cancelar a marcação</button>
    </form>
<?php endif; ?>

</div>
