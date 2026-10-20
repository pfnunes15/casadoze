<?php
/**
 * Uma sessão do Grimório.
 *
 * @var array $sessão
 * @var array $produtos    os publicados da loja
 * @var array $escolhidos  ids das peças usadas nesta sessão
 */
$pageTitle = (string)$sessão['title'];
$id = (int)$sessão['id'];

/* Os oito desenhos que a Casa tem. Escritos aqui porque são os que o
   js/arte-produtos.js sabe pintar — acrescentar um sem o desenhar lá dava uma
   moldura preta. */
$desenhos = ['', 'candle', 'oil', 'crystal', 'amulet', 'moon', 'sage', 'tarot', 'grimoire'];
?>
<div class="cz">

<header class="admin-header">
    <h1><?= e((string)$sessão['title']) ?></h1>
    <a href="/admin/grimorio" class="btn-admin btn-admin--ghost">Voltar ao Grimório</a>
</header>

<form method="post" action="/admin/grimorio/<?= $id ?>" class="cz-forma">
    <?= csrf_field() ?>

    <fieldset>
        <legend>A sessão</legend>

        <label for="title">Título</label>
        <input id="title" type="text" name="title" maxlength="190" required
               value="<?= e((string)$sessão['title']) ?>">

        <label for="slug">Endereço da página</label>
        <input id="slug" type="text" name="slug" maxlength="190" value="<?= e((string)$sessão['slug']) ?>">
        <small>Fica em <code>/grimorio/<?= e((string)$sessão['slug']) ?></code>.</small>

        <label for="theme">Tema</label>
        <input id="theme" type="text" name="theme" maxlength="120"
               value="<?= e((string)$sessão['theme']) ?>" placeholder="Rituais da lua">
        <small>É o que aparece no filtro do Grimório. Escreva o mesmo tema nas sessões que o partilham.</small>

        <label for="summary">Frase</label>
        <textarea id="summary" name="summary" rows="3" maxlength="500"><?= e((string)$sessão['summary']) ?></textarea>

        <label for="body">Texto</label>
        <textarea id="body" name="body" rows="6"><?= e((string)$sessão['body']) ?></textarea>
    </fieldset>

    <fieldset>
        <legend>O vídeo</legend>

        <label for="url">Endereço no YouTube</label>
        <input id="url" type="url" name="url" maxlength="500" value="<?= e((string)$sessão['url']) ?>"
               placeholder="https://www.youtube.com/watch?v=...">
        <small>
            Cole o que está na barra do browser enquanto vê o vídeo. Os parâmetros de
            seguimento, a lista de onde veio e o «começar aos 1:00» são aceites.
            <strong>Sem endereço, a página desenha a moldura e diz que a sessão ainda não
            tem vídeo</strong> — que é melhor do que um rectângulo preto.
        </small>

        <label for="duration">Duração</label>
        <input id="duration" type="text" name="duration" maxlength="12"
               value="<?= e((string)$sessão['duration']) ?>" placeholder="18:42">
        <small>Escrita à mão: a contagem automática vinha da API do YouTube, que não está ligada.</small>

        <label for="published_at">Data</label>
        <input id="published_at" type="date" name="published_at"
               value="<?= e((string)($sessão['published_at'] ?? '')) ?>">
        <small>As mais recentes aparecem primeiro. Sem data, vai para o fim.</small>

        <label for="chapters">Capítulos</label>
        <textarea id="chapters" name="chapters" rows="6"
                  placeholder="00:00 Abertura&#10;02:15 Preparar o altar"><?= e((string)$sessão['chapters']) ?></textarea>
        <small>Um por linha, com o tempo à frente.</small>

        <label for="drawing">Desenho</label>
        <select id="drawing" name="drawing">
            <?php foreach ($desenhos as $d): ?>
                <option value="<?= e($d) ?>" <?= (string)$sessão['drawing'] === $d ? 'selected' : '' ?>>
                    <?= $d === '' ? '— nenhum —' : e($d) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small>Usado onde não há miniatura do YouTube.</small>
    </fieldset>

    <fieldset>
        <legend>O que foi usado</legend>

        <?php if ($produtos === []): ?>
            <p class="cz-vazio">Ainda não há peças publicadas na loja.</p>
        <?php else: ?>
            <div class="cz-caixas">
                <?php foreach ($produtos as $p): ?>
                    <label class="cz-caixa">
                        <input type="checkbox" name="products[]" value="<?= (int)$p['id'] ?>"
                               <?= in_array((int)$p['id'], $escolhidos, true) ? 'checked' : '' ?>>
                        <span><?= e((string)$p['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small>Aparecem na página da sessão, a ligar à ficha de cada uma.</small>
        <?php endif; ?>
    </fieldset>

    <fieldset>
        <legend>No site</legend>

        <label class="cz-caixa">
            <input type="checkbox" name="is_published" value="1"
                   <?= !empty($sessão['is_published']) ? 'checked' : '' ?>>
            <span>À vista no Grimório</span>
        </label>

        <label for="sort_order">Ordem</label>
        <input id="sort_order" type="number" name="sort_order" value="<?= (int)$sessão['sort_order'] ?>">
        <small>Só conta entre sessões com a mesma data, ou sem data nenhuma.</small>
    </fieldset>

    <div class="cz-forma__pe">
        <button type="submit" class="btn-admin btn-admin--primary">Guardar</button>
        <?php if (!empty($sessão['is_published'])): ?>
            <a href="/grimorio/<?= e((string)$sessão['slug']) ?>" class="btn-admin btn-admin--ghost"
               target="_blank" rel="noopener">Ver no site</a>
        <?php endif; ?>
    </div>
</form>

</div>
