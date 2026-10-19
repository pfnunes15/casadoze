<?php
/**
 * A montra: a loja inteira, uma categoria dela, ou uma intenção.
 *
 * Substitui a vista do pacote — a cadeia de vistas prefere sempre a do site —
 * porque o desenho desta casa não é o de um catálogo qualquer: tem a fila do
 * «Procuro», a barra de filtros que se cola ao topo, e cartões com o desenho
 * do produto em vez de fotografia. Ver `docs/maquete/Loja.dc.html`.
 *
 * **Os filtros filtram no browser e não recarregam a página.** São oito
 * produtos já desenhados, e ir buscar os mesmos oito ao servidor para esconder
 * quatro era uma viagem para nada. Sem JavaScript a barra não aparece — ver
 * js/site.js — e fica a montra inteira, que é a resposta certa. **No dia em que
 * a loja passar de umas dezenas de peças isto tem de mudar**: filtrar e paginar
 * no servidor, porque desenhar trezentos cartões para esconder duzentos é a
 * mesma viagem ao contrário.
 *
 * **O que a maquete tem e esta página não tem, por falta de dados e não por
 * esquecimento:**
 *
 * - *As estrelas.* A maquete põe «★ 4,9 (12)» em cada cartão. Não há tabela de
 *   avaliações, e inventar uma média era pôr no site um número que ninguém
 *   disse. Ver `docs/maquete/NOTAS.md`.
 * - *O filtro «Elemento».* Nenhum produto tem essa característica.
 * - *«Mais vendidos», na ordenação.* Precisa de contar encomendas, e as
 *   encomendas ainda não existem.
 * - *O «desde 18,00 €».* É de produtos com variantes; nenhum tem.
 *
 * @var array      $products
 * @var array      $categories  a árvore publicada
 * @var array|null $category    a que está aberta, ou null
 * @var array|null $tag         a intenção que está aberta, ou null
 * @var array      $cart
 */

use Admedia\Shop\Models\Attribute;
use Admedia\Shop\Models\Category;
use Admedia\Shop\Models\Product;
use Admedia\Shop\Models\Tag;
use Admedia\Shop\Shop;
use App\Services\Sigilos;

/* O cabeçalho é fixo e transparente, e isso só funciona por cima de uma abertura
   de ecrã inteiro. Esta página não tem nenhuma, por isso leva o espaço em cima e
   o cabeçalho deixa de ser transparente — ver `.com-cabeçalho` na folha de
   estilo, e views/pages/composed.php, que faz o mesmo às páginas de blocos. */
$bodyClass = 'com-cabeçalho';

$etiqueta = $tag ?? null;

$pageTitle = $category
    ? (string)$category['name']
    : ($etiqueta ? __('intencao.title', ['intencao' => (string)$etiqueta['name']]) : __('loja.title'));

$entrada = trim((string)setting('shop.intro', ''));

$metaDescription = $category && trim((string)$category['description']) !== ''
    ? mb_substr(trim((string)$category['description']), 0, 160)
    : mb_substr($entrada, 0, 160);

/* O que cada produto é, para os filtros saberem o que esconder. Três perguntas
   por produto, e é por isto que a filtragem no browser só serve enquanto a loja
   for pequena: com trezentos produtos são novecentas consultas. */
$deCada = [];
foreach ($products as $produto) {
    $id = (int)$produto['id'];

    $caraterísticas = [];
    foreach (Attribute::forProduct($id) as $c) {
        $caraterísticas[mb_strtolower((string)$c['name'])] = (string)$c['value'];
    }

    $deCada[$id] = [
        'categorias' => array_map(static fn(array $c): string => (string)$c['slug'], Category::forProduct($id)),
        'etiquetas'  => array_map(static fn(array $t): string => (string)$t['slug'], Tag::forProduct($id)),
        'desenho'    => $caraterísticas['desenho'] ?? '',
        'lua'        => $caraterísticas['lua'] ?? '',
    ];
}

/* As intenções, com o símbolo que a Casa lhes deu no bloco da porta de entrada.
   Só as que têm peças: um chip que não devolve nada é um beco. */
