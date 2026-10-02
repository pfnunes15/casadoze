<?php
/**
 * A ponte: da loja à mesa.
 *
 * Separa as duas metades do que a Casa faz — o que se leva para casa e o que se
 * vive à mesa — e diz em voz alta que são coisas diferentes. Sem ela, a lista
 * de consultas vinha a seguir à grelha de produtos como se fosse mais catálogo.
 *
 * **Por trás passam duas filas de letras ocas**, «Loja» e «Consultas», em tipo
 * muito grande e só com o contorno. Não são texto para ler: são textura, e por
 * isso levam `aria-hidden`. Andam ao sabor do desenrolar — a de cima sai e
 * apaga-se, a de baixo entra e acende-se —, e quem as move é o js/site.js. Sem
 * JavaScript ficam quietas onde estão, que continua a ser um fundo.
 *
 * As repetições estão escritas e não são geradas por JavaScript: uma fila vazia
 * que só se enche depois de o guião correr é uma fila que não existe para quem
 * tem o JavaScript desligado.
 *
 * @var array  $section
 * @var string $headingTag
 */

$opções = \Admedia\Cms\Models\PageSection::options($section);

$esquerda = trim((string)($section['cta_label'] ?? '')) ?: __('ponte.left');
$direita  = trim((string)($opções['cta2_label'] ?? '')) ?: __('ponte.right');

$urlEsquerda = trim((string)($section['cta_url'] ?? '')) ?: '#loja';
$urlDireita  = trim((string)($opções['cta2_url'] ?? '')) ?: '#consultas';

/* Quantas vezes a palavra se repete em cada fila. Seis chega para atravessar um
   ecrã largo com o tipo no tamanho máximo, e é o que a maquete usa. */
$repetições = 6;
?>
<section class="secção--ponte"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>

  <div class="ponte__fila ponte__fila--cima" aria-hidden="true" data-ponte-cima>
    <?php for ($i = 0; $i < $repetições; $i++): ?>
      <span><?= e(__('shop.title')) ?></span><span class="ponte__ponto">&middot;</span>
    <?php endfor; ?>
  </div>

  <div class="ponte__fila ponte__fila--baixo" aria-hidden="true" data-ponte-baixo>
    <?php for ($i = 0; $i < $repetições; $i++): ?>
      <span><?= e(__('consultas.title')) ?></span><span class="ponte__ponto">&middot;</span>
    <?php endfor; ?>
  </div>

  <div class="ponte__dizer">
    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <?php /* Esta sobrescrita leva traço dos dois lados, ao contrário das
               outras: está centrada, e um traço só de um lado puxava-a. */ ?>
      <p class="sobrescrita sobrescrita--ambos" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>

    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> class="ponte__título" data-entra="sobe" data-atraso="100"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
      <p class="ponte__texto" data-entra="sobe" data-atraso="200"><?= nl2br(e((string)$section['body'])) ?></p>
    <?php endif; ?>

    <div class="ponte__setas" data-entra="sobe" data-atraso="300">
      <a class="ponte__seta ponte__seta--esq" href="<?= e($urlEsquerda) ?>">
        <span aria-hidden="true">&uarr;</span>
        <span><?= e($esquerda) ?></span>
        <span class="ponte__risco ponte__risco--esq" aria-hidden="true"></span>
      </a>

      <span class="ponte__losango" aria-hidden="true"></span>

      <a class="ponte__seta ponte__seta--dir" href="<?= e($urlDireita) ?>">
        <span class="ponte__risco ponte__risco--dir" aria-hidden="true"></span>
        <span><?= e($direita) ?></span>
        <span aria-hidden="true">&darr;</span>
      </a>
    </div>
  </div>
</section>

<?php /* O separador que a maquete põe a seguir à ponte: dois fios e o sigilo da
         Casa ao meio. Fora da secção, de propósito — é o que marca o fim
         dela. */ ?>
<div class="separador" aria-hidden="true">
  <span class="separador__fio separador__fio--esq" data-entra="risco"></span>
  <img class="separador__sigilo" src="<?= asset('img/logotipo.png') ?>" alt="" loading="lazy" decoding="async">
  <span class="separador__fio separador__fio--dir" data-entra="risco"></span>
</div>
