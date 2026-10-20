<?php
/**
 * A Sala, por dentro: quem lê as cartas na Casa.
 *
 * Uma linha por pessoa. O que se precisa de ver de relance é o nome, o que faz,
 * quantas leituras dá e se está à vista no site. O resto abre-se.
 *
 * **Criar é um campo e um botão**, aqui mesmo, e cai logo na edição. Um ecrã de
 * criação com metade dos campos e outro de edição com todos são dois
 * formulários a divergir.
 *
 * @var array $pessoas
 * @var array $quantas
 */
$pageTitle = 'A Sala';
?>
<div class="cz">

<header class="admin-header">
    <h1>A Sala</h1>
</header>

<p class="cz-nota">
    Quem lê as cartas na Casa. Cada pessoa tem uma página no site, em
    <code>/sala/&lt;nome&gt;</code>, e aparece na lista da Sala.
    <strong>A agenda continua a ser de cada leitura e não de cada pessoa</strong>:
    quem marca escolhe a leitura e a hora.
</p>

<form class="cz-criar" method="post" action="/admin/sala">
    <?= csrf_field() ?>
    <label for="nova">Nome de quem entra na Sala</label>
    <input id="nova" type="text" name="name" maxlength="190" required
           placeholder="Helena" autocomplete="off">
    <button type="submit" class="btn-admin btn-admin--primary">Acrescentar</button>
</form>

<?php if ($pessoas === []): ?>
    <p class="cz-vazio">Ainda não há ninguém na Sala.</p>
<?php else: ?>
    <div class="cz-lista">
        <?php foreach ($pessoas as $p): ?>
            <?php
            $id = (int)$p['id'];
            $publicada = !empty($p['is_published']);
            $leituras  = (int)($quantas[$id] ?? 0);
            ?>
            <div class="cz-item<?= $publicada ? '' : ' is-off' ?>">
                <div class="cz-item__nome">
                    <a href="/admin/sala/<?= $id ?>/edit"><?= e((string)$p['name']) ?></a>
                    <?php if (!$publicada): ?>
                        <span class="cz-selo is-cancelada">Fora do site</span>
                    <?php endif; ?>
                    <small>
                        <?= e((string)$p['role'] ?: 'sem função escrita') ?>
                        <?php if ((int)$p['years'] > 0): ?> · <?= (int)$p['years'] ?> anos à mesa<?php endif; ?>
                    </small>
                </div>

                <div class="cz-item__numeros">
                    <span<?= $leituras === 0 ? ' class="is-bad"' : '' ?>>
                        <?= $leituras === 0 ? 'sem leituras' : $leituras . ' leitura' . ($leituras === 1 ? '' : 's') ?>
                    </span>
                    <span><?= trim((string)$p['image']) !== '' ? 'com retrato' : 'sem retrato' ?></span>
                </div>

                <div class="cz-item__accoes">
                    <a href="/admin/sala/<?= $id ?>/edit" class="btn-admin btn-admin--sm">Editar</a>

                    <form class="inline-form" method="post" action="/admin/sala/<?= $id ?>/publicar">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-admin btn-admin--sm btn-admin--ghost">
                            <?= $publicada ? 'Esconder' : 'Publicar' ?>
                        </button>
                    </form>

                    <form class="inline-form" method="post" action="/admin/sala/<?= $id ?>"
                          data-confirm="Apagar «<?= e((string)$p['name']) ?>»? A página dela deixa de responder.">
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