$sigilos   = Sigilos::paraEtiquetas();
$intenções = [];
foreach (Tag::all() as $t) {
    $quantas = 0;
    foreach ($deCada as $d) {
        if (in_array((string)$t['slug'], $d['etiquetas'], true)) {
            $quantas++;
        }
    }
    $intenções[] = [
        'slug'    => (string)$t['slug'],
        'nome'    => (string)$t['name'],
        'quantas' => $quantas,
        'sigilo'  => $sigilos[(string)$t['slug']] ?? '',
    ];
}

/* Os grupos da barra de filtros, construídos do que existe e não de uma lista
   escrita. Um grupo sem opções não se desenha — é o que tira «Fase lunar» de um
   site cujos produtos não a declarem, sem uma condição escrita para esse caso. */
$luas = [];
foreach ($deCada as $d) {
    if ($d['lua'] !== '' && !in_array($d['lua'], $luas, true)) {
        $luas[] = $d['lua'];
    }
}
sort($luas);

$grupos = [];

if ($categories !== []) {
    $grupos[] = [
        'chave'  => 'categoria',
        'título' => __('loja.filter.category'),
        'opções' => array_map(
            static fn(array $c): array => ['valor' => (string)$c['slug'], 'nome' => (string)$c['name']],
            $categories
        ),
    ];
}

if ($intenções !== []) {
    $grupos[] = [
        'chave'  => 'intencao',
        'título' => __('loja.filter.intent'),
        'opções' => array_map(
            static fn(array $i): array => ['valor' => $i['slug'], 'nome' => $i['nome']],
            array_values(array_filter($intenções, static fn(array $i): bool => $i['quantas'] > 0))
        ),
    ];
}

if ($luas !== []) {
    $grupos[] = [
        'chave'  => 'lua',
        'título' => __('loja.filter.moon'),
        'opções' => array_map(static fn(string $l): array => ['valor' => $l, 'nome' => $l], $luas),
    ];
}

$grupos[] = [
    'chave'  => 'estado',
    'título' => __('loja.filter.stock'),
    'opções' => [['valor' => 'disponivel', 'nome' => __('loja.filter.in_stock')]],
];

/* As migalhas. A loja é sempre o degrau do meio: uma categoria e uma intenção
   são duas maneiras de a olhar, e não dois sítios diferentes. */
$migalhas = [[locale_url('/'), (string)setting('site.name', '')], [Shop::to('index'), __('shop.title')]];
if ($category) {
    $migalhas[] = ['', (string)$category['name']];
} elseif ($etiqueta) {
    $migalhas[] = ['', (string)$etiqueta['name']];
}

$quantos = count($products);

/* Quando é que uma peça é nova.
   -------------------------------------------------------------------------
   **O catálogo de instalação não é novidade.** Com a regra óbvia — criado há
   menos de trinta dias — as oito peças semeadas pelas migrações apareciam
   todas com o selo «Novo» no dia em que o site abrisse, e um selo que está em
   tudo não diz nada. A loja abriu quando a primeira peça foi criada; novo é o
   que entrou depois disso. */
$abertura = PHP_INT_MAX;
foreach ($products as $p) {
    $abertura = min($abertura, strtotime((string)($p['created_at'] ?? '')) ?: PHP_INT_MAX);
}
$depoisDaAbertura = $abertura === PHP_INT_MAX ? PHP_INT_MAX : $abertura + 86400;
?>
<?php /* Um `<div>` e não um `<main>`: o `<main id="conteudo">` é do layout, e
         dois numa página é um a mais — o atalho «saltar para o conteúdo» do
         cabeçalho aponta para o de cima, e um leitor de ecrã que encontre dois
         deixa de saber qual é o conteúdo da página. */ ?>
