<?php
/**
 * The staff door, for somebody who lost the key. Same screen as views/auth/login.php.
 *
 * The answer is deliberately the same whether the address exists or not — see
 * AuthController::sendReset. Telling somebody "no such account" at this door is
 * telling them which addresses do have one.
 */
use Admedia\Core\Auth;

$semMoldura = true;
$pageRobots = 'noindex, nofollow';
$bodyClass  = 'entrada';
$pageTitle  = 'Repor a palavra-passe · ' . setting('site.name', 'Covil Ancestral');
?>
<div class="entrada__folha">
  <a class="entrada__marca" href="<?= e(locale_url('/')) ?>">
    <img src="<?= asset('img/logotipo.svg') ?>" alt="" width="96" height="92">
    <span><?= e(mb_strtoupper(setting('site.name', 'Covil Ancestral'))) ?></span>
  </a>

  <div class="entrada__caixa">
    <p class="sobrescrita">Gestão do site</p>
    <h1 class="entrada__título">Repor a palavra-passe</h1>
    <p class="entrada__dizer">
      Escreva o seu e-mail. Se houver uma conta com esse endereço, recebe uma
      ligação para escolher uma palavra-passe nova.
    </p>

    <?php partial('partials/flash', ['flashes' => $flashes ?? []]); ?>

    <form method="post" action="<?= e(Auth::adminForgotPath()) ?>">
      <?= csrf_field() ?>

      <p class="entrada__campo">
        <label class="campo__rótulo" for="email">E-mail</label>
        <input class="campo<?= errors('email') ? ' campo--errado' : '' ?>" type="email" id="email" name="email"
               value="<?= e((string)old('email')) ?>" required autocomplete="username" autofocus>
        <?php foreach (errors('email') as $msg): ?>
          <span class="campo__erro"><?= e($msg) ?></span>
        <?php endforeach; ?>
      </p>

      <button type="submit" class="botão botão--cheio botão--largo">
        <span>Enviar a ligação</span>
      </button>
    </form>

    <p class="entrada__pé">
      <a href="<?= e(Auth::adminLoginPath()) ?>">&larr; Voltar a entrar</a>
    </p>
  </div>
</div>
