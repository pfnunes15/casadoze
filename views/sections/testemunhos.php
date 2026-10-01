<?php
/**
 * Palavras de quem passou pela Casa.
 *
 * As aspas são do desenho e não do texto: escritas no campo, apareciam a dobrar
 * em quem as escrevesse por hábito.
 *
 * @var array $section
 * @var string $headingTag
 */
$itens = $section['items'] ?? [];
?>
<?php if ($itens !== []): ?>
<section class="secção"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">
    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <p class="sobrescrita"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>
    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> style="margin-block: 18px 32px"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <div class="grelha grelha--larga">
      <?php foreach ($itens as $item): ?>
        <?php /* Um `<blockquote>` e não um `<div>`: é uma citação, e é isso que
                 um leitor de ecrã deve anunciar. O `<cite>` leva quem a disse. */ ?>
        <blockquote class="cartão" style="margin: 0">
          <p class="prosa-citada">&laquo;<?= nl2br(e((string)($item['body'] ?? ''))) ?>&raquo;</p>
          <footer class="cartão__pé">
            <cite class="cartão__ordinal" style="font-style: normal"><?= e((string)($item['title'] ?? '')) ?></cite>
            <?php if (trim((string)($item['subtitle'] ?? '')) !== ''): ?>
              <span class="cartão__dito" style="font-size: 15px"><?= e((string)$item['subtitle']) ?></span>
            <?php endif; ?>
          </footer>
        </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
