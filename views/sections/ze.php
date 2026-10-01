<?php
/**
 * Quem está por trás: o retrato ao lado do texto, e duas listas.
 *
 * «O que faço» e «o que não faço» são as duas listas, e são o que esta secção
 * tem de dizer: uma casa que lê cartas precisa de ser clara sobre o que promete,
 * e a segunda lista é a que faz essa clareza. Separadas pelo `rating` do item —
 * 1 para a primeira, 0 para a segunda — e não por dois campos de texto, para
 * poderem ter cinco linhas numa página e três noutra.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */
$itens = $section['items'] ?? [];

$faz    = array_filter($itens, static fn(array $i): bool => (int)($i['rating'] ?? 0) === 1);
$nãoFaz = array_filter($itens, static fn(array $i): bool => (int)($i['rating'] ?? 0) !== 1);
?>
<section class="secção secção--caixa"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">
    <div class="grelha grelha--larga" style="align-items: start">

      <?php if (trim((string)$section['image']) !== ''): ?>
        <img src="<?= e(media_url($section['image'])) ?>" alt="<?= e((string)$section['image_alt']) ?>"
             loading="lazy" decoding="async"
             style="aspect-ratio: 4/5; object-fit: cover; width: 100%">
      <?php endif; ?>

      <div>
        <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
          <p class="sobrescrita"><?= e((string)$section['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if (trim((string)$section['heading']) !== ''): ?>
          <<?= $headingTag ?> style="margin-block: 18px 20px"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
        <?php endif; ?>

        <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
          <div style="color: var(--letra-fraca)"><?= (string)$section['body'] ?></div>
        <?php endif; ?>

        <?php if ($faz !== [] || $nãoFaz !== []): ?>
        <div class="grelha" style="margin-top: 28px; gap: 24px">
          <?php foreach ([['faz', $faz, 'about.does'], ['nao-faz', $nãoFaz, 'about.doesnt']] as [$qual, $lista, $chave]): ?>
            <?php if ($lista !== []): ?>
            <div>
              <<?= $itemTag ?> class="agenda__rótulo" style="display:block; margin-bottom: 12px">
                <?= e(__($chave)) ?>
              </<?= $itemTag ?>>
              <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px">
                <?php foreach ($lista as $item): ?>
                  <li style="font-size: 17px; color: <?= $qual === 'faz' ? 'var(--letra)' : 'var(--letra-tenue)' ?>">
                    <?= e((string)$item['title']) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (trim((string)($section['cta_label'] ?? '')) !== '' && trim((string)($section['cta_url'] ?? '')) !== ''): ?>
          <p style="margin-top: 28px">
            <a class="botão" href="<?= e((string)$section['cta_url']) ?>"><?= e((string)$section['cta_label']) ?></a>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
