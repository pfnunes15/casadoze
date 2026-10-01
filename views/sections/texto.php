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

    <?= (string)($section['body'] ?? '') ?>
  </div>
</section>
