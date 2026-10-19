<?php
/**
 * Depois do pagamento.
 *
 * **Esta página não decide que alguma coisa foi paga.** Mostra o que o aviso de
 * quem recebeu o dinheiro já escreveu. Até esse aviso chegar diz que o
 * pagamento está a ser confirmado, que é verdade, em vez de agradecer dinheiro
 * que ninguém viu. É a regra do pacote e fica como está.
 *
 * Ver `docs/maquete/Loja.dc.html`, ecrã «Confirmação». O que de lá não vem:
 *
 * - *Os blocos do MB WAY e do Multibanco* — a referência, a entidade, o relógio
 *   das 48 horas. Não há esses meios de pagamento ligados. Ver
 *   `docs/maquete/NOTAS.md`.
 * - *«Seguir a encomenda»* — precisa de contrato com os CTT.
 * - *«Guardar numa conta»* — a Conta não existe.
 * - *«Enquanto esperas, vê o Grimório»* — as sessões não existem.
 *
 * @var array $order
 * @var array $items
 * @var bool  $webhookMissing
 */

use Admedia\Shop\Shop;

$bodyClass = 'com-cabeçalho';
$pageTitle = __('shop.thanks.title');

$pago = in_array((string)$order['status'], ['paid', 'shipped'], true);

/* Os quatro passos da maquete. Qual é o aceso sai do estado da encomenda e não
   de uma contagem de dias: uma linha do tempo que avança sozinha com o
   calendário diz «Enviada» de uma encomenda que está na prateleira. */
$ondeVai = match ((string)$order['status']) {
    'shipped' => 2,
    'paid'    => 1,
    default   => 0,
};

$passos = [
    ['nome' => __('loja.done.step1'), 'dito' => __('loja.done.step1.body')],
    ['nome' => __('loja.done.step2'), 'dito' => __('loja.done.step2.body')],
    ['nome' => __('loja.done.step3'), 'dito' => __('loja.done.step3.body')],
    ['nome' => __('loja.done.step4'), 'dito' => __('loja.done.step4.body')],
];

$quando = strtotime((string)$order['created_at']) ?: time();

/* A morada vem do aviso de quem recebeu o pagamento, e por isso só existe
   depois dele. Antes disso não se desenha uma caixa vazia. */
$morada = array_values(array_filter([
    trim((string)($order['name'] ?? '')),
    trim((string)($order['ship_line1'] ?? '')),
    trim((string)($order['ship_line2'] ?? '')),
    trim(trim((string)($order['ship_postcode'] ?? '')) . ' ' . trim((string)($order['ship_city'] ?? ''))),
]));
?>
<div class="feito">

  <div class="feito__cabeça">
    <?php /* A lua, que é o sinal da Casa. Não é um visto verde: uma encomenda
             que ainda não está paga com um visto por cima é uma mentira
             desenhada. */ ?>
    <svg class="feito__lua" viewBox="0 0 80 80" aria-hidden="true">
      <circle cx="40" cy="40" r="36" fill="none" stroke="rgba(217,179,108,.35)" stroke-width="1"></circle>
      <path d="M52 12 A30 30 0 1 0 68 58 A24 24 0 1 1 52 12Z" fill="#e8cf98"></path>
      <circle cx="62" cy="22" r="2" fill="#a77be0"></circle>
    </svg>

    <span class="feito__sobrescrita">
      <?= e(__('shop.thanks.order', ['reference' => (string)$order['reference']])) ?>
      &middot; <?= e(date('d/m/Y', $quando)) ?>
    </span>

    <h1 class="feito__título"><?= e(__($pago ? 'loja.done.title' : 'shop.thanks.almost')) ?></h1>

    <p class="feito__dito">
      <?php if ($pago): ?>
        <?= e(__('shop.thanks.paid')) ?>
        <?php if (trim((string)$order['email']) !== ''): ?>
          <?= e(__('shop.thanks.sent_to', ['email' => (string)$order['email']])) ?>
        <?php endif; ?>
      <?php else: ?>
        <?= e(__('shop.thanks.pending')) ?>
      <?php endif; ?>
    </p>

    <?php if (!$pago && $webhookMissing): ?>
      <?php /* Só o dono do site o pode resolver, e esconder a razão fazia a
               encomenda parecer perdida. */ ?>
      <p class="feito__aviso" role="status"><?= e(__('shop.thanks.no_webhook')) ?></p>
    <?php endif; ?>
  </div>

  <div class="feito__par">

    <section class="feito__encomenda">
      <div class="feito__linhas">
        <?php foreach ($items as $item): ?>
          <div class="feito__linha">
            <span class="feito__quantos"><?= (int)$item['quantity'] ?>&times;</span>
            <span class="feito__nome">
              <?= e((string)$item['name']) ?>
              <?php if (trim((string)($item['variant_name'] ?? '')) !== ''): ?>
                <span class="feito__variante"><?= e((string)$item['variant_name']) ?></span>
              <?php endif; ?>
            </span>
            <span class="feito__valor"><?= e(money((int)$item['line_cents'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <dl class="feito__contas">
        <div>
          <dt><?= e(__('shop.totals.products')) ?></dt>
          <dd><?= e(money((int)$order['subtotal_cents'])) ?></dd>
        </div>
        <?php if ((int)$order['discount_cents'] > 0): ?>
          <div class="feito__desconto">
            <dt><?= e(trim((string)$order['discount_label']) ?: __('shop.totals.saved')) ?></dt>
            <dd>&minus;<?= e(money((int)$order['discount_cents'])) ?></dd>
          </div>
        <?php endif; ?>
        <div>
          <dt><?= e(__('shop.totals.shipping')) ?></dt>
          <dd><?= (int)$order['ship_cents'] === 0 ? e(__('shop.free')) : e(money((int)$order['ship_cents'])) ?></dd>
        </div>
        <?php if ((int)$order['vat_cents'] > 0): ?>
          <div class="feito__iva">
            <dt><?= e(__('shop.totals.vat_included')) ?></dt>
            <dd><?= e(money((int)$order['vat_cents'])) ?></dd>
          </div>
        <?php endif; ?>
      </dl>

      <div class="feito__total">
        <span class="feito__totalNome"><?= e(__('loja.cart.total')) ?></span>
        <span class="feito__totalValor"><?= e(money((int)$order['total_cents'])) ?></span>
      </div>
    </section>

    <section class="feito__caminho">
      <h2 class="feito__caminhoTítulo"><?= e(__('loja.done.next')) ?></h2>

      <ol class="caminho">
        <?php foreach ($passos as $i => $p): ?>
          <li class="caminho__passo<?= $i <= $ondeVai ? ' caminho__passo--feito' : '' ?>">
            <span class="caminho__ponto" aria-hidden="true"></span>
            <span class="caminho__dizer">
              <span class="caminho__nome"><?= e($p['nome']) ?></span>
              <span class="caminho__dito"><?= e($p['dito']) ?></span>
            </span>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php if ($morada !== []): ?>
        <div class="feito__morada">
          <span class="feito__moradaRótulo"><?= e(__('loja.done.delivery')) ?></span>
          <address><?= nl2br(e(implode("\n", $morada))) ?></address>
          <?php $discreta = trim((string)setting('shipping.discreet', '')); ?>
          <?php if ($discreta !== ''): ?>
            <span class="feito__moradaNota"><?= e($discreta) ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <a class="botão botão--ouro" href="<?= e(Shop::to('index')) ?>"><span><?= e(__('loja.done.back')) ?></span></a>
    </section>
  </div>
</div>
