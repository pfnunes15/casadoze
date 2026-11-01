<?php
/**
 * The staff door, choosing the new password. Reached from the e-mail link.
 *
 * @var string $token
 */
use Admedia\Core\Auth;

$semMoldura = true;
$pageRobots = 'noindex, nofollow';
$bodyClass  = 'entrada';
$pageTitle  = 'Nova palavra-passe · ' . setting('site.name', 'Covil Ancestral');
?>
<div class="entrada__folha">
  <a class="entrada__marca" href="<?= e(locale_url('/')) ?>">
    <img src="<?= asset('img/logotipo.svg') ?>" alt="" width="96" height="92">
    <span><?= e(mb_strtoupper(setting('site.name', 'Covil Ancestral'))) ?></span>
  </a>

  <div class="entrada__caixa">
    <p class="sobrescrita">Gestão do site</p>
    <h1 class="entrada__título">Nova palavra-passe</h1>

    <?php partial('partials/flash', ['flashes' => $flashes ?? []]); ?>

    <form method="post" action="<?= e(Auth::adminResetPath()) ?>" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <p class="entrada__campo">
        <label class="campo__rótulo" for="password">Palavra-passe</label>
        <span class="campo__ajuda">Pelo menos 8 caracteres.</span>
        <input class="campo<?= errors('password') ? ' campo--errado' : '' ?>" type="password" id="password"
               name="password" required minlength="8" autocomplete="new-password" autofocus>
        <?php foreach (errors('password') as $msg): ?>
          <span class="campo__erro"><?= e($msg) ?></span>
        <?php endforeach; ?>
      </p>

      <p class="entrada__campo">
        <label class="campo__rótulo" for="password_confirmation">Outra vez, para confirmar</label>
        <input class="campo" type="password" id="password_confirmation" name="password_confirmation"
               required minlength="8" autocomplete="new-password">
      </p>

      <button type="submit" class="botão botão--cheio botão--largo">
        <span>Guardar e entrar</span>
      </button>
    </form>
  </div>
</div>
