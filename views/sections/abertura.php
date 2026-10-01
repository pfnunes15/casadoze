<?php
/**
 * A abertura: a fotografia com que a Casa se apresenta.
 *
 * Passa por baixo do cabeçalho, que nesta página é transparente. O retrato é
 * esbatido nas bordas em redondo — a máscara está na folha de estilo —, e por
 * cima dele sobem brasas desenhadas num canvas.
 *
 * @var array  $section
 * @var string $headingTag
 * @var bool   $eager
 */
$opções = \Admedia\Cms\Models\PageSection::options($section);

$brasas = max(0, min(200, (int)($opções['embers'] ?? 70)));

// Dois botões, e cada um só existe com texto **e** endereço. Um botão com texto
// e sem destino é um botão que não faz nada, e quem carrega nele não percebe
// porquê.
$botões = [];
foreach ([['cta_label', 'cta_url', true], ['cta2_label', 'cta2_url', false]] as [$chaveTexto, $chaveUrl, $doBloco]) {
    $texto = trim((string)($doBloco ? ($section[$chaveTexto] ?? '') : ($opções[$chaveTexto] ?? '')));
    $url   = trim((string)($doBloco ? ($section[$chaveUrl]   ?? '') : ($opções[$chaveUrl]   ?? '')));
    if ($texto !== '' && $url !== '') {
        $botões[] = ['texto' => $texto, 'url' => $url];
    }
}
?>
<section class="abertura"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>

  <?php if (trim((string)$section['image']) !== ''): ?>
  <div class="abertura__retrato">
    <?php /* O retrato é desenhado pela folha de estilo, em `::after`, porque leva
             uma máscara radial — e uma máscara num `<img>` deixava o texto
             alternativo sem onde viver. Daí este `role="img"` com o rótulo:
             quem ouve a página recebe a descrição, e quem a vê recebe a
             fotografia mascarada. */ ?>
    <div role="img" aria-label="<?= e($section['image_alt'] ?: ($section['heading'] ?? '')) ?>"
         style="position:absolute;inset:0"></div>
    <?php if ($brasas > 0): ?>
      <?php /* `aria-hidden` porque são decoração: um leitor de ecrã que anuncie
               «canvas» no meio da abertura só atrapalha. */ ?>
      <canvas class="abertura__brasas" data-brasas="<?= $brasas ?>" aria-hidden="true"></canvas>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="abertura__véu" aria-hidden="true"></div>

  <div class="abertura__dizer">
    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?>><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
      <p class="abertura__linha"><?= nl2br(e((string)$section['body'])) ?></p>
    <?php endif; ?>

    <?php if ($botões !== []): ?>
    <div class="abertura__botões">
      <?php foreach ($botões as $i => $botão): ?>
        <a class="botão <?= $i === 0 ? 'botão--cheio' : '' ?>" href="<?= e($botão['url']) ?>">
          <?= e($botão['texto']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
