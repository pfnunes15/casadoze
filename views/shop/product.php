<?php
/**
 * A ficha de uma peça.
 *
 * Substitui a vista do pacote porque o desenho desta casa não é o de um
 * catálogo qualquer. Ver `docs/maquete/Loja.dc.html`, ecrã «Produto».
 *
 * **A peça não tem fotografia: tem desenho.** Cada uma diz, numa característica
 * chamada «Desenho», qual das oito ilustrações leva, e quem as pinta é
 * js/arte-produtos.js num canvas de 600 por 800, com o `id` por semente para
 * sair sempre igual. Onde houver fotografia carregada no backoffice é a
 * fotografia que manda — um desenho é o que se põe enquanto não há a coisa.
 *
 * **O que a maquete tem e esta página não tem, por falta de dados:**
 *
 * - *As estrelas e a secção das avaliações.* Não há tabela de avaliações. Uma
 *   média inventada é um número que ninguém disse. Ver `docs/maquete/NOTAS.md`.
 * - *A galeria de quatro miniaturas.* Na maquete são quatro enquadramentos do
 *   mesmo desenho, mais um lugar marcado para a «[Foto da embalagem discreta]»
 *   que o cliente há-de dar. Quatro recortes do mesmo risco é uma galeria a
 *   fingir; fica o desenho, e as miniaturas voltam quando houver fotografias.
 * - *«No Grimório», com o vídeo de como usar.* As sessões ainda não existem no
 *   site — ver a página `Grimorio` do desenho.
 * - *O «avisa-me quando voltar», no esgotado.* Recolher o e-mail sem ter onde o
 *   guardar nem quem o mande é prometer um aviso que nunca chega. O aviso de
 *   que a Casa está a preparar mais fica; a caixa de texto não.
 *
 * @var array $product
 * @var array $attributes
 * @var array $categories  as desta peça
 * @var array $tags
 * @var array $related
 * @var array $variants
 * @var array $cart
 */

use Admedia\Shop\Models\Variant;
use Admedia\Shop\Shop;
use App\Services\Lua;
use App\Services\Sigilos;

/* O cabeçalho é fixo e transparente, e isso só funciona por cima de uma abertura
   de ecrã inteiro. Esta página não tem nenhuma, por isso leva o espaço em cima e
   o cabeçalho deixa de ser transparente — ver `.com-cabeçalho` na folha de
   estilo, e views/pages/composed.php, que faz o mesmo às páginas de blocos. */
$bodyClass = 'com-cabeçalho';

$id   = (int)$product['id'];
$nome = (string)$product['name'];

$pageTitle       = $nome;
$metaDescription = mb_substr(trim(strip_tags((string)($product['summary'] ?: $product['description']))), 0, 160);

/* As características, por nome em minúsculas: o backoffice deixa escrever
   «Desenho» ou «desenho», e as duas são a mesma coisa. */
$caraterísticas = [];
foreach ($attributes as $c) {
    $caraterísticas[mb_strtolower((string)$c['name'])] = (string)$c['value'];
}

$desenho = $caraterísticas['desenho'] ?? '';
$lua     = $caraterísticas['lua'] ?? '';
$faseDaLua = $lua !== '' ? Lua::faseDoNome($lua) : null;

/* O que fica na tabela das características: tudo menos as duas que a página já
   desenha noutro sítio. Repetir «Lua: Lua cheia» numa linha da tabela por baixo
   do selo que já a desenha é dizer duas vezes a mesma coisa. */
$paraATabela = [];
foreach ($attributes as $c) {
    if (in_array(mb_strtolower((string)$c['name']), ['desenho', 'lua'], true)) {
        continue;
    }
    $paraATabela[] = $c;
}

$categoria = $categories[0] ?? null;
$sigilos   = Sigilos::paraEtiquetas();

$esgotado = (int)$product['stock'] < 1;
$poucas   = !$esgotado && (int)$product['stock'] <= 3;

