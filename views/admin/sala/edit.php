<?php
/**
 * Quem é esta pessoa, e que leituras dá.
 *
 * Um formulário só. As listas — especialidades, formatos e línguas — escrevem-se
 * uma por linha numa caixa de texto, e é isso que os filtros da página da Sala
 * lêem: uma especialidade nova aparece no filtro no dia em que for escrita aqui.
 *
 * @var array $pessoa
 * @var array $consultas   todas, publicadas ou não
 * @var array $escolhidas  ids das que esta pessoa dá
 */

use App\Models\Practitioner;

$pageTitle = (string)$pessoa['name'];
$id = (int)$pessoa['id'];
?>
<div class="cz">

<header class="admin-header">
    <h1><?= e((string)$pessoa['name']) ?></h1>
    <a href="/admin/sala" class="btn-admin btn-admin--ghost">Voltar à Sala</a>
</header>

<form method="post" action="/admin/sala/<?= $id ?>" class="cz-forma">
    <?= csrf_field() ?>

    <fieldset>
        <legend>Quem é</legend>

        <label for="name">Nome</label>
        <input id="name" type="text" name="name" maxlength="190" required
               value="<?= e((string)$pessoa['name']) ?>">

        <label for="slug">Endereço da página</label>
        <input id="slug" type="text" name="slug" maxlength="160"
               value="<?= e((string)$pessoa['slug']) ?>">
        <small>
            Fica em <code>/sala/<?= e((string)$pessoa['slug']) ?></code>. Mudá-lo parte
            as ligações que alguém já tenha guardado.
        </small>

        <label for="role">O que faz</label>
        <input id="role" type="text" name="role" maxlength="190"
               value="<?= e((string)$pessoa['role']) ?>" placeholder="Taróloga">

        <label for="quote">Uma frase sua</label>
        <input id="quote" type="text" name="quote" maxlength="500"
               value="<?= e((string)$pessoa['quote']) ?>">
        <small>Aparece em destaque no perfil, entre aspas.</small>

        <label for="bio">Percurso</label>
        <textarea id="bio" name="bio" rows="6"><?= e((string)$pessoa['bio']) ?></textarea>

        <label for="years">Anos à mesa</label>
        <input id="years" type="number" name="years" min="0" max="99"
               value="<?= (int)$pessoa['years'] ?>">
        <small>Zero não mostra nada.</small>
    </fieldset>

    <fieldset>
        <legend>Como lê</legend>

        <label for="deck">Baralho</label>
        <input id="deck" type="text" name="deck" maxlength="190"
               value="<?= e((string)$pessoa['deck']) ?>" placeholder="Rider-Waite-Smith">

        <label for="sign">Signo</label>
        <input id="sign" type="text" name="sign" maxlength="60"
               value="<?= e((string)$pessoa['sign']) ?>">

        <?php /* Uma por linha. É o que os filtros da Sala lêem — ver
                 App\Models\Practitioner::lista. */ ?>
        <label for="specialties">Especialidades</label>
        <textarea id="specialties" name="specialties" rows="4"
                  placeholder="Tarot&#10;Amor e relações"><?= e((string)$pessoa['specialties']) ?></textarea>
        <small>Uma por linha. Estas são as que aparecem no filtro da Sala.</small>

        <label for="formats">Formatos</label>
        <textarea id="formats" name="formats" rows="3"
                  placeholder="Online&#10;Presencial"><?= e((string)$pessoa['formats']) ?></textarea>
        <small>Uma por linha.</small>

        <label for="languages">Línguas</label>
        <textarea id="languages" name="languages" rows="3"
                  placeholder="Português&#10;English"><?= e((string)$pessoa['languages']) ?></textarea>
        <small>Uma por linha.</small>
    </fieldset>

    <fieldset>
        <legend>Retrato</legend>

        <label for="image">Endereço da fotografia</label>
        <input id="image" type="text" name="image" maxlength="255"
               value="<?= e((string)$pessoa['image']) ?>" placeholder="/assets/uploads/helena.webp">
        <small>
            Sem fotografia, a página desenha as iniciais —
            <strong><?= e(Practitioner::iniciais($pessoa)) ?></strong> — sobre o fundo da Casa.
            É melhor do que um rectângulo vazio onde devia estar uma cara.
        </small>

        <label for="image_focus">De onde recortar</label>
        <input id="image_focus" type="text" name="image_focus" maxlength="40"
               value="<?= e((string)($pessoa['image_focus'] ?? 'center')) ?>" placeholder="center">
        <small>
            Os cartões são mais altos do que largos e a fotografia não é: o browser
            recorta-a, e por omissão recorta pelo meio. Se a pessoa estiver de lado,
            escreva <code>right center</code> ou <code>left center</code>; se a cara
            estiver em cima, <code>center 30%</code>.
        </small>

        <label for="image_alt">Descrição da fotografia</label>
        <input id="image_alt" type="text" name="image_alt" maxlength="255"
               value="<?= e((string)$pessoa['image_alt']) ?>">
        <small>Para quem ouve a página em vez de a ver.</small>
    </fieldset>

    <fieldset>
        <legend>Que leituras dá</legend>

        <?php if ($consultas === []): ?>
            <p class="cz-vazio">Ainda não há consultas no catálogo.</p>
        <?php else: ?>
            <div class="cz-caixas">
                <?php foreach ($consultas as $c): ?>
                    <label class="cz-caixa">
                        <input type="checkbox" name="consultations[]" value="<?= (int)$c['id'] ?>"
                               <?= in_array((int)$c['id'], $escolhidas, true) ? 'checked' : '' ?>>
                        <span>
                            <?= e((string)$c['name']) ?>
                            <?php if (empty($c['is_published'])): ?>
                                <small>(fora do site)</small>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small>
                Aparecem no perfil, com o preço e a próxima vaga. A hora continua a
                marcar-se na página da leitura.
            </small>
        <?php endif; ?>
    </fieldset>

    <fieldset>
        <legend>No site</legend>

        <label class="cz-caixa">
            <input type="checkbox" name="is_published" value="1"
                   <?= !empty($pessoa['is_published']) ? 'checked' : '' ?>>
            <span>À vista na Sala</span>
        </label>

        <label for="sort_order">Ordem</label>
        <input id="sort_order" type="number" name="sort_order" value="<?= (int)$pessoa['sort_order'] ?>">
        <small>Menor aparece primeiro.</small>
    </fieldset>

    <div class="cz-forma__pe">
        <button type="submit" class="btn-admin btn-admin--primary">Guardar</button>
        <?php if (!empty($pessoa['is_published'])): ?>
            <a href="/sala/<?= e((string)$pessoa['slug']) ?>" class="btn-admin btn-admin--ghost"
               target="_blank" rel="noopener">Ver no site</a>
        <?php endif; ?>
    </div>
</form>

</div>
