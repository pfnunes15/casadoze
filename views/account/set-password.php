<?php
/**
 * Choosing the password, arriving from a link in an e-mail.
 *
 * The same screen for the invitation and for the recovery, because it is the
 * same thing happening. What changes is how it is put: to one it is the
 * account's first password, to the other it is another one.
 *
 * The form has no `action` on purpose — it posts back to whichever of the two
 * addresses brought the person here, and those are two different ones.
 *
 * @var string $token
 * @var bool   $isFirst
 */
use Admedia\Core\Accounts\CustomerAuth;

$pageTitle  = ($isFirst ? 'Definir a palavra-passe' : 'Nova palavra-passe')
            . ' · ' . setting('site.name', 'Covil Ancestral');
$pageRobots = 'noindex, nofollow';

$diz = static fn(string $chave): array => (array)(($flashes ?? [])[$chave] ?? []);
?>
<section class="secção porta">
  <div class="miolo porta__par">
    <div class="porta__caixa">
      <p class="sobrescrita">A tua conta</p>
      <h1 class="porta__título"><?= $isFirst ? 'Definir a palavra-passe' : 'Nova palavra-passe' ?></h1>
      <p class="porta__dizer-topo">
        <?= $isFirst
            ? 'A tua conta foi criada com a tua compra. Escolhe uma palavra-passe e ficas com acesso a tudo o que reservaste.'
            : 'Escolhe a nova palavra-passe da tua conta.' ?>
      </p>

      <?php foreach ($diz('account.bad') as $m): ?><p class="aviso aviso--mau" role="alert"><?= e($m) ?></p><?php endforeach; ?>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="t" value="<?= e($token) ?>">

        <p class="porta__campo">
          <label class="campo__rótulo" for="password">Palavra-passe</label>
          <span class="campo__ajuda">Pelo menos 8 caracteres.</span>
          <input class="campo" type="password" id="password" name="password"
                 required minlength="8" autocomplete="new-password" autofocus>
        </p>

        <p class="porta__campo">
          <label class="campo__rótulo" for="password_confirm">Outra vez, para confirmar</label>
          <input class="campo" type="password" id="password_confirm" name="password_confirm"
                 required minlength="8" autocomplete="new-password">
        </p>

        <button type="submit" class="botão botão--cheio botão--largo">
          <span><?= $isFirst ? 'Definir e entrar' : 'Guardar e entrar' ?></span>
        </button>
      </form>

      <p class="porta__pé">
        <a href="<?= e(CustomerAuth::loginPath()) ?>">&larr; Voltar a entrar</a>
      </p>
    </div>

    <?php partial('partials/conta-lado'); ?>
  </div>
</section>
