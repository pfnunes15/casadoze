<?php
/**
 * A Sala: a equipa.
 *
 * Ver `docs/maquete/Sala.dc.html`. Os cartões com o retrato — ou as iniciais,
 * onde ainda não há retrato —, o que cada uma lê, em que formatos e em que
 * línguas, e os filtros por cima.
 *
 * **Sem estrelas.** A maquete põe «★ 4,9 · 212 avaliações» em cada cartão. Não
 * há tabela de avaliações, e inventar uma média é pôr no site um número que
 * ninguém disse — o mesmo erro do stock a 12. Ver `docs/maquete/NOTAS.md`.
 *
 * **Sem «A Casa escolhe por mim».** O botão da maquete escolhe uma taróloga ao
 * acaso; com a agenda ainda a ser da consulta e não da pessoa, escolher uma
 * pessoa não muda nada no que se marca a seguir.
 *
 * Os filtros filtram no browser, como os da loja, e pela mesma razão: são
 * quatro cartões já desenhados.
 *
 * @var array $pessoas
 * @var array $consultas  id da pessoa => consultas que dá
 */

use App\Models\Practitioner;

$bodyClass = 'com-cabeçalho';

$pageTitle       = __('sala.title');
$metaDescription = __('sala.intro');

/* Os filtros são construídos do que as pessoas declaram, e não de uma lista
   escrita: uma especialidade nova aparece no filtro no dia em que alguém a
   escrever no backoffice. */
$grupos = [];
foreach (['specialties' => 'sala.filter.spec', 'formats' => 'sala.filter.fmt', 'languages' => 'sala.filter.lang'] as $coluna => $chave) {
    $valores = [];
    foreach ($pessoas as $p) {
        foreach (Practitioner::lista($p, $coluna) as $v) {
            $valores[$v] = true;
        }
    }
    if ($valores === []) {
        continue;
    }
    $valores = array_keys($valores);
    sort($valores);
    $grupos[] = ['chave' => $coluna, 'título' => __($chave), 'opções' => $valores];
}
?>
<div class="sala">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(locale_url('/')) ?>"><?= e((string)setting('site.name', '')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e(__('sala.title')) ?></span>
  </nav>

  <header class="sala__topo">
    <div class="sala__dizer">
      <span class="sobrescrita"><?= e(__('sala.eyebrow')) ?></span>
      <h1 class="sala__h1"><?= e(__('sala.title')) ?></h1>
      <p class="sala__entrada"><?= e(__('sala.intro')) ?></p>
    </div>
    <span class="loja__conta"><?= e(__(count($pessoas) === 1 ? 'sala.one' : 'sala.many', ['n' => count($pessoas)])) ?></span>
  </header>

  <?php if ($grupos !== []): ?>
    <div class="filtros filtros--sala" data-filtros-sala hidden>
      <div class="filtros__fila">
        <span class="filtros__rótulo"><?= e(__('loja.filter')) ?></span>
        <?php foreach ($grupos as $g): ?>
          <button type="button" class="filtros__grupo" data-grupo="<?= e($g['chave']) ?>" aria-expanded="false">
            <?= e($g['título']) ?>
            <span class="filtros__seta" aria-hidden="true">&#9662;</span>
          </button>
        <?php endforeach; ?>
        <button type="button" class="filtros__limpar" data-limpar hidden><?= e(__('loja.clear')) ?></button>
      </div>

      <?php foreach ($grupos as $g): ?>
        <div class="filtros__opções" data-opções="<?= e($g['chave']) ?>" hidden>
          <?php foreach ($g['opções'] as $o): ?>
            <button type="button" class="filtros__opção" data-filtro="<?= e($g['chave']) ?>"
                    data-valor="<?= e($o) ?>" aria-pressed="false"><?= e($o) ?></button>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="sala__fila" data-sala>
    <?php foreach ($pessoas as $i => $p): ?>
      <?php
      $id      = (int)$p['id'];
      $perfil  = locale_url('/sala/' . rawurlencode((string)$p['slug']));
      $retrato = trim((string)$p['image']);
      $specs   = Practitioner::lista($p, 'specialties');
      $fmts    = Practitioner::lista($p, 'formats');
      $langs   = Practitioner::lista($p, 'languages');
      ?>
      <article class="taróloga" data-entra="sobe" data-atraso="<?= (int)$i * 90 ?>"
               data-specialties="<?= e(implode('|', $specs)) ?>"
               data-formats="<?= e(implode('|', $fmts)) ?>"
               data-languages="<?= e(implode('|', $langs)) ?>">

        <a class="taróloga__retrato" href="<?= e($perfil) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($retrato !== ''): ?>
            <img src="<?= e(media_url($retrato)) ?>" alt="" loading="lazy">
          <?php else: ?>
            <?php /* Sem retrato, as iniciais. Um rectângulo vazio onde devia
                     estar uma cara diz que falta uma fotografia; as iniciais
                     dizem de quem é o lugar. */ ?>
            <span class="taróloga__iniciais"><?= e(Practitioner::iniciais($p)) ?></span>
          <?php endif; ?>
        </a>

        <div class="taróloga__dizer">
          <span class="taróloga__papel"><?= e((string)$p['role']) ?></span>
          <h2 class="taróloga__nome"><a href="<?= e($perfil) ?>"><?= e((string)$p['name']) ?></a></h2>

          <?php if ($specs !== []): ?>
            <div class="taróloga__specs">
              <?php foreach (array_slice($specs, 0, 3) as $s): ?>
                <span class="taróloga__spec"><?= e($s) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="taróloga__meta">
            <?php if ((int)$p['years'] > 0): ?>
              <span><?= e(__('sala.years', ['n' => (int)$p['years']])) ?></span>
            <?php endif; ?>
            <?php if ($fmts !== []): ?><span><?= e(implode(' · ', $fmts)) ?></span><?php endif; ?>
            <?php if ($langs !== []): ?><span><?= e(implode(' · ', $langs)) ?></span><?php endif; ?>
          </div>

          <div class="taróloga__pé">
            <span class="taróloga__quantas">
              <?= e(__(count($consultas[$id] ?? []) === 1 ? 'sala.reads_one' : 'sala.reads_many',
                       ['n' => count($consultas[$id] ?? [])])) ?>
            </span>
            <a class="taróloga__botão" href="<?= e($perfil) ?>"><?= e(__('sala.profile')) ?></a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="loja__vazio" hidden data-vazio>
    <span class="loja__vazioTítulo"><?= e(__('sala.empty')) ?></span>
    <button type="button" class="botão botão--ouro" data-limpar hidden><span><?= e(__('loja.clear')) ?></span></button>
  </div>

  <?php /* O que vale para todas. É o que a maquete põe por baixo da fila, e é
           o que responde à pergunta que uma pessoa faz ao ver quatro nomes:
           «então e qual escolho?» */ ?>
  <section class="principios-sala">
    <ol class="principios">
      <?php for ($n = 1; $n <= 3; $n++): ?>
        <li class="principio">
          <h2 class="principio__nome"><?= e(__('sala.rule.' . $n)) ?></h2>
          <p class="principio__dito"><?= e(__('sala.rule.' . $n . '.body')) ?></p>
        </li>
      <?php endfor; ?>
    </ol>
  </section>
</div>
