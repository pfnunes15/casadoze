<?php
/**
 * The staff door.
 *
 * Not a page of the site, so it does not wear the site: no menu, no basket, no
 * five-storey footer. Somebody arriving here came to work, and a basket sitting
 * above a password field is an invitation to click the wrong thing. `$semMoldura`
 * is what tells the layout to leave all that out.
 *
 * The customers' door is somewhere else entirely — `/conta/entrar`, against the
 * `customers` table, with a session of its own. The two have nothing to do with
 * one another and this screen does not link to that one: a customer who lands
 * here by accident is told where to go, and that is all.
 *
 * The addresses come from Auth and are not written out here. On a site with a
 * shop the staff door is not always at /login, and a form that posts to a
 * hard-coded address posts to the wrong door. See config/auth.php.
 */
use Admedia\Core\Auth;

$semMoldura = true;
$pageRobots = 'noindex, nofollow';
$bodyClass  = 'entrada';
$pageTitle  = 'Entrar na gestão · ' . setting('site.name', 'Covil Ancestral');
?>
<div class="entrada__folha">
  <a class="entrada__marca" href="<?= e(locale_url('/')) ?>">
    <img src="<?= asset('img/logotipo.svg') ?>" alt="" width="96" height="92">
    <span><?= e(mb_strtoupper(setting('site.name', 'Covil Ancestral'))) ?></span>
  </a>

  <div class="entrada__caixa">
    <p class="sobrescrita">Gestão do site</p>
    <h1 class="entrada__título">Entrar</h1>

    <?php partial('partials/flash', ['flashes' => $flashes ?? []]); ?>

    <form method="post" action="<?= e(Auth::adminLoginPath()) ?>">
      <?= csrf_field() ?>

      <p class="entrada__campo">
        <label class="campo__rótulo" for="email">E-mail</label>
        <input class="campo<?= errors('email') ? ' campo--errado' : '' ?>" type="email" id="email" name="email"
               value="<?= e((string)old('email')) ?>" required autocomplete="username" autofocus>
        <?php foreach (errors('email') as $msg): ?>
          <span class="campo__erro"><?= e($msg) ?></span>
        <?php endforeach; ?>
      </p>

      <p class="entrada__campo">
        <label class="campo__rótulo" for="password">Palavra-passe</label>
        <input class="campo<?= errors('password') ? ' campo--errado' : '' ?>" type="password" id="password"
               name="password" required autocomplete="current-password">
        <?php foreach (errors('password') as $msg): ?>
          <span class="campo__erro"><?= e($msg) ?></span>
        <?php endforeach; ?>
      </p>

      <button type="submit" class="botão botão--cheio botão--largo">
        <span>Entrar</span>
      </button>
    </form>

    <p class="entrada__pé">
      <a href="<?= e(Auth::adminForgotPath()) ?>">Esqueci-me da palavra-passe</a>
    </p>
  </div>

  <?php /* The customers' door, named and not linked to by accident. Somebody who
           bought something and ended up here needs to be told this is not it. */ ?>
  <p class="entrada__fora">
    Vieste comprar ou marcar? <a href="<?= e(\Admedia\Core\Accounts\CustomerAuth::loginPath()) ?>">A tua conta é aqui</a>.
  </p>
  <p class="entrada__fora">
    <a href="<?= e(locale_url('/')) ?>">&larr; Voltar ao site</a>
  </p>
</div>
