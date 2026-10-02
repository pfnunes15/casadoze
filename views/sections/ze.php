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
 * O retrato é o fundo de um `div` e não um `<img>`: leva uma sombra por dentro a
 * fechar-lhe as bordas e um fio a 14px da margem, e os dois têm de ficar por cima
 * da fotografia. A fotografia transborda a moldura em cinco por cento acima e
 * abaixo, para ter folga quando se mexe com o desenrolar.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */
$itens = $section['items'] ?? [];

$faz    = array_filter($itens, static fn(array $i): bool => (int)($i['rating'] ?? 0) === 1);
$nãoFaz = array_filter($itens, static fn(array $i): bool => (int)($i['rating'] ?? 0) !== 1);

$imagem = trim((string)$section['image']);
?>
<section class="secção secção--caixa secção--ze"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo ze__par">

    <?php if ($imagem !== ''): ?>
      <?php /* Entra por cortina: descobre-se de baixo para cima enquanto encolhe
               de 1,06 para 1. É a única da página que entra assim. */ ?>
      <div class="ze__retrato" data-entra="cortina">
        <div class="ze__foto" role="img" aria-label="<?= e($section['image_alt'] ?: ($section['heading'] ?? '')) ?>"
             style="background-image: url('<?= e(media_url($imagem)) ?>')"></div>
        <div class="ze__sombra" aria-hidden="true"></div>
        <div class="ze__fio" aria-hidden="true"></div>
      </div>
    <?php endif; ?>

    <div class="ze__dizer">
      <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
        <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
      <?php endif; ?>

      <?php if (trim((string)$section['heading']) !== ''): ?>
        <<?= $headingTag ?> class="ze__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
      <?php endif; ?>

      <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
        <div class="ze__prosa" data-entra="sobe" data-atraso="140"><?= (string)$section['body'] ?></div>
      <?php endif; ?>

      <?php if ($faz !== [] || $nãoFaz !== []): ?>
      <div class="ze__listas" data-entra="sobe" data-atraso="200">
        <?php foreach ([['sim', $faz, 'about.does', '&#10003;'], ['nao', $nãoFaz, 'about.doesnt', '&#10005;']] as [$qual, $lista, $chave, $sinal]): ?>
          <?php if ($lista !== []): ?>
          <div class="ze__lista">
            <?php /* O sinal é ornamento — o rótulo já diz o que a lista é —, e um
                     leitor de ecrã que anuncie «marca de visto» antes de «o que
                     faço» só acrescenta ruído. */ ?>
            <span class="ze__rótulo ze__rótulo--<?= $qual ?>">
              <span aria-hidden="true"><?= $sinal ?></span> <?= e(__($chave)) ?>
            </span>
            <span class="ze__linhas">
              <?php foreach ($lista as $i => $item): ?><?= $i > 0 ? '<br>' : '' ?><?= e((string)$item['title']) ?><?php endforeach; ?>
            </span>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (trim((string)($section['cta_label'] ?? '')) !== '' && trim((string)($section['cta_url'] ?? '')) !== ''): ?>
        <a class="ze__ligação" href="<?= e((string)$section['cta_url']) ?>" data-entra="sobe">
          <?= e((string)$section['cta_label']) ?> <span aria-hidden="true">&rarr;</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>
