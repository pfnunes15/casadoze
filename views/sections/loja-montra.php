<?php
/**
 * A montra da loja, numa página de conteúdo.
 *
 * **Mostra produtos; não os guarda.** Os produtos estão no catálogo, em Loja, com
 * o preço, o stock e a fotografia, e é de lá que esta lista vem. Na maquete
 * estavam escritos à mão dentro da página, e isso queria dizer que mudar um preço
 * era mexer em todas as páginas onde ele aparecia — e que o stock da montra não
 * tinha nada a ver com o stock que a loja contava.
 *
 * O botão «Adicionar» é um formulário e não uma ligação, porque adicionar ao
 * cesto muda coisas: um GET que muda estado é um GET que o browser repete ao
 * recarregar, e dois produtos entravam no cesto por quem carregou em F5.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 * @var bool   $eager
 * @var \Admedia\Core\App $app
 */

/* Sem loja não há montra. Um site do mesmo CMS sem `config/shop.php` não tem as
   classes da loja para chamar, e chamá-las rebentava a página toda — não só este
   bloco. Ver App::hasShop. */
if (!$app->hasShop()) {
    return;
}

$opções   = \Admedia\Cms\Models\PageSection::options($section);
$quantos  = max(2, min(24, (int)($opções['limit'] ?? 8)));
$categoria = trim((string)($opções['category'] ?? ''));

/* De uma categoria ou de todas. `withDescendants` e não só a categoria em si:
   quem escolhe «Velas & Óleos» quer o que está lá dentro, e uma subcategoria que
   aparecesse vazia era um buraco que ninguém explicava. */
if ($categoria !== '') {
    $cat = \Admedia\Shop\Models\Category::findBySlug($categoria);
    $produtos = $cat === null
        ? []
        : \Admedia\Shop\Models\Product::inCategories(
            \Admedia\Shop\Models\Category::withDescendants((int)$cat['id'])
          );
} else {
    $produtos = \Admedia\Shop\Models\Product::published();
}

$produtos = array_slice($produtos, 0, $quantos);

// O botão para a loja inteira. Vazio usa o endereço da loja deste site, que está
// em config/shop.php — escrevê-lo à mão aqui era tê-lo em dois sítios.
$botãoTexto = trim((string)($section['cta_label'] ?? ''));
$botãoUrl   = trim((string)($section['cta_url'] ?? '')) ?: \Admedia\Shop\Shop::to('index');
?>
<section class="secção"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">

    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <p class="sobrescrita"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>

    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> style="margin-block: 18px 16px"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
      <p style="max-width: 640px; color: var(--letra-fraca)"><?= nl2br(e((string)$section['body'])) ?></p>
    <?php endif; ?>

    <?php if ($produtos === []): ?>
      <?php /* A montra vazia diz-se. Um bloco que desaparecesse deixava o editor
               a pensar que o tinha configurado mal, e um visitante a não ver
               nada entre dois títulos. */ ?>
      <p class="agenda__vazia" style="margin-top: 28px"><?= e(__('shop.empty')) ?></p>
    <?php else: ?>

    <div class="grelha" style="margin-top: 32px">
      <?php foreach ($produtos as $produto): ?>
        <?php
        $disponível = \Admedia\Shop\Models\Product::isAvailable($produto);
        $ficha      = \Admedia\Shop\Shop::to('product', ['slug' => $produto['slug']]);
        ?>
        <article class="cartão">
          <?php if (trim((string)$produto['image']) !== ''): ?>
            <a href="<?= e($ficha) ?>" tabindex="-1" aria-hidden="true">
              <?php /* `loading` e `fetchpriority` conforme o bloco: as imagens do
                       primeiro bloco são o que quem chega está à espera; as de
                       baixo não devem competir com elas. */ ?>
              <img src="<?= e(media_url($produto['image'])) ?>" alt=""
                   loading="<?= $eager ? 'eager' : 'lazy' ?>"
                   <?= $eager ? '' : 'decoding="async"' ?>
                   style="aspect-ratio: 4/5; object-fit: cover; width: 100%">
            </a>
          <?php endif; ?>

          <<?= $itemTag ?> class="cartão__nome">
            <a href="<?= e($ficha) ?>" style="color: inherit"><?= e((string)$produto['name']) ?></a>
          </<?= $itemTag ?>>

          <?php if (trim((string)($produto['summary'] ?? '')) !== ''): ?>
            <p class="cartão__dito"><?= e((string)$produto['summary']) ?></p>
          <?php endif; ?>

          <div class="cartão__pé">
            <span class="cartão__linha"><?= e(money((int)$produto['price_cents'])) ?></span>

            <?php if ($disponível): ?>
              <?php /* O formulário leva para onde voltar: sem isto, adicionar da
                       montra deixava a pessoa na página do cesto, longe do sítio
                       onde estava a escolher. */ ?>
              <form method="post" action="<?= e(\Admedia\Shop\Shop::to('cart_add')) ?>" style="margin-left: auto">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int)$produto['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="back" value="<?= e(\Admedia\Core\Locales::path()) ?>">
                <button type="submit" class="botão"><?= e(__('shop.product.add')) ?></button>
              </form>
            <?php else: ?>
              <span class="cartão__ordinal" style="margin-left: auto"><?= e(__('shop.sold_out')) ?></span>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($botãoTexto !== ''): ?>
      <p style="margin-top: 32px">
        <a class="botão" href="<?= e($botãoUrl) ?>"><?= e($botãoTexto) ?></a>
      </p>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</section>
