<?php
/**
 * Comprar por intenção: a outra entrada para a loja.
 *
 * A loja arruma-se por categorias, que é como se arruma um armazém — velas de um
 * lado, cristais do outro. Quem chega não procura uma vela: procura dormir
 * melhor. Estes cartões abrem o mesmo catálogo pela razão, e cada um leva a uma
 * etiqueta da loja.
 *
 * **A contagem é lida e não escrita.** O cartão diz quantas peças tem a etiqueta,
 * e quem o diz é a loja no momento de desenhar a página. Escrito à mão no
 * backoffice, era um número que ficava errado no dia em que um produto esgotasse
 * — e ninguém se lembra de ir lá corrigi-lo.
 *
 * @var array  $section
 * @var string $headingTag
 */

use Admedia\Shop\Models\Product;
use Admedia\Shop\Models\Tag;

$itens = $section['items'] ?? [];

/* Os símbolos, a traço, desenhados numa grelha de 60 por 60. Vivem aqui e não
   numa folha de estilo nem num ficheiro de imagem: são cinco linhas de `path` e
   são conteúdo do bloco — um ficheiro por símbolo era cinco pedidos ao servidor
   para desenhar cinco riscos. */
$símbolos = [
    'olho' => 'M10 30 Q30 12 50 30 Q30 48 10 30Z M30 24 a6 6 0 1 0 0.01 0 M30 6 a24 24 0 1 0 0.01 0',
    'laco' => 'M24 18 a12 12 0 1 0 0.01 0 M36 18 a12 12 0 1 0 0.01 0',
    'sol'  => 'M30 20 a10 10 0 1 0 0.01 0 M30 6V12 M30 48V54 M6 30H12 M48 30H54 '
            . 'M13 13L17 17 M43 43L47 47 M47 13L43 17 M17 43L13 47',
    'fumo' => 'M22 54 C14 44 30 38 22 28 C16 20 26 14 22 6 M34 54 C26 44 42 38 34 28 '
            . 'C28 20 38 14 34 6 M46 50 C40 42 50 38 46 30',
    'lua'  => 'M36 10 A20 20 0 1 0 50 40 A16 16 0 1 1 36 10Z '
            . 'M46 14 L47.5 18 L51.5 19.5 L47.5 21 L46 25 L44.5 21 L40.5 19.5 L44.5 18Z '
            . 'M52 30 L52.8 32 L54.8 32.8 L52.8 33.6 L52 35.6 L51.2 33.6 L49.2 32.8 L51.2 32Z',
];

/* Quantas peças tem cada etiqueta. Uma pergunta por cartão — cinco consultas
   para cinco cartões —, e só onde há loja: um site do mesmo CMS sem
   config/shop.php não tem a classe para chamar. */
$temLoja = $app->hasShop();
?>
<?php if ($itens !== []): ?>
<section class="secção secção--intenções"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">
    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>
    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <div class="intenções">
      <?php foreach ($itens as $índice => $item): ?>
        <?php
        $etiqueta = trim((string)($item['subtitle'] ?? ''));
        $símbolo  = $símbolos[(string)($item['caption'] ?? '')] ?? '';

        /* Sem etiqueta — ou com uma que já não existe — o cartão desenha-se à
           mesma, sem contagem e a apontar para a loja toda. Um cartão que
           desaparecesse por causa de uma etiqueta apagada era uma fila de cinco
           que passava a quatro sem ninguém perceber porquê. */
        $registo = ($temLoja && $etiqueta !== '') ? Tag::findBySlug($etiqueta) : null;
        $quantas = $registo !== null ? count(Product::withTag((int)$registo['id'])) : null;
        $destino = $registo !== null
            ? locale_url('/intencao/' . rawurlencode($etiqueta))
            : ($temLoja ? \Admedia\Shop\Shop::to('index') : '');
        ?>
        <?php if ($destino !== ''): ?>
        <a class="intenção" href="<?= e($destino) ?>"
           data-entra="sobe" data-atraso="<?= (int)$índice * 80 ?>">
          <?php /* A luz que segue o rato, posta num elemento próprio para não
                   disputar o fundo do cartão. Decoração, e por isso escondida
                   de quem ouve a página. */ ?>
          <span class="intenção__luz" aria-hidden="true"></span>

          <?php if ($símbolo !== ''): ?>
            <svg class="intenção__símbolo" viewBox="0 0 60 60" aria-hidden="true">
              <path d="<?= e($símbolo) ?>" fill="none" stroke="currentColor" stroke-width="1.2"
                    stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          <?php endif; ?>

          <span class="intenção__dizer">
            <span class="intenção__nome"><?= e((string)($item['title'] ?? '')) ?></span>
            <?php if ($quantas !== null): ?>
              <span class="intenção__conta">
                <?= e(__($quantas === 1 ? 'intencao.piece' : 'intencao.pieces', ['n' => $quantas])) ?>
                <span aria-hidden="true">&rarr;</span>
              </span>
            <?php endif; ?>
          </span>
        </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
