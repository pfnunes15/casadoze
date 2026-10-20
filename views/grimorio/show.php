<?php
/**
 * Uma sessão do Grimório.
 *
 * Ver `docs/maquete/Grimorio.dc.html`, a vista de um vídeo. O leitor em cima, a
 * descrição e os capítulos, e as peças usadas ao lado.
 *
 * **O vídeo só é carregado quando alguém carrega no botão.** Um `<iframe>` do
 * YouTube posto no documento traz consigo os guiões e os cookies do YouTube
 * antes de alguém ter dito que quer ver — e a página de cookies da Casa diz que
 * este site não usa cookies de publicidade. Até lá é a miniatura, que é uma
 * imagem.
 *
 * Sem endereço escrito, a moldura diz que ali vai estar uma sessão, sem fingir
 * que já está.
 *
 * @var array $sessão
 * @var array $capítulos
 * @var array $produtos
 * @var array $outras
 */

use Admedia\Cms\Video as Endereço;
use Admedia\Shop\Shop;
use App\Models\Video;
use App\Services\Datas;

$bodyClass = 'com-cabeçalho';

$pageTitle       = (string)$sessão['title'];
$metaDescription = mb_substr(trim((string)$sessão['summary']), 0, 160);

$noTubo = Video::noYouTube($sessão);
?>
<div class="sessão-pagina">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(locale_url('/')) ?>"><?= e((string)setting('site.name', '')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <a href="<?= e(locale_url('/grimorio')) ?>"><?= e(__('grimorio.title')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e((string)$sessão['title']) ?></span>
  </nav>

  <?php /* O leitor. Com endereço é um botão que troca a miniatura pelo iframe;
           sem endereço é a moldura tracejada. */ ?>
  <div class="leitor<?= $noTubo === null ? ' leitor--vazio' : '' ?>"
       <?= $noTubo !== null ? 'data-leitor="' . e($noTubo['id']) . '" data-começo="' . (int)$noTubo['start'] . '"' : '' ?>>
    <?php if ($noTubo !== null): ?>
      <img class="leitor__miniatura" src="<?= e(Endereço::youtubeThumb($noTubo)) ?>"
           alt="<?= e((string)$sessão['title']) ?>">
      <button type="button" class="leitor__ver" data-ver>
        <span class="leitor__seta" aria-hidden="true">&#9654;</span>
        <span class="fora-do-ecrã"><?= e(__('grimorio.play')) ?></span>
      </button>
      <span class="leitor__nota"><?= e(__('grimorio.loads_youtube')) ?></span>
    <?php else: ?>
      <span class="leitor__seta" aria-hidden="true">&#9654;</span>
      <span class="leitor__porVir"><?= e(__('grimorio.coming')) ?></span>
    <?php endif; ?>
    <?php if (trim((string)$sessão['duration']) !== ''): ?>
      <span class="sessão__duração"><?= e((string)$sessão['duration']) ?></span>
    <?php endif; ?>
  </div>

  <div class="sessão-pagina__par">

    <div class="sessão-pagina__dizer">
      <?php if (trim((string)$sessão['theme']) !== ''): ?>
        <span class="consulta-ficha__tipo"><?= e((string)$sessão['theme']) ?></span>
      <?php endif; ?>

      <h1 class="consulta-ficha__nome"><?= e((string)$sessão['title']) ?></h1>

      <?php if ($sessão['published_at'] !== null): ?>
        <span class="sessão__quando"><?= e(Datas::diaCurto(new \DateTimeImmutable((string)$sessão['published_at']))) ?></span>
      <?php endif; ?>

      <?php if (trim((string)$sessão['summary']) !== ''): ?>
        <p class="consulta-ficha__prosa"><?= e((string)$sessão['summary']) ?></p>
      <?php endif; ?>

      <?php if (trim((string)($sessão['body'] ?? '')) !== ''): ?>
        <div class="consulta-ficha__prosa"><?= (string)$sessão['body'] ?></div>
      <?php endif; ?>

      <?php if ($capítulos !== []): ?>
        <div class="capitulos">
          <h2 class="outras__título"><?= e(__('grimorio.chapters')) ?></h2>
          <ol class="capitulos__lista">
            <?php foreach ($capítulos as $c): ?>
              <li class="capitulo">
                <?php if ($c['tempo'] !== ''): ?>
                  <span class="capitulo__tempo"><?= e($c['tempo']) ?></span>
                <?php endif; ?>
                <span class="capitulo__título"><?= e($c['título']) ?></span>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($produtos !== []): ?>
      <aside class="sessão-pagina__peças">
        <span class="resumo__título"><?= e(__('grimorio.used_in')) ?></span>
        <div class="sessão-pagina__lista">
          <?php foreach ($produtos as $p): ?>
            <?php
            $desenho = '';
            foreach (\Admedia\Shop\Models\Attribute::forProduct((int)$p['id']) as $a) {
                if (mb_strtolower((string)$a['name']) === 'desenho') { $desenho = (string)$a['value']; }
            }
            ?>
            <a class="sessão-pagina__peça" href="<?= e(Shop::to('product', ['slug' => (string)$p['slug']])) ?>">
              <span class="sessão-pagina__moldura">
                <?php if ($desenho !== ''): ?>
                  <canvas width="600" height="800" data-desenho="<?= e($desenho) ?>"
                          data-semente="<?= (int)$p['id'] ?>" aria-hidden="true"></canvas>
                <?php endif; ?>
              </span>
              <span class="sessão-pagina__peçaDizer">
                <span class="sessão-pagina__peçaNome"><?= e((string)$p['name']) ?></span>
                <span class="sessão-pagina__peçaPreço"><?= e(money((int)$p['price_cents'])) ?></span>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </aside>
    <?php endif; ?>
  </div>

  <?php if ($outras !== []): ?>
    <section class="outras">
      <h2 class="outras__título"><?= e(__('grimorio.others')) ?></h2>
      <div class="outras__fila">
        <?php foreach ($outras as $o): ?>
          <a class="outra" href="<?= e(locale_url('/grimorio/' . rawurlencode((string)$o['slug']))) ?>">
            <span class="outra__nome"><?= e((string)$o['title']) ?></span>
            <span class="outra__meta">
              <?= e((string)$o['theme']) ?><?php if (trim((string)$o['duration']) !== ''): ?> &middot; <?= e((string)$o['duration']) ?><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
