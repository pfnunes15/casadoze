<?php
/**
 * Entrar / Criar conta.
 *
 * Links and not JavaScript tabs: they are two addresses, each with a form that
 * posts somewhere different, and a tab that swaps a form on the spot is a tab
 * that loses what was typed when the page comes back with an error.
 *
 * @var string $atual  'entrar' or 'registar'
 */
use Admedia\Core\Auth;
use Admedia\Core\Accounts\CustomerAuth;

$registar = locale_url(Auth::doors()['customer_signup'] ?? '/conta/registar');
?>
<div class="porta__abas">
  <a class="porta__aba<?= $atual === 'entrar' ? ' porta__aba--aqui' : '' ?>"
     href="<?= e(CustomerAuth::loginPath()) ?>"<?= $atual === 'entrar' ? ' aria-current="page"' : '' ?>>Entrar</a>
  <a class="porta__aba<?= $atual === 'registar' ? ' porta__aba--aqui' : '' ?>"
     href="<?= e($registar) ?>"<?= $atual === 'registar' ? ' aria-current="page"' : '' ?>>Criar conta</a>
</div>
