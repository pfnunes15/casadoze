<?php
/**
 * Princípios: a fila numerada do que não muda.
 *
 * **Os números são postos pela folha de estilo** — um contador de CSS — e não
 * se escrevem no backoffice. Escritos, apagar o segundo de quatro deixava a
 * lista a contar 01, 03, 04, e alguém tinha de ir renumerar à mão.
 *
 * Serve três sítios do desenho com a mesma forma: «Quatro coisas que não
 * mudam» na Casa, «Da marcação à mesa» nas Consultas e as garantias que a
 * Casa queira contar por passos.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */
$itens = $section['items'] ?? [];

if ($itens === []) {
    return;
}
?>
<section class="secção secção--principios"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">

    <div class="principios__dizer">
      <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
        <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
      <?php endif; ?>
      <?php if (trim((string)$section['heading']) !== ''): ?>
        <<?= $headingTag ?> class="principios__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
      <?php endif; ?>
      <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
        <p class="principios__entrada" data-entra="sobe" data-atraso="140"><?= nl2br(e((string)$section['body'])) ?></p>
      <?php endif; ?>
    </div>

    <ol class="principios">
      <?php foreach ($itens as $i => $item): ?>
        <li class="principio" data-entra="sobe" data-atraso="<?= (int)$i * 90 ?>">
          <<?= $itemTag ?> class="principio__nome"><?= e((string)($item['title'] ?? '')) ?></<?= $itemTag ?>>
          <?php if (trim((string)($item['body'] ?? '')) !== ''): ?>
            <p class="principio__dito"><?= nl2br(e((string)$item['body'])) ?></p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
