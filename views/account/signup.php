<?php
/**
 * Asking for an account.
 *
 * No password field. It asks for the e-mail, sends a link, and the password is
 * chosen on the other side — the same road as somebody who bought as a guest.
 * So only whoever actually reads that mailbox ends up with an account, and
 * nobody types a password here and forgets it before confirming anything.
 */
use Admedia\Core\Auth;

$pageTitle  = 'Criar conta · ' . setting('site.name', 'Covil Ancestral');
$pageRobots = 'noindex, nofollow';
$registar   = locale_url(Auth::doors()['customer_signup'] ?? '/conta/registar');

/* This screen's own messages. `$flashes` is what the View hands every view, and
   each key carries a list because nothing stops there being two. */
$diz = static fn(string $chave): array => (array)(($flashes ?? [])[$chave] ?? []);
?>
<section class="secção porta">
  <div class="miolo porta__par">
    <div class="porta__caixa">
      <p class="sobrescrita">A tua conta</p>
      <h1 class="porta__título">Criar conta</h1>
      <p class="porta__dizer-topo">
        Leva menos de um minuto, e não precisas de conta para comprar.
        Escreve o teu email e enviamos-te uma ligação para definires a palavra-passe.
      </p>

      <?php partial('partials/conta-abas', ['atual' => 'registar']); ?>

      <?php foreach ($diz('account.bad') as $m): ?><p class="aviso aviso--mau" role="alert"><?= e($m) ?></p><?php endforeach; ?>
      <?php foreach ($diz('account.ok') as $m): ?><p class="aviso aviso--bom" role="status"><?= e($m) ?></p><?php endforeach; ?>

      <form method="post" action="<?= e($registar) ?>">
        <?= csrf_field() ?>

        <p class="porta__campo">
          <label class="campo__rótulo" for="name">Nome <span class="campo__opcional">(opcional)</span></label>
          <input class="campo" type="text" id="name" name="name" maxlength="190"
                 value="<?= e((string)old('name')) ?>" autocomplete="name">
        </p>

        <p class="porta__campo">
          <label class="campo__rótulo" for="email">Email</label>
          <input class="campo" type="email" id="email" name="email" maxlength="190"
                 value="<?= e((string)old('email')) ?>" required autocomplete="email">
        </p>

        <button type="submit" class="botão botão--cheio botão--largo">
          <span>Criar conta</span>
        </button>
      </form>
    </div>

    <?php partial('partials/conta-lado'); ?>
  </div>
</section>
