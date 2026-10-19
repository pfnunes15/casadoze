<?php
/**
 * A gaveta do cesto: o que lá está, sem sair da página.
 *
 * **Um pedaço de página e não uma página.** É pedido pelo js/site.js depois de
 * juntar uma peça, e entra dentro do `<aside>` que já está no documento. Por
 * isso não tem cabeçalho, nem rodapé, nem layout — ver GavetaController.
 *
 * O desenho está em `docs/maquete/Landing Page.dc.html`, no `<aside
 * aria-label="Carrinho">`: as linhas, a barra dos portes grátis, o subtotal, e
 * os dois botões do fim.
 *
 * @var array $cart
 * @var int   $gratisAPartirDe  0 se a Casa não o tiver dito
 * @var array $desenhos
 */

use Admedia\Shop\Shop;

$linhas   = $cart['lines'];
$subtotal = (int)$cart['subtotal_cents'];
$faltam   = $gratisAPartirDe > 0 ? max(0, $gratisAPartirDe - $subtotal) : 0;
?>
<div class="gaveta__topo">
  <span class="gaveta__título">
    <?= e(__('site.cart')) ?>
    <span class="gaveta__conta"><?= (int)$cart['count'] ?></span>
  </span>
  <button type="button" class="gaveta__fechar" data-fechar-gaveta
          aria-label="<?= e(__('site.menu_close')) ?>">&#10005;</button>
</div>

<?php if ($linhas === []): ?>

  <div class="gaveta__vazio">
    <svg viewBox="0 0 40 40" aria-hidden="true">
      <g fill="none" stroke="currentColor" stroke-width="1">
        <path d="M8 14 H32 L30 36 H10Z"></path>
        <path d="M14 14 V10 a6 6 0 0 1 12 0 V14"></path>
      </g>
    </svg>
    <span class="gaveta__vazioTítulo"><?= e(__('loja.cart.empty')) ?></span>
    <a class="gaveta__botão" href="<?= e(Shop::to('index')) ?>"><?= e(__('loja.cart.to_shop')) ?></a>
  </div>

<?php else: ?>

  <div class="gaveta__corpo">
    <?php if ($gratisAPartirDe > 0): ?>
      <div class="portes portes--gaveta">
        <span class="portes__dito">
          <?= $faltam > 0
                ? e(__('loja.cart.free_left', ['price' => money($faltam)]))
                : e(__('loja.cart.free_yes')) ?>
        </span>
        <span class="portes__calha">
          <i style="width: <?= min(100, (int)round($subtotal / $gratisAPartirDe * 100)) ?>%"></i>
        </span>
      </div>
    <?php endif; ?>

    <?php foreach ($linhas as $linha): ?>
      <?php
      $p     = $linha['product'];
      $pid   = (int)$p['id'];
      $ficha = Shop::to('product', ['slug' => (string)$p['slug']]);
      ?>
      <div class="gaveta__linha">
        <a class="gaveta__moldura" href="<?= e($ficha) ?>" tabindex="-1" aria-hidden="true">
          <?php if (trim((string)$p['image']) !== ''): ?>
            <img src="<?= e(media_url((string)$p['image'])) ?>" alt="" loading="lazy">
          <?php elseif (($desenhos[$pid] ?? '') !== ''): ?>
            <canvas width="600" height="800" data-desenho="<?= e($desenhos[$pid]) ?>"
                    data-semente="<?= $pid ?>" aria-hidden="true"></canvas>
          <?php endif; ?>
        </a>

        <div class="gaveta__dizer">
          <a class="gaveta__nome" href="<?= e($ficha) ?>"><?= e((string)$p['name']) ?></a>
          <?php if (($linha['variant_name'] ?? '') !== ''): ?>
            <span class="gaveta__variante"><?= e((string)$linha['variant_name']) ?></span>
          <?php endif; ?>
          <span class="gaveta__quantos"><?= (int)$linha['quantity'] ?> &times; <?= e(money((int)$linha['unit_cents'])) ?></span>
        </div>

        <span class="gaveta__valor"><?= e(money((int)$linha['line_cents'])) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="gaveta__pé">
    <div class="gaveta__soma">
      <span><?= e(__('shop.totals.products')) ?></span>
      <span class="gaveta__subtotal"><?= e(money($subtotal)) ?></span>
    </div>
    <span class="gaveta__nota"><?= e(__('loja.drawer.next')) ?></span>
    <a class="gaveta__botão" href="<?= e(locale_url('/checkout')) ?>"><?= e(__('loja.cart.checkout')) ?></a>
    <a class="gaveta__botão gaveta__botão--vazio" href="<?= e(Shop::to('cart')) ?>"><?= e(__('loja.drawer.see')) ?></a>
  </div>

<?php endif; ?>
