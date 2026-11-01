<?php
/**
 * The customers' door.
 *
 * Inside the site, unlike the staff door: somebody signing in here is in the
 * middle of buying or booking, and taking the menu and the basket away mid-way
 * would strand them. The staff door, which is a tool and not a page, drops all
 * of it — see views/auth/login.php.
 *
 * It posts to `CustomerAuth::loginPath()`. The package's version of this file
 * posts to `Shop::to('login')`, which on this site is `/login` — the staff door,
 * against the `users` table. A customer with the right password was told the
 * password was wrong, because he was being looked for in the wrong table. The
 * POST that answers this form is registered in config/routes.php.
 */
use Admedia\Core\Auth;
use Admedia\Core\Accounts\CustomerAuth;

$pageTitle  = 'Entrar · ' . setting('site.name', 'Covil Ancestral');
$pageRobots = 'noindex, nofollow';
/* O «e-mail ou palavra-passe incorretos» é desenhado aqui dentro, ao pé do
   campo. Sem isto a moldura desenhava-o outra vez no topo da página. */
$avisosNaVista = true;
$recuperar  = locale_url(Auth::doors()['customer_forgot'] ?? '/conta/recuperar');
?>
<section class="secção porta">
  <div class="miolo porta__par">
    <div class="porta__caixa">
      <p class="sobrescrita">A tua conta</p>
      <h1 class="porta__título">Bem-vinda de volta</h1>
      <p class="porta__dizer-topo">Entra para veres encomendas, consultas e moradas.</p>

      <?php partial('partials/conta-abas', ['atual' => 'entrar']); ?>
      <?php partial('partials/flash', ['flashes' => $flashes ?? []]); ?>

      <form method="post" action="<?= e(CustomerAuth::loginPath()) ?>">
        <?= csrf_field() ?>

        <p class="porta__campo">
          <label class="campo__rótulo" for="email">Email</label>
          <input class="campo" type="email" id="email" name="email" placeholder="o.teu@email.pt"
                 value="<?= e((string)old('email')) ?>" required autocomplete="username" autofocus>
        </p>

        <p class="porta__campo">
          <span class="porta__rótulo-fila">
            <label class="campo__rótulo" for="password">Palavra-passe</label>
            <a class="porta__esqueci" href="<?= e($recuperar) ?>">Esqueci-me</a>
          </span>
          <input class="campo" type="password" id="password" name="password"
                 required autocomplete="current-password">
        </p>

        <button type="submit" class="botão botão--cheio botão--largo">
          <span>Entrar</span>
        </button>
      </form>

      <?php /* Where the people who run the house go. Named here because this is
               the door they will try first, and an unexplained refusal at the
               wrong door is the worst half-minute of anybody's day. */ ?>
      <p class="porta__pé">
        <?= e(__('account.staff_hint')) ?>
        <a href="<?= e(Auth::adminLoginPath()) ?>"><?= e(__('account.staff_door')) ?></a>
      </p>
    </div>

    <?php partial('partials/conta-lado'); ?>
  </div>
</section>
