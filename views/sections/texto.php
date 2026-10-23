<?php
/**
 * Prosa, na largura do miolo.
 *
 * O bloco que serve para o que não tem bloco: a privacidade, os termos, uma
 * carta. O `body` vem do editor de texto e é HTML — sai sem escapar de propósito,
 * e quem o escreve é quem entrou no backoffice.
 *
 * @var array  $section
 * @var string $headingTag
 */
?>
<section class="secção"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo" style="max-width: 760px">
    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <p class="sobrescrita"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>

    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> style="margin-block: 18px 24px"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <div class="prosa"><?= (string)($section['body'] ?? '') ?></div>

    <?php /* A button where the prose asks for one. A page of text that ends by
             telling you to do something and leaves you to find the way there is
             a page that wastes the one moment the reader was ready. */ ?>
    <?php $botão = trim((string)($section['cta_label'] ?? '')); $destino = trim((string)($section['cta_url'] ?? '')); ?>
    <?php if ($botão !== '' && $destino !== ''): ?>
      <p class="texto__pé">
        <a class="botão botão--ouro" href="<?= e($destino) ?>">
          <span><?= e($botão) ?></span>
          <span aria-hidden="true">&rarr;</span>
        </a>
      </p>
    <?php endif; ?>
  </div>
</section>
