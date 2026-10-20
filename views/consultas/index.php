<?php
/**
 * As consultas: a lista.
 *
 * Ver `docs/maquete/Consultas.dc.html`, ecrã «Consultas». Cada consulta é um
 * cartão largo com o desenho à esquerda e o que ela é à direita, e por baixo
 * duas secções de texto: como funciona, e o que se pergunta antes de marcar.
 *
 * **Os filtros por formato e por tipo não estão.** A maquete tem-nos, e com
 * quatro consultas um filtro que reduz quatro a três é um botão que não serve
 * para nada. Voltam quando a Sala trouxer mais gente a dar consultas — e aí
 * fazem falta a sério.
 *
 * **«Oferecer» não está.** É o vale de oferta, e não há vales: nem tabela, nem
 * código, nem validade. Um botão que promete um vale que não existe é pior do
 * que botão nenhum. Ver `docs/maquete/NOTAS.md`.
 *
 * O «Como funciona» e o «Antes de marcares» vêm de blocos da página, e não
 * estão escritos aqui: são texto da Casa, e texto da Casa muda sem programador.
 *
 * @var array $consultas
 * @var array $próximas  id da consulta => data da próxima vaga
 */

use App\Models\Consultation;
use App\Services\Datas;

$bodyClass = 'com-cabeçalho';

$pageTitle       = __('consultas.title');
$metaDescription = mb_substr(trim((string)setting('readings.intro', '')), 0, 160);

$romanos = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

$entrada = trim((string)setting('readings.intro', ''));
?>
<div class="consultas">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(locale_url('/')) ?>"><?= e((string)setting('site.name', '')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e(__('consultas.title')) ?></span>
  </nav>

  <header class="consultas__topo">
    <div class="consultas__dizer">
      <h1 class="consultas__h1"><?= e(__('consultas.title')) ?></h1>
      <?php if ($entrada !== ''): ?>
        <p class="consultas__entradaPágina"><?= nl2br(e($entrada)) ?></p>
      <?php endif; ?>
    </div>
    <a class="consultas__paraLoja" href="<?= e(\Admedia\Shop\Shop::to('index')) ?>">
      <?= e(__('consultas.to_shop')) ?> <span aria-hidden="true">&rarr;</span>
    </a>
  </header>

  <div class="consultas__lista">
    <?php foreach ($consultas as $i => $consulta): ?>
      <?php
      $id       = (int)$consulta['id'];
      $duração  = (int)$consulta['duration_min'];
      $preço    = $consulta['price_cents'];
      $marcável = !empty($consulta['is_bookable']);
      $ficha    = locale_url('/consultas/' . rawurlencode((string)$consulta['slug']));
      $próxima  = $próximas[$id] ?? null;
      ?>
      <article class="cartaConsulta" data-entra="sobe" data-atraso="<?= (int)$i * 90 ?>">

        <span class="cartaConsulta__ordinal" aria-hidden="true"><?= e($romanos[$i + 1] ?? (string)($i + 1)) ?></span>

        <div class="cartaConsulta__corpo">
          <span class="cartaConsulta__tipo"><?= e(__('consultas.mode.' . (string)$consulta['mode'])) ?></span>

          <h2 class="cartaConsulta__nome"><a href="<?= e($ficha) ?>"><?= e((string)$consulta['name']) ?></a></h2>

          <?php if (trim((string)($consulta['summary'] ?? '')) !== ''): ?>
            <p class="cartaConsulta__dito"><?= e((string)$consulta['summary']) ?></p>
          <?php endif; ?>

          <div class="cartaConsulta__meta">
            <?php /* A duração só aparece quando há duração: os trabalhos
                     espirituais não duram meia hora, duram o que durarem. */ ?>
            <?php if ($duração > 0): ?>
              <span class="cartaConsulta__duração"><?= e(duration_text($duração)) ?></span>
            <?php endif; ?>
            <?php foreach (Consultation::modos($consulta) as $modo): ?>
              <span class="cartaConsulta__modo"><?= e(__('consultas.mode.' . $modo)) ?></span>
            <?php endforeach; ?>
          </div>

          <div class="cartaConsulta__pé">
            <div class="cartaConsulta__dinheiro">
              <span class="cartaConsulta__preço">
                <?= $preço !== null ? e(money((int)$preço)) : e(__('consultas.on_request')) ?>
              </span>

              <?php /* A próxima vaga, lida e não escrita. Dita a quem está a
                       decidir: «há vaga na quinta» é o que faz carregar no
                       botão, e «sem vagas» é o que evita a decepção de o
                       carregar para nada. */ ?>
              <?php if ($marcável): ?>
                <span class="cartaConsulta__vaga<?= $próxima === null ? ' cartaConsulta__vaga--sem' : '' ?>">
                  <i aria-hidden="true"></i>
                  <?= $próxima === null
                        ? e(__('consultas.no_slots'))
                        : e(__('consultas.next_slot', ['when' => Datas::diaCurto(new \DateTimeImmutable($próxima)) . ', ' . (new \DateTimeImmutable($próxima))->format('H:i')])) ?>
                </span>
              <?php endif; ?>
            </div>

            <a class="cartaConsulta__botão" href="<?= e($ficha) ?>">
              <?= e(__($marcável ? 'consultas.book' : 'consultas.talk')) ?>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <p class="consultas__aviso"><?= e(__('consultas.disclaimer')) ?></p>
</div>
