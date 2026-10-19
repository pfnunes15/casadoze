<?php
/**
 * Finalizar a compra: contacto, envio, pagamento.
 *
 * Três passos numa página só, como na maquete — ver `docs/maquete/Loja.dc.html`,
 * ecrã «Checkout». **Mostra; não cobra.** O formulário submete para o
 * `POST /checkout` do pacote, que é quem conta o dinheiro, escreve a encomenda
 * por pagar e entrega quem compra a quem recebe o pagamento.
 *
 * ---------------------------------------------------------------------------
 * **Três coisas da maquete que esta página não faz, e porquê.**
 *
 * 1. *Os campos do cartão.* A maquete desenha o número, a validade e o CVC
 *    dentro desta página. **Não se faz.** Um número de cartão escrito num
 *    formulário nosso passa pelo nosso servidor, e isso põe este site dentro do
 *    âmbito do PCI-DSS — auditorias, varrimentos, responsabilidade. Quem recebe
 *    o pagamento tem uma página própria, encriptada e certificada para isso, e
 *    é para lá que este botão leva. É a diferença entre desenhar um cofre e
 *    guardar dinheiro nele.
 *
 * 2. *A morada de entrega.* Pela mesma porta: a página de quem recebe o
 *    pagamento pede a morada e devolve-a com a confirmação, e é de lá que ela
 *    entra nas colunas `ship_*` da encomenda. Pedi-la aqui era pedir duas
 *    vezes a mesma coisa e ficar com duas versões dela. O dia em que a Casa
 *    tiver Multibanco ou MB WAY — que não têm página própria — esta página
 *    passa a ser o sítio certo para a pedir. Ver `docs/maquete/NOTAS.md`.
 *
 * 3. *Criar conta ao finalizar, e os pontos de entrega dos CTT.* A Conta não
 *    existe ainda, e seguir encomendas nos CTT precisa de contrato.
 * ---------------------------------------------------------------------------
 *
 * @var array $cart
 * @var array $discount
 * @var array $shipping
 * @var int   $vat
 * @var array $gateways  os que têm com que cobrar
 * @var bool  $canSell
 */

use Admedia\Shop\Models\Attribute;
use Admedia\Shop\Shop;

$bodyClass = 'com-cabeçalho';
$anúncio   = true;
$pageTitle = __('loja.cart.checkout');

$linhas   = $cart['lines'];
$subtotal = (int)$cart['subtotal_cents'];
$desconto = $discount ?? ['lines' => [], 'cents' => 0, 'free_shipping' => false, 'code' => ''];

$zonaPredefinida = $shipping[0] ?? null;
$envio = $zonaPredefinida === null ? 0 : (int)$zonaPredefinida['cents'];
$total = $subtotal - (int)$desconto['cents'] + $envio;

$desenhos = [];
foreach ($linhas as $linha) {
    $pid = (int)$linha['product']['id'];
    foreach (Attribute::forProduct($pid) as $c) {
        if (mb_strtolower((string)$c['name']) === 'desenho') {
            $desenhos[$pid] = (string)$c['value'];
        }
    }
}

