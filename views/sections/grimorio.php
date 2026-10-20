<?php
/**
 * O Grimório: as sessões gratuitas do Zé.
 *
 * Três vídeos do canal, com a miniatura, a duração e o produto que foi usado na
 * sessão. É a secção que liga o que se vê de graça ao que se vende: quem assiste
 * a um ritual com sálvia encontra o molho de sálvia a um clique.
 *
 * **As sessões vêm da tabela `videos` e não dos itens deste bloco.** Eram itens
 * até o Grimório passar a ter página própria; uma sessão escrita em dois sítios
 * é uma que fica desactualizada num deles. O que este bloco traz é o título, a
 * frase e o botão do canal.
 *
 * **A miniatura vem do YouTube**, do endereço de cada sessão. Enquanto esse
 * endereço não estiver escrito, o cartão desenha a moldura tracejada com o
 * botão de ver ao meio — diz que ali vai estar um vídeo sem fingir que já está.
 *
 * O cartão leva à página da sessão **neste site** e não ao YouTube: é lá que
 * estão os capítulos e a peça que foi usada, e é de lá que se vai à loja.
 *
 * O endereço do canal vem das Definições — Redes > YouTube — e não se escreve
 * aqui: é o mesmo que o rodapé usa.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */

/* **As sessões vêm da tabela e não dos itens deste bloco.** Eram itens até o
   Grimório passar a ter página própria; uma sessão escrita em dois sítios é uma
   que fica desactualizada num deles. O bloco fica, porque é ele que põe o
   Grimório na porta de entrada — o que ele traz é o título, a frase e o botão
   do canal. Ver App\Models\Video e a migração 0019. */
$itens = \App\Models\Video::published(null, 3);
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
        /* A ligação é para a página da sessão neste site e não para o YouTube:
           é lá que estão os capítulos e a peça que foi usada, e é de lá que se
           vai para a loja. O botão de ver o canal continua a ir para fora. */
        $url      = locale_url('/grimorio/' . rawurlencode((string)$item['slug']));
        $duração  = trim((string)$item['duration']);
        $título   = trim((string)$item['title']);
        $noTubo   = \App\Models\Video::noYouTube($item);
        $capa     = $noTubo === null ? '' : \Admedia\Cms\Video::youtubeThumb($noTubo);

        $peças    = \App\Models\Video::produtos((int)$item['id']);
        $produto  = $peças === [] ? '' : (string)$peças[0]['name'];
        $ondeEstá = $peças === [] ? '' : \Admedia\Shop\Shop::to('product', ['slug' => (string)$peças[0]['slug']]);
        ?>
        <article class="vídeo" data-entra="sobe" data-atraso="<?= (int)$índice * 100 ?>">

          <?php /* Com endereço é uma ligação; sem ele é uma moldura à espera. A
                   diferença tem de estar no elemento e não só na cor: um `<a>`
                   sem `href` é alcançável pelo teclado e não faz nada. */ ?>
          <a class="vídeo__moldura<?= $capa === '' ? ' vídeo__moldura--vazia' : '' ?>"
             href="<?= e($url) ?>" aria-label="<?= e($título) ?>">

            <?php if ($capa !== ''): ?>
              <img class="vídeo__capa" src="<?= e($capa) ?>" alt=""
                   loading="lazy" decoding="async">
            <?php else: ?>
              <span class="vídeo__aviso"><?= e(__('grimorio.thumb')) ?></span>
            <?php endif; ?>

            <span class="vídeo__ver" aria-hidden="true">&#9654;</span>

            <?php if ($duração !== ''): ?>
              <span class="vídeo__tempo"><?= e($duração) ?></span>
            <?php endif; ?>

          </a>

          <?php if ($título !== ''): ?>
            <<?= $itemTag ?> class="vídeo__nome"><?= e($título) ?></<?= $itemTag ?>>
          <?php endif; ?>

          <?php if ($produto !== '' && $ondeEstá !== ''): ?>
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
