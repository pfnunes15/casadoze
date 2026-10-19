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
use App\Services\Sigilos;

$itens = $section['items'] ?? [];

/* Os símbolos vivem em App\Services\Sigilos. Estavam escritos aqui dentro, e
   passaram a precisar de dois sítios no dia em que a loja quis a mesma fila de
   chips: dois arrays iguais em duas vistas é um que fica desactualizado. Qual é
   o símbolo de cada intenção continua a ser escolha da Casa, no campo de cada
   item deste bloco. */

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
        $símbolo  = Sigilos::traço((string)($item['caption'] ?? ''));

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
