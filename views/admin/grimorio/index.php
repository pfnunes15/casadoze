<?php
/**
 * O Grimório, por dentro: as sessões do canal.
 *
 * @var array $sessões
 * @var array $peças
 */
$pageTitle = 'Grimório';
?>
<div class="cz">

<header class="admin-header">
    <h1>Grimório</h1>
</header>

<p class="cz-nota">
    As sessões gratuitas do canal. Cada uma tem página própria em
    <code>/grimorio/&lt;título&gt;</code>, e as três mais recentes aparecem na porta
    de entrada. <strong>A peça da loja usada em cada sessão é o que faz o
    Grimório vender</strong>: quem vê um ritual com sálvia encontra o molho a um
    clique.
</p>

<form class="cz-criar" method="post" action="/admin/grimorio">
    <?= csrf_field() ?>
    <label for="nova">Título da sessão</label>
    <input id="nova" type="text" name="title" maxlength="190" required
           placeholder="Ritual de lua cheia" autocomplete="off">
    <button type="submit" class="btn-admin btn-admin--primary">Acrescentar</button>
</form>

<?php if ($sessões === []): ?>
    <p class="cz-vazio">Ainda não há sessões.</p>
<?php else: ?>
    <div class="cz-lista">
        <?php foreach ($sessões as $v): ?>
            <?php
            $id = (int)$v['id'];
            $publicada = !empty($v['is_published']);
            $temVídeo  = \App\Models\Video::noYouTube($v) !== null;
            $quantas   = (int)($peças[$id] ?? 0);
            ?>
            <div class="cz-item<?= $publicada ? '' : ' is-off' ?>">
                <div class="cz-item__nome">
                    <a href="/admin/grimorio/<?= $id ?>/edit"><?= e((string)$v['title']) ?></a>
                    <?php if (!$publicada): ?>
                        <span class="cz-selo is-cancelada">Fora do site</span>
                    <?php endif; ?>
                    <small>
                        <?= e((string)$v['theme'] ?: 'sem tema') ?>
                        <?php if (trim((string)$v['duration']) !== ''): ?> · <?= e((string)$v['duration']) ?><?php endif; ?>
                    </small>
                </div>

                <div class="cz-item__numeros">
                    <span<?= $temVídeo ? '' : ' class="is-bad"' ?>>
                        <?= $temVídeo ? 'com vídeo' : 'sem vídeo' ?>
                    </span>
                    <span<?= $quantas === 0 ? ' class="is-bad"' : '' ?>>
                        <?= $quantas === 0 ? 'sem peças' : $quantas . ' peça' . ($quantas === 1 ? '' : 's') ?>
                    </span>
                </div>

                <div class="cz-item__accoes">
                    <a href="/admin/grimorio/<?= $id ?>/edit" class="btn-admin btn-admin--sm">Editar</a>

                    <form class="inline-form" method="post" action="/admin/grimorio/<?= $id ?>/publicar">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-admin btn-admin--sm btn-admin--ghost">
                            <?= $publicada ? 'Esconder' : 'Publicar' ?>
                        </button>
                    </form>

                    <form class="inline-form" method="post" action="/admin/grimorio/<?= $id ?>"
                          data-confirm="Apagar «<?= e((string)$v['title']) ?>»? A página dela deixa de responder.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Eliminar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>
