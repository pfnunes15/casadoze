<?php
/**
 * A barra dourada do topo da loja.
 *
 * Três frases que rodam, como na maquete — ver `docs/maquete/Loja.dc.html`.
 * **Duas saem do que a loja já sabe** e não de um campo que alguém tem de ir
 * manter: os portes grátis saem do valor em Definições > Portes, e a embalagem
 * discreta da frase que a Casa lá escreveu. A terceira é a que muda com a época
 * e essa é escrita, em Definições > Loja.
 *
 * Sem nenhuma das três não há barra. Uma faixa dourada vazia no topo da página
 * é um erro visível a toda a largura do ecrã.
 *
 * Sem JavaScript fica a primeira, que é a que interessa mais.
 *
 * @var \Admedia\Core\App $app
 */

use Admedia\Shop\Shipping;

$frases = [];

if ($app->hasShop() && Shipping::freeFrom() !== null) {
    $frases[] = __('loja.announce.free', ['price' => money((int)Shipping::freeFrom())]);
}

$escrita = trim((string)setting('shop.announce', ''));
if ($escrita !== '') {
    $frases[] = $escrita;
}

$discreta = trim((string)setting('shipping.discreet', ''));
if ($discreta !== '') {
    $frases[] = __('loja.discreet');
}

if ($frases === []) {
    return;
}
?>
<div class="anúncio" data-anúncio>
  <?php foreach ($frases as $i => $frase): ?>
    <span class="anúncio__frase<?= $i === 0 ? ' anúncio__frase--posta' : '' ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>><?= e($frase) ?></span>
  <?php endforeach; ?>
</div>
