<?php
/**
 * O Grimório: as sessões.
 *
 * Ver `docs/maquete/Grimorio.dc.html`. Os cartões com a moldura 16:9, o tema, a
 * duração e a peça que foi usada.
 *
 * **Uma sessão sem endereço desenha a moldura e não finge.** Nenhuma das três
 * tem vídeo escrito ainda: o cartão mostra o botão de ver ao meio de uma
 * moldura tracejada, que é o que a maquete faz — diz que ali vai estar uma
 * sessão sem prometer que já está.
 *
 * **Sem contagem de visualizações.** Vem da API do YouTube, que não está
 * ligada, e um número escrito à mão hoje é um número errado amanhã.
 *
 * @var array $sessões
 * @var array $produtos  id da sessão => peças usadas
 * @var array $temas
 */

use Admedia\Cms\Video as Endereço;
use Admedia\Shop\Shop;
use App\Models\Video;

$bodyClass = 'com-cabeçalho';

$pageTitle       = __('grimorio.title');
$metaDescription = __('grimorio.intro');

$canal = trim((string)setting('social.youtube', ''));
?>
<div class="grimorio-pagina">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(locale_url('/')) ?>"><?= e((string)setting('site.name', '')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e(__('grimorio.title')) ?></span>
  </nav>

  <header class="grimorio-pagina__topo">
    <div class="sala__dizer">
      <span class="sobrescrita"><?= e(__('grimorio.eyebrow')) ?></span>
      <h1 class="sala__h1"><?= e(__('grimorio.title')) ?></h1>
      <p class="sala__entrada"><?= e(__('grimorio.intro')) ?></p>
    </div>
    <?php if ($canal !== ''): ?>
      <a class="consultas__paraLoja" href="<?= e($canal) ?>" rel="noopener" target="_blank">
        <?= e(__('grimorio.channel')) ?> <span aria-hidden="true">&rarr;</span>
      </a>
    <?php endif; ?>
  </header>

  <?php if ($temas !== []): ?>
    <div class="filtros filtros--sala" data-filtros-sala hidden>
      <div class="filtros__fila">
        <span class="filtros__rótulo"><?= e(__('loja.filter')) ?></span>
        <button type="button" class="filtros__grupo" data-grupo="tema" aria-expanded="false">
          <?= e(__('grimorio.theme')) ?>
          <span class="filtros__seta" aria-hidden="true">&#9662;</span>
        </button>
        <button type="button" class="filtros__limpar" data-limpar hidden><?= e(__('loja.clear')) ?></button>
      </div>
      <div class="filtros__opções" data-opções="tema" hidden>
        <?php foreach ($temas as $t): ?>
          <button type="button" class="filtros__opção" data-filtro="tema"
                  data-valor="<?= e($t) ?>" aria-pressed="false"><?= e($t) ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="grimorio-pagina__fila" data-sala>
    <?php foreach ($sessões as $i => $v): ?>
      <?php
      $id     = (int)$v['id'];
      $página = locale_url('/grimorio/' . rawurlencode((string)$v['slug']));
      $noTubo = Video::noYouTube($v);
      $peças  = $produtos[$id] ?? [];
      ?>
      <article class="sessão taróloga" data-entra="sobe" data-atraso="<?= (int)$i * 90 ?>"
               data-tema="<?= e((string)$v['theme']) ?>">

        <a class="sessão__moldura<?= $noTubo === null ? ' sessão__moldura--vazia' : '' ?>"
           href="<?= e($página) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($noTubo !== null): ?>
            <img src="<?= e(Endereço::youtubeThumb($noTubo)) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <span class="sessão__ver" aria-hidden="true">&#9654;</span>
          <?php if (trim((string)$v['duration']) !== ''): ?>
            <span class="sessão__duração"><?= e((string)$v['duration']) ?></span>
          <?php endif; ?>
        </a>

        <div class="taróloga__dizer">
          <?php if (trim((string)$v['theme']) !== ''): ?>
            <span class="taróloga__papel"><?= e((string)$v['theme']) ?></span>
          <?php endif; ?>

          <h2 class="taróloga__nome"><a href="<?= e($página) ?>"><?= e((string)$v['title']) ?></a></h2>

          <?php if (trim((string)$v['summary']) !== ''): ?>
            <p class="sessão__dito"><?= e((string)$v['summary']) ?></p>
          <?php endif; ?>

          <?php if ($peças !== []): ?>
            <div class="sessão__peças">
              <span class="sessão__peçasRótulo"><?= e(__('grimorio.used')) ?></span>
              <?php foreach ($peças as $p): ?>
                <a href="<?= e(Shop::to('product', ['slug' => (string)$p['slug']])) ?>"><?= e((string)$p['name']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="loja__vazio" hidden data-vazio>
    <span class="loja__vazioTítulo"><?= e(__('grimorio.empty')) ?></span>
    <button type="button" class="botão botão--ouro" data-limpar hidden><span><?= e(__('loja.clear')) ?></span></button>
  </div>
</div>
