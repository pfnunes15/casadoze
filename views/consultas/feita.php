<?php
/**
 * A marcação feita, e o que se pode fazer com ela.
 *
 * Chega-se aqui pelo `token` e não pelo código: o código diz-se ao telefone e é
 * curto de propósito, e um endereço adivinhável dava a qualquer pessoa o nome, o
 * telefone e a razão por que outra vem — que numa casa destas é o que menos se
 * quer à mostra.
 *
 * **O botão de desmarcar desaparece a certa altura**, e não é por decoração: a
 * Casa escreve em Definições com quanta antecedência é que ainda se desmarca pelo
 * site. Passado esse prazo, a mesa já está preparada, e o que se quer é que a
 * pessoa telefone — não que desmarque às duas da manhã sem ninguém saber.
 *
 * @var array      $marcacao
 * @var array|null $consulta
 * @var bool       $acabou
 */
$pageTitle = __('marcacao.done.title');
$bodyClass = 'com-cabeçalho';

$quando    = new \DateTimeImmutable((string)$marcacao['starts_at']);
$cancelada = $marcacao['status'] === 'cancelada';

/* Quanto falta, em horas, e a partir de quando é que já não se desmarca pelo
   site. `readings.cancel_hours` a zero quer dizer «até à hora», que é uma
   decisão possível e não um campo esquecido. */
$horasDeAviso = (int)setting('readings.cancel_hours', '24');
$aTempo       = !$acabou && !$cancelada
             && $quando > (new \DateTimeImmutable())->modify('+' . $horasDeAviso . ' hours');

$bem = $flashes['marcacao.ok'][0] ?? null;
$mal = $flashes['marcacao.bad'][0] ?? null;
?>
<section class="secção">
  <div class="miolo">
    <div class="marcação">

      <?php if ($bem !== null): ?><p class="aviso aviso--bom"><?= e($bem) ?></p><?php endif; ?>
      <?php if ($mal !== null): ?><p class="aviso aviso--mau"><?= e($mal) ?></p><?php endif; ?>

      <div>
        <p class="sobrescrita"><?= e(__('consultas.title')) ?></p>
        <h1 style="margin-top: 18px"><?= e(__('marcacao.done.title')) ?></h1>
      </div>

      <?php if ($cancelada): ?>
        <p class="aviso aviso--mau"><?= e(__('marcacao.done.cancelled')) ?></p>
      <?php elseif ($acabou): ?>
        <p class="aviso"><?= e(__('marcacao.done.past')) ?></p>
      <?php endif; ?>

      <div>
        <p class="agenda__rótulo"><?= e(__('marcacao.done.code')) ?></p>
        <p class="marcação__código"><?= e((string)$marcacao['code']) ?></p>
        <p class="ajuda" style="color: var(--letra-tenue)"><?= e(__('marcacao.done.code_help')) ?></p>
      </div>

      <dl class="marcação__lista">
        <div>
          <dt><?= e(__('marcacao.done.what')) ?></dt>
          <dd>
            <?= e((string)$marcacao['consultation_name']) ?>
            <?php if ($consulta !== null): ?>
              <br><span style="font-size: 16px; color: var(--letra-fraca)">
                <?= e(\App\Models\Consultation::linha($consulta)) ?>
              </span>
            <?php endif; ?>
          </dd>
        </div>

        <div>
          <dt><?= e(__('marcacao.done.when')) ?></dt>
          <?php /* A hora a que acaba sai da duração e não de uma coluna: duas
                   verdades sobre a mesma coisa acabam sempre a discordar. */ ?>
          <dd>
            <?= e(format_date((string)$marcacao['starts_at'], 'l, j \d\e F \d\e Y')) ?>
            &middot; <?= e($quando->format('H:i')) ?>
            <?php if ((int)$marcacao['duration_min'] > 0): ?>
              &ndash; <?= e($quando->modify('+' . (int)$marcacao['duration_min'] . ' minutes')->format('H:i')) ?>
            <?php endif; ?>
          </dd>
        </div>

        <div>
          <dt><?= e(__('marcacao.done.where')) ?></dt>
          <dd>
            <?= e(__('consultas.mode.' . (string)$marcacao['mode'])) ?>
            <?php if ((string)$marcacao['mode'] === 'online'): ?>
              <br><span style="font-size: 16px; color: var(--letra-fraca)">
                <?= e(setting('readings.online_note') ?: __('marcacao.done.online_note')) ?>
              </span>
            <?php else: ?>
              <?php
              /* A morada da Casa, das Definições. Guardar uma cópia dela na
                 marcação deixava duas, e no dia em que a Casa mudasse de sítio
                 as marcações antigas apontavam para o sítio errado — que é
                 exactamente quando isso dá problema. */
              $linhas = array_values(array_filter(
                  array_map('trim', preg_split('/\R/', setting('contact.address')) ?: []),
                  static fn(string $l): bool => $l !== ''
              ));
              ?>
              <?php if ($linhas !== []): ?>
                <br><span style="font-size: 16px; color: var(--letra-fraca)">
                  <?= implode('<br>', array_map('e', $linhas)) ?>
                </span>
              <?php endif; ?>
            <?php endif; ?>
          </dd>
        </div>
      </dl>

      <?php if ($aTempo): ?>
        <form method="post" action="<?= e(locale_url('/marcacao/' . $marcacao['token'] . '/cancelar')) ?>"
              onsubmit="return confirm(<?= e(json_encode(__('marcacao.done.cancel_confirm'), JSON_UNESCAPED_UNICODE)) ?>)">
          <?= csrf_field() ?>
          <button type="submit" class="botão"><?= e(__('marcacao.done.cancel')) ?></button>
        </form>
      <?php elseif (!$acabou && !$cancelada): ?>
        <?php /* Fora do prazo. O que se oferece é o telefone — que é o que a Casa
                 quer que aconteça, e não um botão que recusa. */ ?>
        <?php $telefone = trim(setting('contact.phone')); ?>
        <?php if ($telefone !== ''): ?>
          <p class="ressalva">
            <a href="tel:<?= e(\Admedia\Cms\Phone::dial($telefone)) ?>"><?= e($telefone) ?></a>
          </p>
        <?php endif; ?>
      <?php endif; ?>

      <p class="ressalva"><?= e(__('consultas.disclaimer')) ?></p>
    </div>
  </div>
</section>
