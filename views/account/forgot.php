<?php
/**
 * Recovering the password.
 *
 * Also what is left for somebody who let the seven days of the invitation run
 * out: the account exists all the same, and this link gets into it.
 */
use Admedia\Core\Auth;
use Admedia\Core\Accounts\CustomerAuth;

$pageTitle  = 'Recuperar a palavra-passe · ' . setting('site.name', 'Covil Ancestral');
$pageRobots = 'noindex, nofollow';
$recuperar  = locale_url(Auth::doors()['customer_forgot'] ?? '/conta/recuperar');

$diz = static fn(string $chave): array => (array)(($flashes ?? [])[$chave] ?? []);
?>
<section class="secção porta">
  <div class="miolo porta__par">
    <div class="porta__caixa">
      <p class="sobrescrita">A tua conta</p>
      <h1 class="porta__título">Recuperar acesso</h1>
      <p class="porta__dizer-topo">
        Escreve o email da tua conta e enviamos-te uma ligação para escolheres
        uma palavra-passe nova.
      </p>

      <?php foreach ($diz('account.bad') as $m): ?><p class="aviso aviso--mau" role="alert"><?= e($m) ?></p><?php endforeach; ?>
      <?php foreach ($diz('account.ok') as $m): ?><p class="aviso aviso--bom" role="status"><?= e($m) ?></p><?php endforeach; ?>

      <form method="post" action="<?= e($recuperar) ?>">
        <?= csrf_field() ?>

        <p class="porta__campo">
          <label class="campo__rótulo" for="email">Email da conta</label>
          <input class="campo" type="email" id="email" name="email" maxlength="190"
                 value="<?= e((string)old('email')) ?>" required autocomplete="email" autofocus>
        </p>

        <button type="submit" class="botão botão--cheio botão--largo">
          <span>Enviar a ligação</span>
        </button>
      </form>

      <p class="porta__pé">
        <a href="<?= e(CustomerAuth::loginPath()) ?>">&larr; Voltar a entrar</a>
      </p>
    </div>

    <?php partial('partials/conta-lado'); ?>
  </div>
</section>
