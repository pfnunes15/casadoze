<?php
/**
 * A previsão dos signos: a roda e a carta da semana.
 *
 * Doze botões em roda, e ao centro a constelação do que estiver escolhido. Ao
 * lado, a carta: o período, o elemento, o regente, a frase da semana, três
 * medidores e três números da sorte.
 *
 * **Vem desenhada com o signo do dia e muda no browser.** O servidor escolhe o
 * signo em que o sol anda hoje e desenha a carta desse; carregar noutro troca a
 * carta sem ir ao servidor, porque as doze estão todas no documento. É isso que
 * faz a roda responder no instante — e é também o que a mantém inteira sem
 * JavaScript: lá ficam as doze cartas, uma debaixo da outra.
 *
 * **A previsão não muda a cada recarga.** O que varia com a semana — os
 * medidores e os números — sai de um acaso semeado com o signo e com a
 * segunda-feira dessa semana. Ver App\Services\Signos, e a nota lá sobre porque
 * é que isso importa.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */

use App\Services\Signos;

$agora = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
$segunda = Signos::segundaFeira($agora);
$domingo = $segunda->modify('+6 days');

$doDia = Signos::doDia($agora);

$nomes     = explode('|', __('signos.names'));
$planetas  = explode('|', __('signos.planets'));
$elementos = explode('|', __('signos.elements'));
$cores     = explode('|', __('signos.colors'));
$medidores = explode('|', __('signos.meters'));
$sorte     = explode('|', __('signos.lucky'));

/* As datas por extenso. Sem a extensão `intl` cai para o formato do PHP, que diz
   o mês em inglês — feio, e melhor do que uma página em branco. */
$curto = static function (int $mês, int $dia) use ($agora): string {
    $data = $agora->setDate(2001, $mês, $dia);

    if (!class_exists(\IntlDateFormatter::class)) {
        return $data->format('j M');
    }

    return (new \IntlDateFormatter(
        str_replace('-', '_', current_locale()),
        \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'd MMM'
    ))->format($data) ?: $data->format('j M');
};

$diaDaSemana = static function (int $quantos) use ($segunda): string {
    $data = $segunda->modify('+' . $quantos . ' days');

    if (!class_exists(\IntlDateFormatter::class)) {
        return $data->format('l');
    }

    $nome = (new \IntlDateFormatter(
        str_replace('-', '_', current_locale()),
        \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'EEEE'
    ))->format($data) ?: $data->format('l');

    return mb_convert_case(mb_substr($nome, 0, 1), MB_CASE_UPPER) . mb_substr($nome, 1);
};

