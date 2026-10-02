<?php
/**
 * O Grimório: as sessões gratuitas do Zé.
 *
 * Três vídeos do canal, com a miniatura, a duração e o produto que foi usado na
 * sessão. É a secção que liga o que se vê de graça ao que se vende: quem assiste
 * a um ritual com sálvia encontra o molho de sálvia a um clique.
 *
 * **As miniaturas ainda não existem.** Enquanto um vídeo não tiver endereço, o
 * cartão desenha a moldura tracejada com o botão de ver ao meio — que é o que a
 * maquete mostra, e é honesto: diz que ali vai estar um vídeo sem fingir que já
 * está. Preenchido o endereço, a moldura passa a ligação.
 *
 * O endereço do canal vem das Definições — Redes > YouTube — e não se escreve
 * aqui: é o mesmo que o rodapé usa.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */

$itens = $section['items'] ?? [];
$canal = trim((string)setting('social.youtube'));

$cta = trim((string)($section['cta_label'] ?? ''));
?>
<section class="secção secção--grimorio"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo grimorio__miolo">

    <div class="grimorio__topo">
      <div class="grimorio__dizer">
        <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
          <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if (trim((string)$section['heading']) !== ''): ?>
          <<?= $headingTag ?> class="grimorio__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
        <?php endif; ?>

        <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
          <p class="grimorio__texto" data-entra="sobe" data-atraso="140"><?= nl2br(e((string)$section['body'])) ?></p>
        <?php endif; ?>
      </div>

      <?php /* O botão do canal só existe com endereço. Sem ele não se desenha —
               um botão que não leva a lado nenhum é pior do que não haver. */ ?>
      <?php if ($cta !== '' && $canal !== ''): ?>
        <a class="grimorio__canal" href="<?= e($canal) ?>" rel="noopener" target="_blank" data-entra="sobe">
          <span aria-hidden="true">&#9654;</span>
          <span><?= e($cta) ?></span>
        </a>
      <?php endif; ?>
    </div>

    <?php if ($itens !== []): ?>
    <div class="grimorio__vídeos">
      <?php foreach ($itens as $índice => $item): ?>
        <?php
        $url      = trim((string)($item['url'] ?? ''));
        $duração  = trim((string)($item['caption'] ?? ''));
        $título   = trim((string)($item['title'] ?? ''));
        $produto  = trim((string)($item['subtitle'] ?? ''));
        $ondeEstá = trim((string)($item['link_label'] ?? '')) ?: '#loja';
        $capa     = trim((string)($item['image'] ?? ''));
        ?>
        <article class="vídeo" data-entra="sobe" data-atraso="<?= (int)$índice * 100 ?>">

          <?php /* Com endereço é uma ligação; sem ele é uma moldura à espera. A
                   diferença tem de estar no elemento e não só na cor: um `<a>`
                   sem `href` é alcançável pelo teclado e não faz nada. */ ?>
          <?php if ($url !== ''): ?>
            <a class="vídeo__moldura" href="<?= e($url) ?>" rel="noopener" target="_blank"
               aria-label="<?= e($título) ?>">
          <?php else: ?>
            <span class="vídeo__moldura vídeo__moldura--vazia">
          <?php endif; ?>

            <?php if ($capa !== ''): ?>
              <img class="vídeo__capa" src="<?= e(media_url($capa)) ?>" alt=""
                   loading="lazy" decoding="async">
            <?php else: ?>
              <span class="vídeo__aviso"><?= e(__('grimorio.thumb')) ?></span>
            <?php endif; ?>

            <span class="vídeo__ver" aria-hidden="true">&#9654;</span>

            <?php if ($duração !== ''): ?>
              <span class="vídeo__tempo"><?= e($duração) ?></span>
            <?php endif; ?>

          <?php if ($url !== ''): ?></a><?php else: ?></span><?php endif; ?>

          <?php if ($título !== ''): ?>
            <<?= $itemTag ?> class="vídeo__nome"><?= e($título) ?></<?= $itemTag ?>>
          <?php endif; ?>

          <?php if ($produto !== ''): ?>
            <span class="vídeo__usado">
              <?= e(__('grimorio.related')) ?>:
              <a href="<?= e($ondeEstá) ?>"><?= e($produto) ?></a>
            </span>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
