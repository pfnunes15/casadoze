<?php
/**
 * Uma consulta, com a agenda.
 *
 * Ver `docs/maquete/Consultas.dc.html`, ecrã «Consulta»: o que ela é à
 * esquerda, o calendário do mês e as horas do dia à direita, e por baixo como
 * se preparar e as outras consultas.
 *
 * **O calendário funciona sem JavaScript.** Os dias são âncoras para a lista de
 * horas desse dia, e as listas estão todas no documento — quem não tem
 * JavaScript vê a agenda inteira, dia a dia, e escolhe uma hora. O js/site.js
 * não acrescenta horas nenhumas: esconde as dos outros dias e troca de mês sem
 * recarregar. Uma agenda que só existe depois de o JavaScript correr é uma
 * agenda que não existe para quem a procura.
 *
 * **As horas são todas uma escolha do mesmo `name`.** Só a escolhida é enviada,
 * e por isso as dos dias escondidos não precisam de ser desligadas.
 *
 * O que a maquete tem e esta página não tem: as avaliações (não há tabela), o
 * vale de oferta (não há vales) e a escolha de duração dentro da mesma consulta
 * (cada duração é uma consulta no catálogo, com o seu horário).
 *
 * @var array                 $consulta
 * @var array                 $dias      Y-m-d => [ [id, hora, livres], … ]
 * @var array                 $fechados  conjunto de ConsultationClosure
 * @var \DateTimeImmutable[]  $meses
 * @var array                 $outras
 */

use App\Models\Consultation;
use App\Models\ConsultationClosure;
use App\Services\Datas;
use App\Services\Lua;

$bodyClass = 'com-cabeçalho';

$id       = (int)$consulta['id'];
$marcável = !empty($consulta['is_bookable']);
$duração  = (int)$consulta['duration_min'];
$preço    = $consulta['price_cents'];
$modos    = Consultation::modos($consulta);

$pageTitle       = (string)$consulta['name'];
$metaDescription = mb_substr(trim(strip_tags((string)($consulta['summary'] ?: $consulta['body']))), 0, 160);

$hoje = (new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get())))->format('Y-m-d');

/* O primeiro dia com vagas é o que abre. Sem vagas nenhumas não abre nada e o
   calendário fica a dizer que está cheio, que é a verdade. */
$primeiroDia = array_key_first($dias) ?: '';

/* As horas da Casa. Um servidor noutro fuso mostrava a agenda uma hora ao lado
   — ver a mesma nota no bloco «Agora na Casa». */
$fuso = new \DateTimeZone(date_default_timezone_get());

