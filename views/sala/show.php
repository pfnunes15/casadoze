<?php
/**
 * O perfil de quem lê as cartas.
 *
 * Ver `docs/maquete/Sala.dc.html`, o painel que abre ao carregar num cartão.
 * Aqui é uma página e não um painel: um perfil tem endereço próprio, guarda-se
 * nos favoritos e manda-se a alguém.
 *
 * **Marcar leva à ficha da consulta e não a uma agenda desta pessoa.** A agenda
 * é da consulta — ver App\Controllers\SalaController, que explica porquê.
 *
 * Sem as estrelas e sem as avaliações, pela mesma razão de sempre: não há
 * tabela, e uma média inventada é um número que ninguém disse.
 *
 * @var array $pessoa
 * @var array $consultas
 * @var array $próximas
 * @var array $outras
 */

use App\Models\Practitioner;
use App\Services\Datas;

$bodyClass = 'com-cabeçalho';

$pageTitle       = (string)$pessoa['name'];
$metaDescription = mb_substr(trim(strip_tags((string)($pessoa['bio'] ?? ''))), 0, 160);

$retrato = trim((string)$pessoa['image']);
$specs   = Practitioner::lista($pessoa, 'specialties');
$fmts    = Practitioner::lista($pessoa, 'formats');
$langs   = Practitioner::lista($pessoa, 'languages');
?>
<div class="perfil">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(locale_url('/')) ?>"><?= e((string)setting('site.name', '')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <a href="<?= e(locale_url('/sala')) ?>"><?= e(__('sala.title')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e((string)$pessoa['name']) ?></span>
  </nav>

  <div class="perfil__par">

    <div class="perfil__retrato">
      <?php if ($retrato !== ''): ?>
        <?php /* The portrait is the largest thing on a profile: it is never
                 lazy, and it is asked for ahead of the rest. */ ?>
        <?php $fontes = \App\Services\Imagens::srcset($retrato); ?>
        <img src="<?= e($fontes[1] ?? media_url($retrato)) ?>"
             <?= $fontes !== null ? 'srcset="' . e($fontes[0]) . '" sizes="(min-width: 900px) 520px, 100vw"' : '' ?>
             alt="<?= e((string)($pessoa['image_alt'] ?: $pessoa['name'])) ?>"
             width="1254" height="1254" fetchpriority="high" decoding="async"
                 style="object-position: <?= e((string)($pessoa['image_focus'] ?? 'center')) ?>">
      <?php else: ?>
        <span class="taróloga__iniciais perfil__iniciais"><?= e(Practitioner::iniciais($pessoa)) ?></span>
      <?php endif; ?>
    </div>

    <div class="perfil__dizer">
      <span class="perfil__papel"><?= e((string)$pessoa['role']) ?></span>
      <h1 class="perfil__nome"><?= e((string)$pessoa['name']) ?></h1>

      <?php if (trim((string)$pessoa['quote']) !== ''): ?>
        <blockquote class="perfil__dito">&ldquo;<?= e((string)$pessoa['quote']) ?>&rdquo;</blockquote>
      <?php endif; ?>

      <?php if (trim((string)($pessoa['bio'] ?? '')) !== ''): ?>
        <div class="perfil__prosa"><?= (string)$pessoa['bio'] ?></div>
      <?php endif; ?>

      <dl class="perfil__quadro">
        <?php if (trim((string)$pessoa['deck']) !== ''): ?>
          <div><dt><?= e(__('sala.deck')) ?></dt><dd><?= e((string)$pessoa['deck']) ?></dd></div>
        <?php endif; ?>
        <?php if (trim((string)$pessoa['sign']) !== ''): ?>
          <div><dt><?= e(__('sala.sign')) ?></dt><dd><?= e((string)$pessoa['sign']) ?></dd></div>
        <?php endif; ?>
        <?php if ((int)$pessoa['years'] > 0): ?>
          <div><dt><?= e(__('sala.at_table')) ?></dt><dd><?= e(__('sala.years', ['n' => (int)$pessoa['years']])) ?></dd></div>
        <?php endif; ?>
        <?php if ($fmts !== []): ?>
          <div><dt><?= e(__('consultas.format')) ?></dt><dd><?= e(implode(' · ', $fmts)) ?></dd></div>
        <?php endif; ?>
        <?php if ($langs !== []): ?>
          <div><dt><?= e(__('sala.filter.lang')) ?></dt><dd><?= e(implode(' · ', $langs)) ?></dd></div>
        <?php endif; ?>
      </dl>

      <?php if ($specs !== []): ?>
        <div class="perfil__specs">
          <?php foreach ($specs as $s): ?>
            <span class="taróloga__spec"><?= e($s) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($consultas !== []): ?>
    <section class="perfil__leituras">
      <h2 class="outras__título"><?= e(__('sala.reads')) ?></h2>

      <div class="perfil__lista">
        <?php foreach ($consultas as $c): ?>
          <?php
          $cid   = (int)$c['id'];
          $ficha = locale_url('/consultas/' . rawurlencode((string)$c['slug']));
          $vaga  = $próximas[$cid] ?? null;
          ?>
          <a class="leitura" href="<?= e($ficha) ?>">
            <span class="leitura__nome"><?= e((string)$c['name']) ?></span>
            <span class="leitura__meta">
              <?php if ((int)$c['duration_min'] > 0): ?><?= e(duration_text((int)$c['duration_min'])) ?> &middot; <?php endif; ?>
              <?= $c['price_cents'] !== null ? e(money((int)$c['price_cents'])) : e(__('consultas.on_request')) ?>
            </span>
            <?php if (!empty($c['is_bookable'])): ?>
              <span class="leitura__vaga<?= $vaga === null ? ' leitura__vaga--sem' : '' ?>">
                <i aria-hidden="true"></i>
                <?= $vaga === null
                      ? e(__('consultas.no_slots'))
                      : e(__('consultas.next_slot', ['when' => Datas::diaCurto(new \DateTimeImmutable($vaga)) . ', ' . (new \DateTimeImmutable($vaga))->format('H:i')])) ?>
              </span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php /* Dito e não escondido: quem vem de um perfil espera marcar com
               aquela pessoa, e o que o site marca é uma consulta. */ ?>
      <p class="perfil__nota"><?= e(__('sala.booking_note')) ?></p>
    </section>
  <?php endif; ?>

  <?php if ($outras !== []): ?>
    <section class="outras">
      <h2 class="outras__título"><?= e(__('sala.others')) ?></h2>
      <div class="outras__fila">
        <?php foreach ($outras as $o): ?>
          <a class="outra" href="<?= e(locale_url('/sala/' . rawurlencode((string)$o['slug']))) ?>">
            <span class="outra__nome"><?= e((string)$o['name']) ?></span>
            <span class="outra__meta"><?= e((string)$o['role']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
