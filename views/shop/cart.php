<?php
/**
 * O cesto.
 *
 * Substitui a vista do pacote. Ver `docs/maquete/Loja.dc.html`, ecrã
 * «Carrinho»: as linhas à esquerda, o resumo numa caixa à direita que se cola
 * ao ecrã, e o botão que leva ao checkout.
 *
 * **A diferença de forma em relação ao pacote está onde se escreve o e-mail.**
 * A vista do pacote pede aqui o e-mail, a zona de envio e a nota, e manda tudo
 * de uma vez para o pagamento. O desenho desta casa parte isso em dois: o cesto
 * mostra o que lá está e quanto é, e o checkout — três passos, numa página
 * própria — pede quem é, para onde vai e como se paga. O motor é o mesmo: o
 * `POST /checkout` continua a ser o do pacote, e é lá que o dinheiro é contado.
 *
 * A barra dos portes grátis **só aparece quando a Casa disser a partir de
 * quanto**, em Definições > Portes. Uma barra de progresso para uma meta que
 * ninguém definiu é um enfeite que mente.
 *
 * @var array $cart
 * @var array $discount
 * @var array $shipping  as zonas, já com preço para este subtotal
 * @var int   $vat
 * @var bool  $canSell
 */

use Admedia\Shop\Models\Attribute;
use Admedia\Shop\Shipping;
use Admedia\Shop\Shop;

$bodyClass = 'com-cabeçalho';
$anúncio   = true;

$pageTitle = __('shop.cart.title');

$linhas   = $cart['lines'];
$subtotal = (int)$cart['subtotal_cents'];
$poupado  = (int)($cart['saved_cents'] ?? 0);
$desconto = $discount ?? ['lines' => [], 'cents' => 0, 'free_shipping' => false, 'code' => ''];

/* O envio, como estimativa. A zona a sério escolhe-se no checkout; aqui mostra-
   se a primeira, que é a do Continente, para o total não aparecer sem ela. Sem
   zonas configuradas não se inventa um número: diz-se que é no passo seguinte. */
$zonaEstimada = $shipping[0] ?? null;
$envio = $zonaEstimada === null ? null : (int)$zonaEstimada['cents'];

$total = $subtotal - (int)$desconto['cents'] + (int)($envio ?? 0);

/* A barra dos portes grátis. */
$gratisAPartirDe = Shipping::freeFrom();
$faltam = $gratisAPartirDe === null ? null : max(0, $gratisAPartirDe - max(0, $subtotal - (int)$desconto['cents']));

/* O desenho de cada linha, para a moldura não ficar vazia. Uma pergunta por
   linha; um cesto tem meia dúzia. */
