<?php
/**
 * The house name, large, closing the page.
 *
 * Drawn and not photographed. It was a 437 KB PNG of a word, which blurs on a
 * retina screen and has to go back to a designer every time the house changes
 * its name — as it just did. This is the same lettering in two kilobytes,
 * sharp at any size, and it reads whatever is written in Definições > Nome.
 *
 * Inline and not an `<img>`: an SVG loaded through `<img>` is a document of its
 * own and never sees the page's fonts, so the name would come out in whatever
 * serif the system happens to have. Inline, it uses `--titulo`, the same letter
 * as every title on the site.
 *
 * Decoration, hence `aria-hidden`: the name is in the header and in the
 * copyright line below, and a third reading only gets in the way.
 *
 * @var string $nome
 */
$texto = mb_strtoupper(trim($nome));

/* The box follows the text and not the other way round: a long name in a fixed
   box gets clipped, a short one floats in the middle of nothing. 70 units a
   character is what this letter measures at this size, with a little air on
   each side — and the flourish below is laid out from the result, so it stays
   in proportion to the name instead of to a width someone once typed. PHP has
   no way to measure a glyph, so this is an estimate; the stylesheet lets the
   drawing overflow rather than cut it, for the name full of Ws. */
$largura = max(520, (int)(mb_strlen($texto) * 70) + 90);
$meio    = intdiv($largura, 2);
$braço   = (int)($largura * 0.30);   // where the flourish ends
$arco    = (int)($largura * 0.11);   // the thin line under it
?>
<svg class="letreiro" viewBox="0 10 <?= $largura ?> 186" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="letreiro-ouro" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%"   stop-color="#8a5f24"/>
      <stop offset="26%"  stop-color="#f6e3a8"/>
      <stop offset="50%"  stop-color="#d9a94e"/>
      <stop offset="76%"  stop-color="#b8863b"/>
      <stop offset="100%" stop-color="#7d5420"/>
    </linearGradient>

    <?php /* The glow is the lettering itself, blurred and laid underneath. A
             drop shadow would light only one side; gold on black glows all
             round, which is what the mock-up shows. */ ?>
    <filter id="letreiro-brilho" x="-20%" y="-45%" width="140%" height="190%">
      <feGaussianBlur stdDeviation="9" result="turvo"/>
      <feColorMatrix in="turvo" type="matrix"
        values="1 0 0 0 0.85  0 1 0 0 0.62  0 0 1 0 0.25  0 0 0 0.6 0"/>
    </filter>
  </defs>

  <g class="letreiro__nome" text-anchor="middle">
    <text x="<?= $meio ?>" y="112" fill="#d9a94e" filter="url(#letreiro-brilho)"><?= e($texto) ?></text>
    <text x="<?= $meio ?>" y="112" fill="url(#letreiro-ouro)"><?= e($texto) ?></text>
  </g>

  <?php /* The flourish: two strokes thinning out to the sides, meeting at a
           four-pointed diamond. Drawn from the middle out. */ ?>
  <g fill="none" stroke="url(#letreiro-ouro)" stroke-linecap="round">
    <path stroke-width="2.6"
          d="M<?= $meio - 26 ?> 162C<?= $meio - (int)($braço * 0.45) ?> 162 <?= $meio - (int)($braço * 0.68) ?> 146 <?= $meio - $braço ?> 138"/>
    <path stroke-width="2.6"
          d="M<?= $meio + 26 ?> 162C<?= $meio + (int)($braço * 0.45) ?> 162 <?= $meio + (int)($braço * 0.68) ?> 146 <?= $meio + $braço ?> 138"/>
    <path stroke-width="1.1"
          d="M<?= $meio - $arco ?> 171C<?= $meio - (int)($arco * 0.5) ?> 181 <?= $meio + (int)($arco * 0.5) ?> 181 <?= $meio + $arco ?> 171"/>
  </g>
  <path fill="url(#letreiro-ouro)"
        d="M<?= $meio ?> 144 L<?= $meio + 12 ?> 162 L<?= $meio ?> 180 L<?= $meio - 12 ?> 162Z"/>
</svg>
