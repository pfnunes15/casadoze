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
<section class="secção secção--testemunhos"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo testemunhos__miolo">
    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>
    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> class="testemunhos__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <div class="testemunhos__fila">
      <?php foreach ($itens as $índice => $item): ?>
        <?php /* Um `<figure>` com `<blockquote>` lá dentro, e não um `<div>`: é
                 uma citação com atribuição, e é isso que um leitor de ecrã deve
                 anunciar. As aspas são do desenho e não do texto — escritas no
                 campo, apareciam a dobrar em quem as escrevesse por hábito. */ ?>
        <figure class="testemunho" data-entra="sobe" data-atraso="<?= (int)$índice * 100 ?>">
          <blockquote class="testemunho__dito">
            <span class="testemunho__aspas" aria-hidden="true">&ldquo;</span>
            <span class="testemunho__texto"><?= nl2br(e((string)($item['body'] ?? ''))) ?></span>
          </blockquote>
          <figcaption class="testemunho__quem">
            <span class="testemunho__nome"><?= e((string)($item['title'] ?? '')) ?></span>
            <?php if (trim((string)($item['subtitle'] ?? '')) !== ''): ?>
              <span class="testemunho__oque"><?= e((string)$item['subtitle']) ?></span>
            <?php endif; ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
