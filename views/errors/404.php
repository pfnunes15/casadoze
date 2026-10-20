<?php
/**
 * A página que não existe.
 *
 * Ver `docs/maquete/404.dc.html`. A lua nova — a que não se vê — é o sinal, e
 * não um «404» grande: o número está lá para quem o procura, no topo, e quem
 * chegou aqui por engano precisa é de saber por onde sair.
 *
 * **Três saídas e não uma.** Quem cai num 404 vinha a caminho de alguma coisa:
 * a loja, uma consulta, ou a porta de entrada. Uma só ligação para a raiz
 * obriga a recomeçar a procura do princípio.
 *
 * Substitui a vista do pacote, que é a mesma em todos os sites.
 *
 * @var \Admedia\Core\App $app
 */

use Admedia\Shop\Shop;

$bodyClass = 'com-cabeçalho';
$pageTitle = __('error.404.title');
?>
<div class="perdido">
  <svg class="perdido__lua" viewBox="0 0 80 80" aria-hidden="true">
    <circle cx="40" cy="40" r="36" fill="none" stroke="rgba(217,179,108,.35)" stroke-width="1"></circle>
    <circle cx="40" cy="40" r="30" fill="#0e0b12"></circle>
    <circle cx="58" cy="26" r="1.6" fill="#a77be0"></circle>
    <circle cx="24" cy="52" r="1.2" fill="#a77be0"></circle>
  </svg>

  <span class="perdido__sobrescrita"><?= e(__('error.404.eyebrow')) ?></span>
  <h1 class="perdido__título"><?= e(__('error.404.heading')) ?></h1>
  <p class="perdido__dito"><?= e(__('error.404.text')) ?></p>

  <div class="perdido__saídas">
    <?php if ($app->hasShop()): ?>
      <a class="botão botão--ouro" href="<?= e(Shop::to('index')) ?>"><span><?= e(__('loja.cart.to_shop')) ?></span></a>
    <?php endif; ?>
    <a class="botão" href="<?= e(locale_url('/consultas')) ?>"><span><?= e(__('consultas.book')) ?></span></a>
    <a class="perdido__raiz" href="<?= e(locale_url('/')) ?>"><?= e(__('error.home')) ?></a>
  </div>
</div>