$nomesDosDias = Datas::iniciais();
?>
<div class="consulta-ficha">

  <nav class="migalhas" aria-label="<?= e(__('loja.breadcrumb')) ?>">
    <a href="<?= e(locale_url('/')) ?>"><?= e((string)setting('site.name', '')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <a href="<?= e(locale_url('/consultas')) ?>"><?= e(__('consultas.title')) ?></a>
    <span class="migalhas__traço" aria-hidden="true">/</span>
    <span aria-current="page"><?= e((string)$consulta['name']) ?></span>
  </nav>

  <div class="consulta-ficha__par">

    <div class="consulta-ficha__dizer">
      <span class="consulta-ficha__tipo"><?= e(__('consultas.mode.' . (string)$consulta['mode'])) ?></span>
      <h1 class="consulta-ficha__nome"><?= e((string)$consulta['name']) ?></h1>

      <div class="consulta-ficha__linha">
        <span class="consulta-ficha__preço">
          <?= $preço !== null ? e(money((int)$preço)) : e(__('consultas.on_request')) ?>
        </span>
        <?php if ($duração > 0): ?>
          <span class="consulta-ficha__duração"><?= e(duration_text($duração)) ?></span>
        <?php endif; ?>
      </div>

      <?php if (trim((string)($consulta['body'] ?? '')) !== ''): ?>
        <div class="consulta-ficha__prosa"><?= (string)$consulta['body'] ?></div>
      <?php elseif (trim((string)($consulta['summary'] ?? '')) !== ''): ?>
        <p class="consulta-ficha__prosa"><?= e((string)$consulta['summary']) ?></p>
      <?php endif; ?>

      <div class="consulta-ficha__quadro">
        <div class="consulta-ficha__célula">
          <span class="peça__rótulo"><?= e(__('consultas.format')) ?></span>
          <span class="consulta-ficha__valor">
            <?php foreach ($modos as $i => $m): ?><?= $i > 0 ? ' · ' : '' ?><?= e(__('consultas.mode.' . $m)) ?><?php endforeach; ?>
          </span>
          <?php $notaOnline = trim((string)setting('readings.online_note', '')); ?>
          <?php if ($notaOnline !== '' && in_array('online', $modos, true)): ?>
            <span class="consulta-ficha__nota"><?= e($notaOnline) ?></span>
          <?php endif; ?>
        </div>

        <?php if ($duração > 0): ?>
          <div class="consulta-ficha__célula">
            <span class="peça__rótulo"><?= e(__('consultas.duration')) ?></span>
            <span class="consulta-ficha__valor"><?= e(duration_text($duração)) ?></span>
            <span class="consulta-ficha__nota"><?= e(__('consultas.duration.note')) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php /* A agenda. */ ?>
    <div class="agenda" data-agenda>
      <?php if (!$marcável): ?>

        <?php /* Uma consulta que se prepara depois de uma conversa não tem
                 horas para escolher, e inventar-lhe um calendário era mandar
                 alguém escolher uma hora que não quer dizer nada. */ ?>
        <div class="agenda__conversa">
          <span class="agenda__conversaTítulo"><?= e(__('consultas.by_talk')) ?></span>
          <p class="agenda__conversaDito"><?= e(__('consultas.by_talk.body')) ?></p>
          <a class="botão botão--ouro" href="<?= e(locale_url('/#contacto')) ?>">
            <span><?= e(__('consultas.talk')) ?></span>
          </a>
        </div>

      <?php elseif ($dias === []): ?>

        <div class="agenda__conversa">
          <span class="agenda__conversaTítulo"><?= e(__('consultas.no_slots')) ?></span>
          <p class="agenda__conversaDito"><?= e(__('consultas.no_slots.body')) ?></p>
        </div>

      <?php else: ?>

        <form class="agenda__forma" method="post" action="<?= e(locale_url('/marcar')) ?>">
          <?= csrf_field() ?>

          <div class="agenda__calendário">
            <?php foreach ($meses as $m => $mês): ?>
              <?php
              $primeiroDoMês = $mês->setTime(0, 0);
              $últimoDoMês   = $primeiroDoMês->modify('last day of this month');
              /* Em que coluna começa o mês. `N` dá 1 à segunda e 7 ao domingo,
                 que é a semana portuguesa e a da maquete. */
              $vazias = (int)$primeiroDoMês->format('N') - 1;
              ?>
              <?php /* `data-fora` e não `hidden`: quem não tem JavaScript tem
                       de poder ver os meses todos, um debaixo do outro. É o
                       js/site.js que esconde, e só depois de pôr `agenda--viva`
                       na agenda — ver a folha de estilo. Com `hidden` no HTML,
                       sem JavaScript a agenda acabava em Outubro. */ ?>
              <div class="mês" data-mês="<?= e($mês->format('Y-m')) ?>"<?= $m === 0 ? '' : ' data-fora' ?>>
                <div class="mês__topo">
                  <?php /* As setas são escondidas no HTML e mostradas pelo
                           js/site.js: sem ele não trocam de mês nenhum, e os
                           meses estão todos à vista de qualquer maneira. */ ?>
                  <button type="button" class="mês__seta" data-mês-anterior hidden
                          aria-label="<?= e(__('consultas.prev_month')) ?>"<?= $m === 0 ? ' disabled' : '' ?>>&lsaquo;</button>
                  <span class="mês__nome"><?= e(Datas::mêsEAno($mês)) ?></span>
                  <button type="button" class="mês__seta" data-mês-seguinte hidden
                          aria-label="<?= e(__('consultas.next_month')) ?>"<?= $m === count($meses) - 1 ? ' disabled' : '' ?>>&rsaquo;</button>
                </div>

                <div class="mês__grelha">
                  <?php foreach ($nomesDosDias as $n): ?>
                    <span class="mês__inicial" aria-hidden="true"><?= e(mb_strtoupper($n)) ?></span>
                  <?php endforeach; ?>

                  <?php for ($v = 0; $v < $vazias; $v++): ?>
                    <span class="mês__vazio" aria-hidden="true"></span>
                  <?php endfor; ?>

                  <?php for ($dia = $primeiroDoMês; $dia <= $últimoDoMês; $dia = $dia->modify('+1 day')): ?>
                    <?php
                    $ymd     = $dia->format('Y-m-d');
                    $temVaga = isset($dias[$ymd]);
                    $fechado = ConsultationClosure::isClosed($fechados, $ymd, $id);
                    $éHoje   = $ymd === $hoje;

                    /* A lua, como na maquete: um ponto nos dias de lua nova e de
                       lua cheia. Calculada ao meio-dia — a fase à meia-noite
                       apanha tanto a do dia anterior como a deste. */
                    $faseDoDia = Lua::nomeDaFase(Lua::fase($dia->setTime(12, 0)->getTimestamp()));
                    $marco     = $faseDoDia === 0 ? 'nova' : ($faseDoDia === 4 ? 'cheia' : '');

                    $classes = 'dia'
                        . ($temVaga ? ' dia--livre' : '')
                        . ($fechado ? ' dia--fechado' : '')
                        . ($éHoje ? ' dia--hoje' : '');
                    ?>
                    <?php if ($temVaga): ?>
                      <a class="<?= $classes ?>" href="#dia-<?= e($ymd) ?>" data-dia="<?= e($ymd) ?>"
                         aria-label="<?= e(Datas::diaCurto(new \DateTimeImmutable($ymd)) . ' · ' . __('consultas.n_slots', ['n' => count($dias[$ymd])])) ?>">
                        <?= (int)$dia->format('j') ?>
                        <?php if ($marco !== ''): ?><i class="dia__lua dia__lua--<?= $marco ?>" aria-hidden="true"></i><?php endif; ?>
                      </a>
                    <?php else: ?>
                      <span class="<?= $classes ?>" aria-hidden="true">
                        <?= (int)$dia->format('j') ?>
                        <?php if ($marco !== ''): ?><i class="dia__lua dia__lua--<?= $marco ?>" aria-hidden="true"></i><?php endif; ?>
                      </span>
                    <?php endif; ?>
                  <?php endfor; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <ul class="mês__legenda">
              <li><i class="dia__lua dia__lua--nova" aria-hidden="true"></i><?= e(__('lua.0')) ?></li>
              <li><i class="dia__lua dia__lua--cheia" aria-hidden="true"></i><?= e(__('lua.4')) ?></li>
              <li><i class="mês__legendaHoje" aria-hidden="true"></i><?= e(__('consultas.today')) ?></li>
            </ul>
          </div>

          <div class="agenda__horas">
            <?php foreach ($dias as $ymd => $horas): ?>
              <fieldset class="horas" id="dia-<?= e($ymd) ?>" data-horas="<?= e($ymd) ?>"<?= $ymd === $primeiroDia ? '' : ' data-fora' ?>>
                <legend class="horas__dia"><?= e(Datas::diaPorExtenso(new \DateTimeImmutable((string)$ymd))) ?></legend>
                <div class="horas__fila">
                  <?php foreach ($horas as $h): ?>
                    <label class="hora">
                      <input type="radio" name="slot_id" value="<?= (int)$h['id'] ?>" required>
                      <span class="hora__valor"><?= e($h['hora']) ?></span>
                      <?php if ((int)$h['livres'] === 1): ?>
                        <span class="hora__última"><?= e(__('consultas.last_one')) ?></span>
                      <?php endif; ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              </fieldset>
            <?php endforeach; ?>

            <?php /* Quem marca. Os campos são os que o BookingController lê —
                     ver lá o `check()`, que é quem os valida. */ ?>
            <div class="agenda__quem">
              <label class="campo__rótulo" for="m-nome"><?= e(__('marcacao.name')) ?></label>
              <input class="campo" id="m-nome" type="text" name="name" required maxlength="120"
                     autocomplete="name" value="<?= e((string)old('name')) ?>">

              <label class="campo__rótulo" for="m-email"><?= e(__('marcacao.email')) ?></label>
              <input class="campo" id="m-email" type="email" name="email" required maxlength="190"
                     autocomplete="email" value="<?= e((string)old('email')) ?>">

              <label class="campo__rótulo" for="m-tel">
                <?= e(__('marcacao.phone')) ?>
                <span class="campo__opcional"><?= e(__('marcacao.phone_optional')) ?></span>
              </label>
              <input class="campo" id="m-tel" type="tel" name="phone" maxlength="40"
                     autocomplete="tel" value="<?= e((string)old('phone')) ?>">

              <?php if (count($modos) > 1): ?>
                <span class="campo__rótulo"><?= e(__('marcacao.mode')) ?></span>
                <div class="agenda__modos">
                  <?php foreach ($modos as $i => $m): ?>
                    <label class="hora hora--modo">
                      <input type="radio" name="mode" value="<?= e($m) ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                      <span class="hora__valor"><?= e(__('consultas.mode.' . $m)) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <input type="hidden" name="mode" value="<?= e($modos[0] ?? 'presencial') ?>">
              <?php endif; ?>

              <?php if ((int)$consulta['max_party'] > 1): ?>
                <label class="campo__rótulo" for="m-quantos"><?= e(__('marcacao.people')) ?></label>
                <input class="campo" id="m-quantos" type="number" name="people" min="1"
                       max="<?= (int)$consulta['max_party'] ?>" value="<?= e((string)(old('people') ?: 1)) ?>">
              <?php else: ?>
                <input type="hidden" name="people" value="1">
              <?php endif; ?>

              <label class="campo__rótulo" for="m-nota"><?= e(__('marcacao.note')) ?></label>
              <textarea class="campo campo--prosa" id="m-nota" name="note" rows="3"
                        maxlength="2000"><?= e((string)old('note')) ?></textarea>
              <span class="consulta-ficha__nota"><?= e(__('marcacao.note_help')) ?></span>

              <?php /* O alçapão. Uma pessoa nunca o vê; um robô preenche-o por o
                       encontrar no documento. Ver BookingController::store. */ ?>
              <div class="fora-do-ecrã" aria-hidden="true">
                <label for="m-website">Website</label>
                <input id="m-website" type="text" name="website" tabindex="-1" autocomplete="off">
              </div>

              <label class="aceito">
                <input type="checkbox" name="consent" value="1" required>
                <span class="aceito__marca" aria-hidden="true"></span>
                <span><?= e(__('marcacao.consent')) ?></span>
              </label>

              <button type="submit" class="agenda__botão"><?= e(__('marcacao.submit')) ?></button>
            </div>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </div>

  <?php /* Como te preparar. Três frases que valem para qualquer consulta; estão
           no catálogo das línguas e não num bloco porque esta página ainda não
           é uma página do CMS. Quando for, passam a ser um bloco e a Casa
           escreve-as. */ ?>
  <section class="preparar">
    <div class="preparar__dizer">
      <span class="sobrescrita"><?= e(__('consultas.before')) ?></span>
      <h2 class="preparar__título"><?= e(__('consultas.prepare')) ?></h2>
    </div>
    <div class="preparar__fila">
      <?php for ($p = 1; $p <= 3; $p++): ?>
        <div class="preparar__item">
          <span class="preparar__nome"><?= e(__('consultas.prepare.' . $p)) ?></span>
          <span class="preparar__dito"><?= e(__('consultas.prepare.' . $p . '.body')) ?></span>
        </div>
      <?php endfor; ?>
    </div>
  </section>

  <?php if ($outras !== []): ?>
    <section class="outras">
      <h2 class="outras__título"><?= e(__('consultas.others')) ?></h2>
      <div class="outras__fila">
        <?php foreach ($outras as $o): ?>
          <a class="outra" href="<?= e(locale_url('/consultas/' . rawurlencode((string)$o['slug']))) ?>">
            <span class="outra__nome"><?= e((string)$o['name']) ?></span>
            <span class="outra__meta">
              <?php if ((int)$o['duration_min'] > 0): ?><?= e(duration_text((int)$o['duration_min'])) ?> &middot; <?php endif; ?>
              <?= $o['price_cents'] !== null ? e(money((int)$o['price_cents'])) : e(__('consultas.on_request')) ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <p class="consultas__aviso"><?= e(__('consultas.disclaimer')) ?></p>
</div>