$semanaDe = $curto((int)$segunda->format('n'), (int)$segunda->format('j'));
$semanaA  = $curto((int)$domingo->format('n'), (int)$domingo->format('j'));
?>
<section class="secção secção--signos"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>
         data-signos data-escolhido="<?= (int)$doDia ?>">

  <?php /* O céu por trás da secção: cento e quarenta estrelas a piscar, uma em
           cada nove na cor do signo escolhido. Decoração, e por isso escondida
           de quem ouve a página. */ ?>
  <canvas class="signos__céu" data-céu aria-hidden="true"></canvas>

  <div class="miolo signos__miolo">

    <div class="signos__topo">
      <div class="signos__dizer">
        <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
          <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
        <?php endif; ?>
        <?php if (trim((string)$section['heading']) !== ''): ?>
          <<?= $headingTag ?> class="signos__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
        <?php endif; ?>
      </div>

      <span class="signos__semana" data-entra="sobe" data-atraso="140">
        <span aria-hidden="true">&#10022;</span>
        <?= e(__('signos.from')) ?> <?= e($semanaDe) ?> <?= e(__('signos.to')) ?> <?= e($semanaA) ?>
      </span>
    </div>

    <div class="signos__par">

      <?php /* A roda. Os botões estão postos por `transform` a partir do centro:
               rodar, afastar, e rodar ao contrário para o símbolo ficar
               direito. */ ?>
      <div class="roda" data-entra="sobe">
        <svg class="roda__aros" viewBox="0 0 400 400" aria-hidden="true">
          <circle cx="200" cy="200" r="196" fill="none" stroke="rgba(217,179,108,.35)" stroke-width="1"></circle>
          <circle cx="200" cy="200" r="146" fill="none" stroke="rgba(217,179,108,.25)" stroke-width="1"></circle>
          <circle class="roda__pontilhado" cx="200" cy="200" r="138" fill="none"
                  stroke-opacity=".5" stroke-width="1" stroke-dasharray="1 5"></circle>
          <g class="roda__divisórias">
            <?php for ($i = 0; $i < Signos::QUANTOS; $i++): ?>
              <?php
              $ângulo = deg2rad($i * 30 + 15 - 90);
              $x1 = 200 + cos($ângulo) * 146; $y1 = 200 + sin($ângulo) * 146;
              $x2 = 200 + cos($ângulo) * 196; $y2 = 200 + sin($ângulo) * 196;
              ?>
              <line x1="<?= round($x1, 1) ?>" y1="<?= round($y1, 1) ?>"
                    x2="<?= round($x2, 1) ?>" y2="<?= round($y2, 1) ?>"
                    stroke="rgba(217,179,108,.28)" stroke-width="1"></line>
            <?php endfor; ?>
          </g>
          <path d="M200 0 L207 12 L193 12Z" fill="#e8cf98"></path>
        </svg>

        <?php for ($i = 0; $i < Signos::QUANTOS; $i++): ?>
          <?php $ângulo = $i * 30; ?>
          <button type="button" class="roda__signo" data-signo="<?= $i ?>"
                  aria-label="<?= e($nomes[$i] ?? '') ?>"
                  aria-pressed="<?= $i === $doDia ? 'true' : 'false' ?>"
                  style="--volta: <?= $ângulo ?>deg">
            <span class="roda__símbolo"><?= Signos::símbolo($i) ?></span>
          </button>
        <?php endfor; ?>

        <div class="roda__centro">
          <?php for ($i = 0; $i < Signos::QUANTOS; $i++): ?>
            <?php $c = Signos::constelação($i); [$viva] = Signos::cores($i); ?>
            <svg class="constelação" viewBox="0 0 100 100" data-constelação="<?= $i ?>"
                 aria-hidden="true"<?= $i === $doDia ? '' : ' hidden' ?>>
              <?php foreach ($c['linhas'] as [$a, $b]): ?>
                <?php
                [$ax, $ay] = $c['estrelas'][$a]; [$bx, $by] = $c['estrelas'][$b];
                $traço = 'M' . $ax . ' ' . $ay . ' L' . $bx . ' ' . $by;
                ?>
                <path d="<?= e($traço) ?>" fill="none" stroke="<?= e($viva) ?>" stroke-opacity=".16"
                      stroke-width="7" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="<?= e($traço) ?>" fill="none" stroke="#e8cf98" stroke-opacity=".7"
                      stroke-width=".5" stroke-linecap="round"></path>
              <?php endforeach; ?>
              <?php foreach ($c['estrelas'] as [$x, $y, $r]): ?>
                <circle cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $r ?>" fill="#fff6dc"
                        style="filter: drop-shadow(0 0 2px #e8cf98)"></circle>
              <?php endforeach; ?>
            </svg>
          <?php endfor; ?>
        </div>
      </div>

      <?php /* As doze cartas, todas no documento. Onze estão escondidas; trocar
               de signo é mostrar outra. Sem JavaScript o `hidden` continua a
               valer e vê-se a do dia, que é a resposta certa. */ ?>
      <div class="signos__cartas" data-entra="sobe" data-atraso="120">
        <?php for ($i = 0; $i < Signos::QUANTOS; $i++): ?>
          <?php
          $p = Signos::periodo($i);
          $semana = Signos::semana($i, $segunda);
          [$viva, $esbatida] = Signos::cores($i);
          $elemento = Signos::elemento($i);
          ?>
          <article class="carta" data-carta="<?= $i ?>"<?= $i === $doDia ? '' : ' hidden' ?>
                   style="--viva: <?= e($viva) ?>; --esbatida: <?= e($esbatida) ?>">

            <div class="carta__topo">
              <div class="carta__nomes">
                <span class="carta__periodo">
                  <?= e($curto($p['começa']['mês'], $p['começa']['dia'])) ?>
                  &ndash;
                  <?= e($curto($p['acaba']['mês'], $p['acaba']['dia'])) ?>
                </span>
                <<?= $itemTag ?> class="carta__nome"><?= e($nomes[$i] ?? '') ?></<?= $itemTag ?>>
              </div>
              <span class="carta__símbolo" aria-hidden="true"><?= Signos::símbolo($i) ?></span>
            </div>

            <div class="carta__etiquetas">
              <span><?= e(__('signos.element')) ?> &middot; <?= e($elementos[$elemento] ?? '') ?></span>
              <span><?= e(__('signos.ruler')) ?> &middot; <?= e($planetas[$i] ?? '') ?></span>
            </div>

            <p class="carta__dito">&ldquo;<?= e(__('signos.text.' . $i)) ?>&rdquo;</p>

            <div class="carta__medidores">
              <?php foreach ($semana['medidores'] as $m => $valor): ?>
                <div class="medidor">
                  <span class="medidor__nome"><?= e($medidores[$m] ?? '') ?></span>
                  <span class="medidor__calha">
                    <i style="width: <?= (int)$valor * 20 ?>%; transition-delay: <?= $m * 0.12 ?>s"></i>
                  </span>
                  <span class="medidor__valor"><?= (int)$valor ?>/5</span>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="carta__sorte">
              <div class="sorte">
                <span class="sorte__nome"><?= e($sorte[0] ?? '') ?></span>
                <span class="sorte__valor"><?= (int)$semana['numero'] ?></span>
              </div>
              <div class="sorte">
                <span class="sorte__nome"><?= e($sorte[1] ?? '') ?></span>
                <span class="sorte__valor"><?= e($diaDaSemana($semana['diaDaSorte'])) ?></span>
              </div>
              <div class="sorte">
                <span class="sorte__nome"><?= e($sorte[2] ?? '') ?></span>
                <span class="sorte__valor">
                  <i class="sorte__ponto" style="background: <?= e($viva) ?>"></i>
                  <?= e($cores[$elemento] ?? '') ?>
                </span>
              </div>
            </div>

            <?php /* Partilhar. Usa a partilha do próprio sistema onde ela
                     existe — no telefone abre a folha de sempre —, e onde não
                     existe copia o endereço. Escondido até o js/site.js dizer
                     que uma das duas coisas é possível: um botão de partilha que
                     não partilha é pior do que não haver botão. */ ?>
            <button type="button" class="carta__partilhar" hidden
                    data-partilhar="<?= e($nomes[$i] ?? '') ?>">
              <span aria-hidden="true">&#8599;</span> <?= e(__('signos.share')) ?>
            </button>
          </article>
        <?php endfor; ?>
      </div>
    </div>

    <p class="signos__nota"><?= e(__('signos.note')) ?></p>
  </div>
</section>
