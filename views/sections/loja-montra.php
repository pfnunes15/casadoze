<?php
/**
 * A montra da loja, na porta de entrada.
 *
 * **Mostra produtos; não os guarda.** Os produtos estão no catálogo, com o
 * preço, o stock e as características, e é de lá que esta lista vem. Escritos à
 * mão dentro da página, mudar um preço era mexer em todas as páginas onde ele
 * aparecia — e o stock da montra não tinha nada a ver com o stock que a loja
 * conta.
 *
 * **Os produtos não têm fotografia: têm desenho.** Cada um diz, numa
 * característica chamada «Desenho», qual das oito ilustrações leva — a vela, o
 * frasco, o cacho de ametista. Quem as pinta é js/arte-produtos.js, num canvas
 * de 600 por 800, e a semente é o `id` do produto para o desenho sair sempre
 * igual. Um produto sem essa característica fica com a moldura vazia em vez de
 * rebentar, que é o que deve acontecer a uma falta de dados.
 *
 * **A fase da lua é um selo e não parte da frase.** Vem da característica «Lua».
 *
 * Os filtros por categoria filtram do lado do browser e não recarregam a página:
 * são oito cartões já desenhados, e ir buscar os mesmos oito ao servidor para
 * esconder quatro era uma viagem para nada. Sem JavaScript não aparecem — ver
 * js/site.js —, e o que fica é a montra inteira, que é a resposta certa.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 * @var bool   $eager
 * @var \Admedia\Core\App $app
 */

use Admedia\Shop\Models\Attribute;
use Admedia\Shop\Models\Category;
use Admedia\Shop\Models\Product;
use Admedia\Shop\Shop;

/* Sem loja não há montra. Um site do mesmo CMS sem `config/shop.php` não tem as
   classes da loja para chamar, e chamá-las rebentava a página toda. */
if (!$app->hasShop()) {
    return;
}

$opções    = \Admedia\Cms\Models\PageSection::options($section);
$quantos   = max(2, min(24, (int)($opções['limit'] ?? 8)));
$categoria = trim((string)($opções['category'] ?? ''));

if ($categoria !== '') {
    $cat = Category::findBySlug($categoria);
    $produtos = $cat === null ? [] : Product::inCategories(Category::withDescendants((int)$cat['id']));
} else {
    $produtos = Product::published();
}

$produtos = array_slice($produtos, 0, $quantos);

/* As categorias de cada produto, para os filtros saberem o que esconder. Uma
   pergunta por produto — oito para oito cartões —, e fica em memória: a fila dos
   filtros precisa da mesma resposta logo a seguir. */
$categorias = Category::tree(true);

$deCada = [];
$caraterísticas = [];
foreach ($produtos as $produto) {
    $id = (int)$produto['id'];

    $deCada[$id] = array_map(
        static fn(array $c): string => (string)$c['slug'],
        Category::forProduct($id)
    );

    $caraterísticas[$id] = [];
    foreach (Attribute::forProduct($id) as $c) {
        $caraterísticas[$id][mb_strtolower((string)$c['name'])] = (string)$c['value'];
    }
}

$botãoTexto = trim((string)($section['cta_label'] ?? ''));
$botãoUrl   = trim((string)($section['cta_url'] ?? '')) ?: Shop::to('index');
?>
<section class="secção secção--loja"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">

    <div class="montra__topo">
      <div class="montra__dizer">
        <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
          <p class="sobrescrita sobrescrita--larga" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if (trim((string)$section['heading']) !== ''): ?>
          <<?= $headingTag ?> data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
        <?php endif; ?>

        <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
          <p class="montra__entrada" data-entra="sobe" data-atraso="160"><?= nl2br(e((string)$section['body'])) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($categorias !== []): ?>
        <?php /* Escondida até o JavaScript a ligar: uma fila de filtros que não
                 filtram é pior do que fila nenhuma. */ ?>
        <div class="montra__filtros" role="group" aria-label="<?= e(__('shop.categories')) ?>"
             data-filtros data-entra="sobe" data-atraso="200" hidden>
          <button type="button" class="pastilha pastilha--activa" data-filtro="">
            <?= e(__('shop.all')) ?>
          </button>
          <?php foreach ($categorias as $c): ?>
            <button type="button" class="pastilha" data-filtro="<?= e((string)$c['slug']) ?>">
              <?= e((string)$c['name']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="montra" data-montra>
      <?php foreach ($produtos as $índice => $produto): ?>
        <?php
        $id      = (int)$produto['id'];
        $desenho = $caraterísticas[$id]['desenho'] ?? '';
        $lua     = $caraterísticas[$id]['lua'] ?? '';
        $esgotado = (int)$produto['stock'] < 1;
        ?>
        <article class="produto" data-entra="sobe" data-atraso="<?= ((int)$índice % 4) * 90 ?>"
                 data-categorias="<?= e(implode(' ', $deCada[$id] ?? [])) ?>">

          <div class="produto__moldura" data-inclina>
            <?php if ($desenho !== ''): ?>
              <?php /* O canvas é desenhado pelo browser e não traz nada escrito:
                       o que descreve o produto é o nome por baixo, que está em
                       texto. Daí o `aria-hidden`. */ ?>
              <canvas class="produto__desenho" width="600" height="800"
                      data-desenho="<?= e($desenho) ?>" data-semente="<?= $id ?>"
                      aria-hidden="true"></canvas>
            <?php endif; ?>

            <span class="produto__luz" aria-hidden="true"></span>

            <?php if ($lua !== ''): ?>
              <span class="produto__lua"><?= e($lua) ?></span>
            <?php endif; ?>
          </div>

          <div class="produto__linha">
            <div class="produto__nomes">
              <<?= $itemTag ?> class="produto__nome"><?= e((string)$produto['name']) ?></<?= $itemTag ?>>
              <?php if (trim((string)($produto['summary'] ?? '')) !== ''): ?>
                <span class="produto__dito"><?= e((string)$produto['summary']) ?></span>
              <?php endif; ?>
            </div>
            <span class="produto__preço"><?= e(money((int)$produto['price_cents'])) ?></span>
          </div>

          <?php if ($esgotado): ?>
            <span class="produto__esgotado"><?= e(__('shop.product.sold_out')) ?></span>
          <?php else: ?>
            <?php /* Conta no cesto do cabeçalho e não vai ao servidor, porque
                     neste site não há cesto para onde ir: a loja, o checkout e
                     as páginas de produto foram retiradas, e ficou a porta de
                     entrada. É também o que a maquete faz — lá o botão soma um
                     ao contador e anuncia-o, e mais nada.

                     `type="button"` de propósito: dentro de um formulário, um
                     botão sem tipo submete-o. */ ?>
            <button type="button" class="produto__botão"
                    data-no-cesto="<?= e((string)$produto['name']) ?>">
              + <?= e(__('shop.product.add')) ?>
            </button>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($botãoTexto !== ''): ?>
      <div class="montra__pé" data-entra="sobe">
        <a class="botão botão--ouro" href="<?= e($botãoUrl) ?>">
          <span><?= e($botãoTexto) ?></span>
          <span aria-hidden="true">&rarr;</span>
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>