$imagem = trim((string)($product['image'] ?? ''));

/* O preço. Com variantes é o da mais barata, e a maquete escreve-o «desde»;
   sem elas é o da peça. */
$preçoBase = (int)$product['price_cents'];
$maisBarata = $preçoBase;
foreach ($variants as $v) {
    $maisBarata = min($maisBarata, Variant::priceOf($v, $product));
}
$temVariantes = $variants !== [];
?>
<?php /* Um `<div>` e não um `<main>`: o `<main id="conteudo">` é do layout. */ ?>
<div class="peça">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(Shop::to('index')) ?>"><?= e(__('loja.title')) ?></a>
    <?php if ($categoria): ?>
      <span class="migalhas__traço" aria-hidden="true">/</span>
      <a href="<?= e(Shop::to('category', ['slug' => (string)$categoria['slug']])) ?>"><?= e((string)$categoria['name']) ?></a>
    <?php endif; ?>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e($nome) ?></span>
  </nav>

  <div class="peça__par">

    <?php /* A moldura. 4:5 e não 3:4 como no cartão da montra: aqui a peça é o
             assunto da página e tem espaço para o ser. */ ?>
    <div class="peça__moldura">
      <?php if ($imagem !== ''): ?>
        <img class="peça__foto" src="<?= e(media_url($imagem)) ?>"
             alt="<?= e((string)($product['image_alt'] ?: $nome)) ?>">
      <?php elseif ($desenho !== ''): ?>
        <canvas class="peça__desenho" width="600" height="800"
                data-desenho="<?= e($desenho) ?>" data-semente="<?= $id ?>"
                aria-hidden="true"></canvas>
      <?php endif; ?>

      <?php if ($esgotado): ?>
        <span class="produto__selo produto__selo--esgotado"><?= e(__('shop.product.sold_out')) ?></span>
      <?php elseif ($poucas): ?>
        <span class="produto__selo produto__selo--poucas"><?= e(__('loja.badge.few')) ?></span>
      <?php endif; ?>
    </div>

    <div class="peça__dizer">

      <div class="peça__cabeça">
        <?php if ($categoria): ?>
          <span class="peça__sobrescrita"><?= e((string)$categoria['name']) ?></span>
        <?php endif; ?>
        <h1 class="peça__nome"><?= e($nome) ?></h1>
      </div>

      <div class="peça__preço">
        <span class="peça__valor">
          <?= $temVariantes ? e(__('loja.from', ['price' => money($maisBarata)])) : e(money($preçoBase)) ?>
        </span>
        <span class="peça__iva"><?= e(__('loja.vat_included')) ?></span>
      </div>

      <?php if (trim((string)($product['description'] ?? '')) !== ''): ?>
        <div class="peça__prosa"><?= (string)$product['description'] ?></div>
      <?php elseif (trim((string)($product['summary'] ?? '')) !== ''): ?>
        <p class="peça__prosa"><?= e((string)$product['summary']) ?></p>
      <?php endif; ?>

      <?php /* Duas células com um fio de um pixel entre elas: o fundo do
               contentor é o fio, e cada célula tapa-o com o fundo da página. */ ?>
      <?php if ($tags !== [] || $lua !== ''): ?>
      <div class="peça__quadro">
        <?php if ($tags !== []): ?>
          <div class="peça__célula">
            <span class="peça__rótulo"><?= e(__('loja.filter.intent')) ?></span>
            <div class="peça__intenções">
              <?php foreach ($tags as $t): ?>
                <a class="peça__intenção" href="<?= e(locale_url('/intencao/' . rawurlencode((string)$t['slug']))) ?>">
                  <?php if (($sigilos[(string)$t['slug']] ?? '') !== ''): ?>
                    <svg viewBox="0 0 60 60" aria-hidden="true">
                      <path d="<?= e($sigilos[(string)$t['slug']]) ?>" fill="none" stroke="currentColor"
                            stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  <?php endif; ?>
                  <?= e((string)$t['name']) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($lua !== ''): ?>
          <div class="peça__célula">
            <span class="peça__rótulo"><?= e(__('loja.moon_for')) ?></span>
            <span class="peça__lua">
              <?php if ($faseDaLua !== null): ?>
                <svg class="peça__luaDesenho" viewBox="0 0 40 40" aria-hidden="true">
                  <circle cx="20" cy="20" r="17" fill="none" stroke="currentColor" stroke-width="1"></circle>
                  <path d="<?= e(Lua::desenho($faseDaLua)) ?>" fill="currentColor"></path>
                </svg>
              <?php endif; ?>
              <span class="peça__luaDizer">
                <span class="peça__luaNome"><?= e($lua) ?></span>
                <?php if ($faseDaLua !== null): ?>
                  <span class="peça__luaLuz"><?= e(__('loja.moon_light', ['n' => Lua::luzPorCento($faseDaLua)])) ?></span>
                <?php endif; ?>
              </span>
            </span>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($esgotado): ?>
        <?php /* Sem a caixa de «avisa-me quando voltar» da maquete: recolher um
                 e-mail sem ter onde o guardar nem quem o mande é prometer um
                 aviso que nunca chega. */ ?>
        <div class="peça__esgotado">
          <span class="peça__esgotadoRótulo"><?= e(__('loja.sold_out.more')) ?></span>
          <span class="peça__esgotadoDito"><?= e(__('loja.sold_out.body')) ?></span>
        </div>
      <?php else: ?>
        <form class="peça__comprar" method="post" action="<?= e(Shop::to('cart_add')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= $id ?>">

          <?php if ($temVariantes): ?>
            <?php /* Uma peça que se vende por tamanho não se compra sem um: o
                     controlador recusa, e aqui o `required` diz porquê antes de
                     o pedido sair. */ ?>
            <fieldset class="peça__variantes">
              <legend class="peça__rótulo"><?= e(__('loja.variant')) ?></legend>
              <?php foreach ($variants as $i => $v): ?>
                <label class="peça__variante">
                  <input type="radio" name="variant_id" value="<?= (int)$v['id'] ?>"
                         <?= $i === 0 ? 'checked' : '' ?> required>
                  <span><?= e((string)$v['name']) ?></span>
                  <span class="peça__varPreço"><?= e(money(Variant::priceOf($v, $product))) ?></span>
                </label>
              <?php endforeach; ?>
            </fieldset>
          <?php endif; ?>

          <div class="peça__linhaComprar">
            <?php /* A quantidade é um `<input type="number">` e não dois botões
                     com um número no meio: os botões da maquete são o desenho, e
                     quem escreve «3» com o teclado tem de poder. O js/site.js
                     põe-lhe os dois botões à volta. */ ?>
            <div class="quantia" data-quantia
                 data-menos="<?= e(__('loja.less')) ?>" data-mais="<?= e(__('loja.more')) ?>">
              <label class="fora-do-ecrã" for="quantia-<?= $id ?>"><?= e(__('loja.quantity')) ?></label>
              <input id="quantia-<?= $id ?>" class="quantia__campo" type="number" name="quantity"
                     value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>" step="1" inputmode="numeric">
            </div>

            <button type="submit" class="peça__botão"><?= e(__('loja.add')) ?></button>
          </div>

          <span class="peça__stock peça__stock--<?= $poucas ? 'poucas' : 'ha' ?>">
            <i aria-hidden="true"></i>
            <?= e($poucas
                ? __('loja.stock.few', ['n' => (int)$product['stock']])
                : __('loja.stock.yes')) ?>
          </span>
        </form>
      <?php endif; ?>

      <?php $discreta = trim((string)setting('shipping.discreet', '')); ?>
      <?php if ($discreta !== ''): ?>
        <div class="peça__caixa">
          <svg viewBox="0 0 40 40" aria-hidden="true">
            <g fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round">
              <path d="M6 12l14-6 14 6v16l-14 6-14-6z"></path>
              <path d="M6 12l14 6 14-6"></path>
              <path d="M20 18v16"></path>
            </g>
          </svg>
          <span class="peça__caixaDizer">
            <span class="peça__caixaNome"><?= e(__('loja.discreet')) ?></span>
            <span class="peça__caixaDito"><?= e($discreta) ?></span>
          </span>
        </div>
      <?php endif; ?>

      <?php if ($paraATabela !== []): ?>
        <div class="peça__tabela">
          <?php foreach ($paraATabela as $c): ?>
            <div class="peça__linha">
              <span class="peça__chave"><?= e((string)$c['name']) ?></span>
              <span class="peça__valorTabela"><?= e((string)$c['value']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($related !== []): ?>
    <?php /* «O que a Casa usa com esta peça»: os relacionados do catálogo. Na
             maquete cada um tem uma caixa que se tira e o total acompanha, e o
             botão junta o que ficou escolhido ao cesto de uma vez. É isso, e o
             `POST /carrinho/kit` é desta casa — o do pacote aceita uma peça de
             cada vez. */ ?>
    <section class="kit">
      <div class="kit__dizer">
        <span class="sobrescrita"><?= e(__('loja.kit.eyebrow')) ?></span>
        <h2 class="kit__título"><?= e(__('loja.kit.title')) ?></h2>
      </div>

      <form class="kit__caixa" method="post" action="<?= e(locale_url('/carrinho/kit')) ?>" data-kit>
        <?= csrf_field() ?>

        <div class="kit__peças">
          <?php foreach ($related as $rel): ?>
            <?php
            $relId = (int)$rel['id'];
            $relDesenho = '';
            foreach (\Admedia\Shop\Models\Attribute::forProduct($relId) as $c) {
                if (mb_strtolower((string)$c['name']) === 'desenho') { $relDesenho = (string)$c['value']; }
            }
            $relEsgotado = (int)$rel['stock'] < 1;
            ?>
            <div class="kit__peça<?= $relEsgotado ? ' kit__peça--fora' : '' ?>">
              <label class="kit__moldura">
                <input type="checkbox" name="product_id[]" value="<?= $relId ?>"
                       data-preco="<?= (int)$rel['price_cents'] ?>"
                       <?= $relEsgotado ? 'disabled' : 'checked' ?>>
                <span class="kit__marca" aria-hidden="true"></span>
                <?php if ($relDesenho !== ''): ?>
                  <canvas width="600" height="800" data-desenho="<?= e($relDesenho) ?>"
                          data-semente="<?= $relId ?>" aria-hidden="true"></canvas>
                <?php endif; ?>
              </label>
              <a class="kit__nome" href="<?= e(Shop::to('product', ['slug' => (string)$rel['slug']])) ?>"><?= e((string)$rel['name']) ?></a>
              <span class="kit__preço"><?= e(money((int)$rel['price_cents'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="kit__soma">
          <?php /* Os moldes das duas frases vão no HTML para a contagem ficar na
                   língua da página sem a repetir em JavaScript. */ ?>
          <span class="kit__conta" data-kit-conta
                data-um="<?= e(__('loja.kit.one', ['n' => '{n}'])) ?>"
                data-muitos="<?= e(__('loja.kit.many', ['n' => '{n}'])) ?>"><?= e(__(count($related) === 1 ? 'loja.kit.one' : 'loja.kit.many', ['n' => count($related)])) ?></span>
          <span class="kit__total" data-kit-total><?= e(money(array_sum(array_map(static fn(array $r): int => (int)$r['price_cents'], $related)))) ?></span>
          <button type="submit" class="kit__botão"><?= e(__('loja.kit.add')) ?></button>
        </div>
      </form>
    </section>
  <?php endif; ?>
</div>
