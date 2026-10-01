<?php
/**
 * A barra do topo, como na maquete aprovada.
 *
 * Três colunas: as ligações à esquerda, a marca ao centro, e à direita o idioma
 * e o cesto. As colunas das pontas são `1fr` iguais e a do meio é `auto`, e é
 * isso que põe a marca no centro **da página** — e não no centro do que sobra
 * depois de o cesto aparecer ou desaparecer.
 *
 * Não tem fundo próprio: flutua por cima da fotografia com que a página abre, e
 * ganha fundo assim que a página sai do topo. Quem lhe põe o `data-descido` é o
 * js/site.js; a folha de estilo só sabe o que fazer com ele. Sem JavaScript o
 * cabeçalho fica transparente sempre, que é o que a maquete mostra em cima da
 * fotografia e continua legível.
 *
 * **Num telefone a fila das ligações não cabe** — cinco entradas com entrelinha
 * larga, mais o idioma e o cesto —, e por isso lá ficam só a marca e o botão que
 * abre o painel. Quem esconde uma e mostra a outra é a folha de estilo: o que
 * cabe é uma questão de largura, e a largura só o browser a sabe.
 *
 * @var \Admedia\Core\App $app
 */

$logo = setting('site.logo');
$nome = setting('site.name', (string)$app->config('app.name', 'Casa de Zé'));

/* O menu desta página, e não o do site: é a página que escolhe, em Páginas >
   Menu do cabeçalho, e vazio quer dizer o menu principal. O $page vem da
   moldura, que faz `require` deste ficheiro e portanto partilha o âmbito — nos
   ecrãs onde não há página nenhuma, como um erro, é o `??` que trata disso. */
$menu = \Admedia\Cms\Models\MenuItem::publicTree(
    \Admedia\Cms\Menus::clean((string)($page['menu'] ?? ''))
);

/* Quantas peças há no cesto. Lê a sessão e não a base de dados, por isso não
   custa uma consulta por página; e só é desenhado com alguma coisa lá dentro.

   Só existe onde existe loja: um site do mesmo CMS sem `config/shop.php` não tem
   a classe do cesto para chamar, e chamá-la rebentava a página toda. */
$cesto = $app->hasShop() ? \Admedia\Shop\Services\Cart::count() : 0;

// As outras línguas, para o selector. Com uma língua só, não desenha nada.
$idiomas = \Admedia\Core\Locales::many() ? \Admedia\Core\Locales::alternates() : [];
?>
<header class="cabeçalho" id="cabeçalho" data-cabeçalho>
  <div class="miolo cabeçalho__linha">

    <?php /* A esquerda: as ligações num ecrã largo, o botão do painel num
             estreito. Os dois estão no documento e é a folha que mostra um
             deles — e não o JavaScript, que chegaria depois de a página
             desenhar e faria a barra saltar. */ ?>
    <div>
      <button type="button" class="pílula cabeçalho__menu" data-abrir-painel
              aria-expanded="false" aria-controls="painel">
        <?= e(__('site.menu')) ?>
      </button>

      <?php if ($menu !== []): ?>
      <nav class="navegação" aria-label="<?= e(__('site.menu_main')) ?>">
        <?php foreach ($menu as $item): ?>
          <a href="<?= e($item['url']) ?>"<?= \Admedia\Cms\Nav::isCurrent($item) ? ' aria-current="page"' : '' ?>>
            <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </div>

    <a class="marca" href="<?= e(locale_url('/')) ?>" aria-label="<?= e($nome) ?>">
      <?php if ($logo !== ''): ?>
        <?php /* O `alt` vazio é de propósito: o nome está escrito ao lado, em
                 texto, e um leitor de ecrã que leia os dois diz o nome da casa
                 duas vezes seguidas. */ ?>
        <img src="<?= e(media_url($logo)) ?>" alt="">
      <?php endif; ?>
      <span><?= e(mb_strtoupper($nome)) ?></span>
    </a>

    <div class="cabeçalho__fim">
      <?php if ($idiomas !== []): ?>
      <div class="idiomas" data-idiomas>
        <button type="button" class="pílula" data-abrir-idiomas
                aria-expanded="false" aria-label="<?= e(__('site.lang')) ?>">
          <?= e(\Admedia\Core\Locales::get(\Admedia\Core\Locales::current())['short'] ?? '') ?>
        </button>
        <?php /* Escondido com o atributo `hidden` e não com uma classe: assim um
                 leitor de ecrã também não o encontra enquanto está fechado, e
                 sem JavaScript a lista fica fechada em vez de ficar aberta a
                 tapar a página. */ ?>
        <div class="idiomas__lista" data-lista-idiomas hidden>
          <?php foreach ($idiomas as $idioma): ?>
            <a href="<?= e($idioma['url']) ?>"<?= $idioma['current'] ? ' aria-current="true"' : '' ?>>
              <span><?= e($idioma['label']) ?></span>
              <span class="código"><?= e($idioma['short']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($app->hasShop()): ?>
      <a class="pílula" href="<?= e(\Admedia\Shop\Shop::to('cart')) ?>">
        <?= e(__('site.cart')) ?>
        <?php if ($cesto > 0): ?><span class="pílula__conta"><?= (int)$cesto ?></span><?php endif; ?>
      </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php /* O painel, com o menu todo. É a navegação nos ecrãs estreitos; nos largos
         o mesmo menu está na barra. Fica depois do cabeçalho no documento e não
         antes, porque é ele que o abre — e quem ouve a página encontra primeiro
         o botão e só depois o que ele abre. */ ?>
<div class="painel" id="painel" data-painel hidden>
  <div class="painel__folha">
    <button type="button" class="pílula painel__fechar" data-fechar-painel>
      <?= e(__('site.menu_close')) ?>
    </button>
    <?php foreach ($menu as $item): ?>
      <a href="<?= e($item['url']) ?>"<?= \Admedia\Cms\Nav::isCurrent($item) ? ' aria-current="page"' : '' ?>>
        <?= e($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