<div class="loja">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <?php foreach ($migalhas as $i => [$url, $nome]): ?>
      <?php if ($i > 0): ?><span class="migalhas__traço" aria-hidden="true">/</span><?php endif; ?>
      <?php if ($url !== '' && $i < count($migalhas) - 1): ?>
        <a href="<?= e($url) ?>"><?= e($nome) ?></a>
      <?php else: ?>
        <span aria-current="page"><?= e($nome) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <header class="loja__topo">
    <div class="loja__dizer">
      <h1 class="loja__título"><?= e($pageTitle) ?></h1>
      <?php if ($entrada !== ''): ?>
        <p class="loja__entrada"><?= nl2br(e($entrada)) ?></p>
      <?php endif; ?>
    </div>
    <span class="loja__conta"><?= e(__($quantos === 1 ? 'intencao.piece' : 'intencao.pieces', ['n' => $quantos])) ?></span>
  </header>

  <?php if ($intenções !== []): ?>
    <?php /* A fila do «Procuro». Ligações a sério e não botões: cada intenção
             tem endereço próprio — /intencao/amor — e é uma página que se
             guarda nos favoritos e se manda a alguém. */ ?>
    <div class="procuro">
      <span class="procuro__rótulo"><?= e(__('loja.looking_for')) ?></span>
      <?php foreach ($intenções as $i): ?>
        <?php if ($i['quantas'] === 0) { continue; } ?>
        <a class="procuro__chip<?= $etiqueta && (string)$etiqueta['slug'] === $i['slug'] ? ' procuro__chip--aceso' : '' ?>"
           href="<?= e(locale_url('/intencao/' . rawurlencode($i['slug']))) ?>">
          <?php if ($i['sigilo'] !== ''): ?>
            <svg class="procuro__sigilo" viewBox="0 0 60 60" aria-hidden="true">
              <path d="<?= e($i['sigilo']) ?>" fill="none" stroke="currentColor" stroke-width="2.4"
                    stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          <?php endif; ?>
          <span><?= e($i['nome']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php /* A barra, a contagem e a grelha são uma coluna só, com 22px entre si
           e 44 a separá-las da fila do «Procuro». A maquete tem aqui também uma
           coluna de filtros à esquerda; é a gaveta do telemóvel, e num ecrã
           largo está escondida. */ ?>
  <div class="loja__corpo">

  <?php /* A barra de filtros, escondida até o JavaScript a ligar: uma barra de
           filtros que não filtra é pior do que barra nenhuma. */ ?>
  <div class="filtros" data-filtros-loja hidden>
    <div class="filtros__fila">
      <span class="filtros__rótulo"><?= e(__('loja.filter')) ?></span>
      <?php foreach ($grupos as $g): ?>
        <button type="button" class="filtros__grupo" data-grupo="<?= e($g['chave']) ?>" aria-expanded="false">
          <?= e($g['título']) ?>
          <span class="filtros__seta" aria-hidden="true">&#9662;</span>
        </button>
      <?php endforeach; ?>
      <button type="button" class="filtros__limpar" data-limpar hidden><?= e(__('loja.clear')) ?></button>
    </div>

    <?php foreach ($grupos as $g): ?>
      <div class="filtros__opções" data-opções="<?= e($g['chave']) ?>" hidden>
        <?php foreach ($g['opções'] as $o): ?>
          <button type="button" class="filtros__opção"
                  data-filtro="<?= e($g['chave']) ?>" data-valor="<?= e($o['valor']) ?>"
                  aria-pressed="false"><?= e($o['nome']) ?></button>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="loja__linha">
    <span class="loja__resultado" data-resultado><?= e(__($quantos === 1 ? 'loja.result' : 'loja.results', ['n' => $quantos])) ?></span>

    <label class="loja__ordenar">
      <?= e(__('loja.sort')) ?>
      <?php /* Sem «Mais vendidos»: contá-lo pede encomendas, e ainda não há. */ ?>
      <select data-ordenar>
        <option value="catalogo"><?= e(__('loja.sort.catalogue')) ?></option>
        <option value="novos"><?= e(__('loja.sort.new')) ?></option>
        <option value="barato"><?= e(__('loja.sort.cheap')) ?></option>
        <option value="caro"><?= e(__('loja.sort.dear')) ?></option>
      </select>
    </label>
  </div>

  <div class="montra montra--loja" data-montra>
    <?php foreach ($products as $índice => $produto): ?>
      <?php
      $id       = (int)$produto['id'];
      $d        = $deCada[$id];
      $esgotado = (int)$produto['stock'] < 1;
      $poucas   = !$esgotado && (int)$produto['stock'] <= 3;
      $nascido  = strtotime((string)($produto['created_at'] ?? '')) ?: 0;
      $novo     = !$esgotado && $nascido > $depoisDaAbertura && $nascido > strtotime('-30 days');
      $ficha    = Shop::to('product', ['slug' => (string)$produto['slug']]);
      ?>
      <article class="produto" data-entra="sobe" data-atraso="<?= ((int)$índice % 4) * 90 ?>"
               data-categoria="<?= e(implode(' ', $d['categorias'])) ?>"
               data-intencao="<?= e(implode(' ', $d['etiquetas'])) ?>"
               data-lua="<?= e($d['lua']) ?>"
               data-estado="<?= $esgotado ? '' : 'disponivel' ?>"
               data-preco="<?= (int)$produto['price_cents'] ?>"
               data-ordem="<?= (int)$índice ?>"
               data-novo="<?= strtotime((string)($produto['created_at'] ?? '')) ?: 0 ?>">

        <?php /* A moldura inteira é a ligação para a ficha, e o nome por baixo
                 é outra. Dois `<a>` para o mesmo sítio e não um `<a>` a embrulhar
                 tudo: o botão de comprar está dentro do cartão, e um botão
                 dentro de uma ligação é marcação inválida — e no teclado é um
                 alvo que engole o outro. */ ?>
        <a class="produto__moldura" href="<?= e($ficha) ?>" data-inclina
           aria-label="<?= e((string)$produto['name']) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($d['desenho'] !== ''): ?>
            <canvas class="produto__desenho" width="600" height="800"
                    data-desenho="<?= e($d['desenho']) ?>" data-semente="<?= $id ?>"
                    aria-hidden="true"></canvas>
          <?php endif; ?>

          <span class="produto__luz" aria-hidden="true"></span>

          <?php if ($d['lua'] !== ''): ?>
            <span class="produto__lua"><?= e($d['lua']) ?></span>
          <?php endif; ?>

          <?php if ($esgotado): ?>
            <span class="produto__selo produto__selo--esgotado"><?= e(__('shop.product.sold_out')) ?></span>
          <?php elseif ($poucas): ?>
            <span class="produto__selo produto__selo--poucas"><?= e(__('loja.badge.few')) ?></span>
          <?php elseif ($novo): ?>
            <span class="produto__selo produto__selo--novo"><?= e(__('loja.badge.new')) ?></span>
          <?php endif; ?>
        </a>

        <div class="produto__linha">
          <div class="produto__nomes">
            <h2 class="produto__nome"><a href="<?= e($ficha) ?>"><?= e((string)$produto['name']) ?></a></h2>
            <?php if (trim((string)($produto['summary'] ?? '')) !== ''): ?>
              <span class="produto__dito"><?= e((string)$produto['summary']) ?></span>
            <?php endif; ?>
          </div>
          <span class="produto__preço"><?= e(money((int)$produto['price_cents'])) ?></span>
        </div>

        <?php /* «Escolher» e não «Adicionar»: daqui vai-se à ficha, onde se vê o
                 que a peça é e se escolhe o que ela tem para escolher. Um cartão
                 que põe no cesto sem a pessoa ter aberto a peça é um cesto cheio
                 de enganos. */ ?>
        <div class="produto__pé">
          <?php if ($esgotado): ?>
            <span class="produto__esgotado"><?= e(__('shop.product.sold_out')) ?></span>
          <?php else: ?>
            <a class="produto__botão" href="<?= e($ficha) ?>"><?= e(__('loja.choose')) ?></a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php /* O vazio tem dois donos: o servidor, quando uma categoria não tem
           peças, e o browser, quando os filtros não deixam nenhuma de pé. O
           mesmo bloco serve os dois. */ ?>
  <div class="loja__vazio"<?= $products === [] ? '' : ' hidden' ?> data-vazio>
    <svg class="loja__vazioSigilo" viewBox="0 0 40 40" aria-hidden="true">
      <path d="M26 6 A15 15 0 1 0 34 30 A12 12 0 1 1 26 6Z" fill="none" stroke="currentColor" stroke-width="1"></path>
    </svg>
    <span class="loja__vazioTítulo"><?= e(__('loja.empty')) ?></span>
    <span class="loja__vazioDito"><?= e(__('loja.empty.body')) ?></span>
    <button type="button" class="botão botão--ouro" data-limpar hidden><span><?= e(__('loja.clear')) ?></span></button>
  </div>

  </div>
</div>