$passo = 0;
?>
<div class="caixa">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(Shop::to('cart')) ?>"><?= e(__('shop.cart.title')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e(__('loja.cart.checkout')) ?></span>
  </nav>

  <div class="caixa__par">

    <form class="caixa__passos" method="post" action="<?= e(Shop::to('checkout')) ?>">
      <?= csrf_field() ?>

      <section class="passo">
        <h2 class="passo__título"><span class="passo__número">I</span><?= e(__('loja.step.contact')) ?></h2>

        <label class="fora-do-ecrã" for="co-email"><?= e(__('shop.checkout.email')) ?></label>
        <input class="campo" id="co-email" type="email" name="email" required autocomplete="email"
               placeholder="<?= e(__('shop.checkout.email_hint')) ?>" value="<?= e((string)old('email')) ?>">
        <span class="passo__nota"><?= e(__('loja.step.contact.why')) ?></span>
      </section>

      <section class="passo">
        <h2 class="passo__título"><span class="passo__número">II</span><?= e(__('loja.step.shipping')) ?></h2>

        <?php if ($shipping === []): ?>
          <p class="passo__falta"><?= e(__('shop.checkout.no_zones')) ?></p>
        <?php else: ?>
          <fieldset class="zonas">
            <legend class="fora-do-ecrã"><?= e(__('shop.checkout.ship_where')) ?></legend>
            <?php foreach ($shipping as $i => $zona): ?>
              <label class="zona">
                <input type="radio" name="shipping" value="<?= e((string)$zona['key']) ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                <span class="zona__ponto" aria-hidden="true"></span>
                <span class="zona__dizer">
                  <span class="zona__nome"><?= e((string)$zona['label']) ?></span>
                  <?php if (trim((string)($zona['note'] ?? '')) !== ''): ?>
                    <span class="zona__nota"><?= e((string)$zona['note']) ?></span>
                  <?php endif; ?>
                </span>
                <span class="zona__preço">
                  <?= (int)$zona['cents'] === 0 ? e(__('shop.free')) : e(money((int)$zona['cents'])) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </fieldset>
        <?php endif; ?>

        <?php /* A morada é pedida na página de quem recebe o pagamento, e vem
                 de lá com a confirmação. Dito e não escondido: quem está a
                 finalizar tem de saber onde é que lhe vai ser pedida. */ ?>
        <span class="passo__nota"><?= e(__('loja.step.shipping.address')) ?></span>

        <?php $discreta = trim((string)setting('shipping.discreet', '')); ?>
        <?php if ($discreta !== ''): ?>
          <span class="passo__caixa">
            <svg viewBox="0 0 40 40" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                <path d="M6 12l14-6 14 6v16l-14 6-14-6z"></path>
                <path d="M6 12l14 6 14-6"></path>
              </g>
            </svg>
            <?= e($discreta) ?>
          </span>
        <?php endif; ?>

        <?php if (trim((string)setting('shop.reverse_charge')) === '1'): ?>
          <label class="campo__rótulo" for="co-nif"><?= e(__('shop.checkout.vat_number')) ?></label>
          <input class="campo" id="co-nif" type="text" name="vat_number" maxlength="32" autocomplete="off"
                 placeholder="<?= e(__('shop.checkout.vat_number_hint')) ?>" value="<?= e((string)old('vat_number')) ?>">
        <?php endif; ?>

        <label class="campo__rótulo" for="co-nota"><?= e(__('shop.checkout.note')) ?></label>
        <textarea class="campo campo--prosa" id="co-nota" name="note" rows="3"
                  placeholder="<?= e(__('shop.checkout.note_hint')) ?>"><?= e((string)old('note')) ?></textarea>
      </section>

      <section class="passo">
        <h2 class="passo__título"><span class="passo__número">III</span><?= e(__('loja.step.payment')) ?></h2>

        <?php if ($gateways === []): ?>
          <p class="passo__falta"><?= e(__('loja.cart.cannot_sell')) ?></p>
        <?php else: ?>
          <ul class="pagamentos">
            <?php foreach ($gateways as $g): ?>
              <li class="pagamento">
                <span class="pagamento__nome"><?= e($g->label()) ?></span>
                <span class="pagamento__nota"><?= e(__('loja.step.payment.hosted')) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php /* Um cadeado e o nome de quem cobra. Não é enfeite: é a
                   resposta à pergunta que uma pessoa faz antes de escrever um
                   cartão, e a resposta é que não o escreve aqui. */ ?>
          <span class="pagamentos__seguro">
            <svg viewBox="0 0 40 40" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 18V13a9 9 0 0 1 18 0v5"></path>
                <path d="M8 18h24v16H8z"></path>
              </g>
            </svg>
            <?= e(__('shop.checkout.secure')) ?>
          </span>
        <?php endif; ?>
      </section>

      <section class="passo passo--fim">
        <label class="aceito">
          <input type="checkbox" name="terms" value="1" required>
          <span class="aceito__marca" aria-hidden="true"></span>
          <span><?= e(__('loja.step.terms')) ?></span>
        </label>

        <?php if ($canSell): ?>
          <button type="submit" class="caixa__botão"><?= e(__('loja.step.place')) ?> &middot; <?= e(money($total)) ?></button>
        <?php else: ?>
          <p class="resumo__fechada"><?= e(__('loja.cart.cannot_sell')) ?></p>
        <?php endif; ?>

        <span class="caixa__ral"><?= e(__('loja.step.ral')) ?></span>
      </section>
    </form>

    <aside class="resumo">
      <div class="resumo__topo">
        <span class="resumo__título"><?= e(__('loja.step.your_order')) ?></span>
        <a class="resumo__mudar" href="<?= e(Shop::to('cart')) ?>"><?= e(__('loja.step.change')) ?></a>
      </div>

      <div class="resumo__linhas">
        <?php foreach ($linhas as $linha): ?>
          <?php $p = $linha['product']; $pid = (int)$p['id']; ?>
          <div class="resumo__linha">
            <span class="resumo__moldura">
              <?php if (($desenhos[$pid] ?? '') !== ''): ?>
                <canvas width="600" height="800" data-desenho="<?= e($desenhos[$pid]) ?>"
                        data-semente="<?= $pid ?>" aria-hidden="true"></canvas>
              <?php endif; ?>
              <span class="resumo__quantos"><?= (int)$linha['quantity'] ?></span>
            </span>
            <span class="resumo__nome">
              <?= e((string)$p['name']) ?>
              <?php if (($linha['variant_name'] ?? '') !== ''): ?>
                <span class="resumo__variante"><?= e((string)$linha['variant_name']) ?></span>
              <?php endif; ?>
            </span>
            <span class="resumo__valor"><?= e(money((int)$linha['line_cents'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <dl class="resumo__contas">
        <div>
          <dt><?= e(__('shop.totals.products')) ?></dt>
          <dd><?= e(money($subtotal)) ?></dd>
        </div>
        <?php foreach ($desconto['lines'] as $l): ?>
          <div class="resumo__desconto">
            <dt><?= e((string)$l['label']) ?></dt>
            <dd><?= $l['free_shipping'] ? e(__('shop.totals.free_shipping')) : '&minus;' . e(money((int)$l['cents'])) ?></dd>
          </div>
        <?php endforeach; ?>
        <div>
          <dt><?= e(__('shop.totals.shipping')) ?></dt>
          <dd><?= $zonaPredefinida === null
                ? e(__('loja.cart.ship_next'))
                : ($envio === 0 ? e(__('shop.free')) : e(money($envio))) ?></dd>
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
    </aside>
  </div>
</div>