$desenhos = [];
foreach ($linhas as $linha) {
    $pid = (int)$linha['product']['id'];
    foreach (Attribute::forProduct($pid) as $c) {
        if (mb_strtolower((string)$c['name']) === 'desenho') {
            $desenhos[$pid] = (string)$c['value'];
        }
    }
}
?>
<div class="cesto">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(Shop::to('index')) ?>"><?= e(__('loja.title')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e(__('shop.cart.title')) ?></span>
  </nav>

  <h1 class="cesto__título"><?= e(__('loja.cart.title')) ?></h1>

  <?php if ((int)$cart['removed'] > 0): ?>
    <p class="cesto__aviso" role="status">
      <?= e(__('shop.cart.dropped_lines', ['count' => (int)$cart['removed']])) ?>
    </p>
  <?php endif; ?>

  <?php if ($linhas === []): ?>

    <div class="cesto__vazio">
      <svg viewBox="0 0 40 40" aria-hidden="true">
        <g fill="none" stroke="currentColor" stroke-width="1">
          <path d="M8 14 H32 L30 36 H10Z"></path>
          <path d="M14 14 V10 a6 6 0 0 1 12 0 V14"></path>
          <path d="M24 21 A5 5 0 1 0 26 28 A4 4 0 1 1 24 21Z"></path>
        </g>
      </svg>
      <span class="cesto__vazioTítulo"><?= e(__('loja.cart.empty')) ?></span>
      <span class="cesto__vazioDito"><?= e(__('loja.cart.empty.body')) ?></span>
      <a class="botão botão--ouro" href="<?= e(Shop::to('index')) ?>"><span><?= e(__('loja.cart.to_shop')) ?></span></a>
    </div>

  <?php else: ?>

    <?php /* Os formulários de tirar uma linha e de mexer no cupão vivem fora
             de tudo, vazios, e os botões apontam para eles com `form=`. Um
             formulário não pode conter outro, e sem isto carregar em Enter na
             caixa do código mandava a pessoa para o pagamento. */ ?>
    <form id="cesto-tirar" method="post" action="<?= e(Shop::to('cart_remove')) ?>"><?= csrf_field() ?></form>
    <form id="cupao-pôr"   method="post" action="<?= e(Shop::to('cart_coupon')) ?>"><?= csrf_field() ?></form>
    <form id="cupao-tirar" method="post" action="<?= e(Shop::to('cart_coupon_off')) ?>"><?= csrf_field() ?></form>

    <div class="cesto__par">

      <form class="cesto__linhas" method="post" action="<?= e(Shop::to('cart_update')) ?>" data-cesto>
        <?= csrf_field() ?>

        <?php if ($gratisAPartirDe !== null): ?>
          <div class="portes">
            <span class="portes__dito">
              <?= $faltam > 0
                    ? e(__('loja.cart.free_left', ['price' => money($faltam)]))
                    : e(__('loja.cart.free_yes')) ?>
            </span>
            <span class="portes__calha">
              <i style="width: <?= $gratisAPartirDe > 0
                    ? min(100, (int)round(max(0, $subtotal - (int)$desconto['cents']) / $gratisAPartirDe * 100))
                    : 100 ?>%"></i>
            </span>
          </div>
        <?php endif; ?>

        <?php foreach ($linhas as $linha): ?>
          <?php
          $p      = $linha['product'];
          $pid    = (int)$p['id'];
          $chave  = (string)($linha['key'] ?? $pid);
          $máximo = (int)($linha['variant']['stock'] ?? $p['stock']);
          ?>
          <div class="cesto__linha">
            <a class="cesto__moldura" href="<?= e(Shop::to('product', ['slug' => (string)$p['slug']])) ?>"
               tabindex="-1" aria-hidden="true">
              <?php if (trim((string)$p['image']) !== ''): ?>
                <img src="<?= e(media_url((string)$p['image'])) ?>" alt="" loading="lazy">
              <?php elseif (($desenhos[$pid] ?? '') !== ''): ?>
                <canvas width="600" height="800" data-desenho="<?= e($desenhos[$pid]) ?>"
                        data-semente="<?= $pid ?>" aria-hidden="true"></canvas>
              <?php endif; ?>
            </a>

            <div class="cesto__dizer">
              <a class="cesto__nome" href="<?= e(Shop::to('product', ['slug' => (string)$p['slug']])) ?>"><?= e((string)$p['name']) ?></a>

              <?php if (($linha['variant_name'] ?? '') !== ''): ?>
                <span class="cesto__variante"><?= e((string)$linha['variant_name']) ?></span>
              <?php endif; ?>

              <span class="cesto__unidade">
                <?php if (!empty($linha['was_cents'])): ?>
                  <s><?= e(money((int)$linha['was_cents'])) ?></s>
                <?php endif; ?>
                <?= e(money((int)$linha['unit_cents'])) ?> <?= e(__('shop.each')) ?>
              </span>

              <?php if ($linha['capped']): ?>
                <span class="cesto__nota"><?= e(__('shop.product.only_left', ['count' => (int)$linha['quantity']])) ?></span>
              <?php endif; ?>

              <div class="cesto__mexer">
                <div class="quantia quantia--pequena" data-quantia
                     data-menos="<?= e(__('loja.less')) ?>" data-mais="<?= e(__('loja.more')) ?>">
                  <label class="fora-do-ecrã" for="q<?= e($chave) ?>">
                    <?= e(__('shop.cart.quantity_of', ['name' => (string)$p['name']])) ?>
                  </label>
                  <input id="q<?= e($chave) ?>" class="quantia__campo" type="number"
                         name="quantity[<?= e($chave) ?>]" value="<?= (int)$linha['quantity'] ?>"
                         min="0" max="<?= max(0, $máximo) ?>" step="1" inputmode="numeric">
                </div>

                <?php /* Tirar é pôr a zero **e** um botão próprio: a zero é o
                         que o teclado já faz, e o botão é o que a maquete tem e
                         o que uma pessoa procura. */ ?>
                <button type="submit" form="cesto-tirar" class="cesto__tirar"
                        name="key" value="<?= e($chave) ?>"><?= e(__('shop.coupon.remove')) ?></button>
              </div>
            </div>

            <span class="cesto__total"><?= e(money((int)$linha['line_cents'])) ?></span>
          </div>
        <?php endforeach; ?>

        <?php /* Escondido pelo js/site.js, que passa a submeter quando a
                 quantidade muda. Sem JavaScript é este botão que guarda, e por
                 isso está no HTML e não é desenhado por ele. */ ?>
        <button type="submit" class="cesto__guardar" data-cesto-guardar><?= e(__('shop.cart.update_button')) ?></button>

        <a class="cesto__voltar" href="<?= e(Shop::to('index')) ?>"><?= e(__('loja.cart.keep_looking')) ?></a>
      </form>

      <aside class="resumo">
        <span class="resumo__título"><?= e(__('loja.cart.summary')) ?></span>

        <div class="resumo__cupão">
          <?php if ((string)$desconto['code'] !== ''): ?>
            <span class="resumo__cupãoPosto">
              <strong><?= e((string)$desconto['code']) ?></strong>
              <button type="submit" form="cupao-tirar" class="resumo__cupãoTirar"
                      aria-label="<?= e(__('shop.coupon.remove')) ?>">&#10005;</button>
            </span>
          <?php else: ?>
            <label class="fora-do-ecrã" for="cupao"><?= e(__('shop.coupon.label')) ?></label>
            <input id="cupao" type="text" name="code" form="cupao-pôr" autocomplete="off"
                   placeholder="<?= e(__('shop.coupon.placeholder')) ?>">
            <button type="submit" form="cupao-pôr" class="resumo__cupãoPôr"><?= e(__('shop.coupon.submit')) ?></button>
          <?php endif; ?>
        </div>

        <dl class="resumo__contas">
          <div>
            <dt><?= e(__('shop.totals.products')) ?></dt>
            <dd><?= e(money($subtotal)) ?></dd>
          </div>

          <?php if ($poupado > 0): ?>
            <div class="resumo__poupado">
              <dt><?= e(__('shop.totals.saved')) ?></dt>
              <dd>&minus;<?= e(money($poupado)) ?></dd>
            </div>
          <?php endif; ?>

          <?php foreach ($desconto['lines'] as $l): ?>
            <div class="resumo__desconto">
              <dt><?= e((string)$l['label']) ?></dt>
              <dd><?= $l['free_shipping'] ? e(__('shop.totals.free_shipping')) : '&minus;' . e(money((int)$l['cents'])) ?></dd>
            </div>
          <?php endforeach; ?>

          <div>
            <dt>
              <?= e(__('shop.totals.shipping')) ?>
              <?php if ($zonaEstimada !== null): ?>
                <span class="resumo__nota">&middot; <?= e(__('loja.cart.ship_estimate', ['zone' => (string)$zonaEstimada['label']])) ?></span>
              <?php endif; ?>
            </dt>
            <dd>
              <?php if ($envio === null): ?>
                <span class="resumo__nota"><?= e(__('loja.cart.ship_next')) ?></span>
              <?php else: ?>
                <?= $envio === 0 ? e(__('shop.free')) : e(money($envio)) ?>
              <?php endif; ?>
            </dd>
          </div>

          <?php if ($vat > 0): ?>
            <div class="resumo__iva">
              <dt><?= e(__('shop.totals.vat_included')) ?></dt>
              <dd><?= e(money($vat)) ?></dd>
            </div>
          <?php endif; ?>
        </dl>

        <div class="resumo__total">
          <span class="resumo__totalNome"><?= e(__('loja.cart.total')) ?></span>
          <span class="resumo__totalValor"><?= e(money($total)) ?></span>
        </div>

        <?php if ($canSell): ?>
          <a class="resumo__botão" href="<?= e(locale_url('/checkout')) ?>"><?= e(__('loja.cart.checkout')) ?></a>
        <?php else: ?>
          <?php /* Dito e não um botão morto: uma loja sem chaves de pagamento ou
                   sem zonas de envio pode ser vista, e quem a vê tem direito a
                   saber porque é que não consegue comprar. */ ?>
          <p class="resumo__fechada"><?= e(__('loja.cart.cannot_sell')) ?></p>
        <?php endif; ?>

        <ul class="resumo__promessas">
          <?php $discreta = trim((string)setting('shipping.discreet', '')); ?>
          <?php if ($discreta !== ''): ?>
            <li><?= e(__('loja.discreet')) ?></li>
          <?php endif; ?>
          <li><?= e(__('loja.cart.returns')) ?></li>
        </ul>
      </aside>
    </div>
  <?php endif; ?>
</div>
