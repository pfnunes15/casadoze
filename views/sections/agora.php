<?php
/**
 * Agora na Casa: o próximo sabbat e o mês da lua.
 *
 * Duas coisas que mudam sozinhas e que ninguém tem de ir lá escrever: qual é o
 * sabbat a seguir e quantos dias faltam, e em que fase está a lua em cada dia
 * deste mês. Ambas saem de App\Services\Lua — ver lá porque é a lua média e não
 * a verdadeira, e o que isso quer dizer.
 *
 * **O que o editor escreve é só o kit**: a frase que o descreve, o preço e o
 * botão. O nome do kit é o do sabbat, e por isso não se escreve — em Novembro
 * passa a ser o do Yule sem ninguém lhe tocar, que é o ponto.
 *
 * A fita das luas sai para fora da margem da secção — `margin-inline` negativa —
 * e isso é de propósito: é uma fita de ponta a ponta do ecrã, e não uma coisa
 * dentro da coluna de texto.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */

use App\Services\Lua;

$opções = \Admedia\Cms\Models\PageSection::options($section);

/* A hora da Casa e não a do servidor: um servidor em Londres mostrava a lua de
   ontem a quem está no Covil, uma hora por dia. */
$agora = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));

$sabbat = Lua::próximoSabbat($agora);
$dias   = Lua::mes($agora);

$faseDeAgora = Lua::fase($agora->getTimestamp());
$nomeDeAgora = __('lua.' . Lua::nomeDaFase($faseDeAgora));
$luzDeAgora  = Lua::luzPorCento($faseDeAgora);

/* As datas por extenso, na língua de quem lê. O IntlDateFormatter é o que sabe
   que em francês se diz «octobre 2026» e em alemão «Oktober 2026»; sem a
   extensão `intl` instalada volta ao formato do PHP, que diz o mês em inglês —
   feio, e melhor do que uma página em branco. */
$porExtenso = static function (\DateTimeImmutable $quando, string $padrão, string $reserva): string {
    if (!class_exists(\IntlDateFormatter::class)) {
        return $quando->format($reserva);
    }

    return (new \IntlDateFormatter(
        str_replace('-', '_', current_locale()),
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        null,
        null,
        $padrão
    ))->format($quando) ?: $quando->format($reserva);
};

/* «Outubro de 2026», e não «outubro 2026»: em português o mês vem ligado ao ano
   por «de», e a primeira letra sobe porque aqui o mês abre uma linha. O
   IntlDateFormatter devolve-o em minúscula, que é o correcto no meio de uma
   frase e não no princípio de uma. */
$mêsPorExtenso = mb_convert_case(
    mb_substr($porExtenso($agora, "LLLL 'de' yyyy", 'F Y'), 0, 1),
    MB_CASE_UPPER
) . mb_substr($porExtenso($agora, "LLLL 'de' yyyy", 'F Y'), 1);
$dataDoSabbat   = $porExtenso($sabbat['quando'], "d 'de' LLLL", 'j F');

$faltam = (int)$sabbat['faltam'];
$quanto = $faltam === 0
    ? __('agora.today')
    : ($faltam === 1 ? __('agora.left_1') : __('agora.left', ['n' => $faltam]));

$preço = trim((string)($opções['price'] ?? ''));
?>
<section class="secção secção--agora"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">

    <div class="agora__par">
      <div class="agora__dizer">
        <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
          <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
        <?php endif; ?>

        <span class="agora__rótulo" data-entra="sobe" data-atraso="60"><?= e(__('agora.next')) ?></span>

        <?php /* O nome do sabbat é o título da secção. Vem da conta e não do
                 campo do editor — em Novembro é Yule sem ninguém lhe tocar. */ ?>
        <<?= $headingTag ?> class="agora__sabbat" data-entra="sobe" data-atraso="120"><?= e($sabbat['nome']) ?></<?= $headingTag ?>>

        <div class="agora__quando" data-entra="sobe" data-atraso="180">
          <span class="agora__data"><?= e($dataDoSabbat) ?></span>
          <span class="agora__contagem"><?= e($quanto) ?></span>
        </div>
      </div>

      <div class="agora__kit" data-entra="sobe" data-atraso="150">
        <?php /* O desenho é o da vela, como na maquete: um kit de sabbat leva
                 sempre uma. Semente fixa para sair sempre igual. */ ?>
        <canvas class="agora__desenho" width="600" height="800"
                data-desenho="candle" data-semente="300" aria-hidden="true"></canvas>

        <div class="agora__kitDizer">
          <<?= $itemTag ?> class="agora__kitNome"><?= e(__('agora.kit', ['sabbat' => $sabbat['nome']])) ?></<?= $itemTag ?>>

          <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
            <p class="agora__kitTexto"><?= nl2br(e((string)$section['body'])) ?></p>
          <?php endif; ?>

          <?php if ($preço !== ''): ?>
            <span class="agora__preço"><?= e($preço) ?></span>
          <?php endif; ?>

          <?php $cta = trim((string)($section['cta_label'] ?? '')); $url = trim((string)($section['cta_url'] ?? '')); ?>
          <?php if ($cta !== '' && $url !== ''): ?>
            <a class="agora__botão" href="<?= e($url) ?>">
              <?= e($cta) ?> <span aria-hidden="true">&rarr;</span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php /* A fita das luas: de ponta a ponta, por cima do fundo escuro. Está fora
           do `.miolo` porque não é conteúdo da coluna — é uma faixa. */ ?>
  <div class="luas">
    <div class="luas__dentro">
      <div class="luas__topo">
        <span class="luas__nome">
          <span class="luas__etiqueta"><?= e(__('agora.lunar')) ?></span>
          <span class="luas__mês"><?= e($mêsPorExtenso) ?></span>
        </span>
        <span>
          <?= e(__('agora.now')) ?> &middot;
          <b class="luas__hoje"><?= e($nomeDeAgora) ?> &middot; <?= (int)$luzDeAgora ?>%</b>
        </span>
      </div>

      <div class="luas__fila" style="--quantos: <?= count($dias) ?>">
        <?php foreach ($dias as $d): ?>
          <?php
          $nome = __('lua.' . Lua::nomeDaFase($d['fase']));
          $classe = 'lua' . ($d['hoje'] ? ' lua--hoje' : '') . ($d['marco'] ? ' lua--marco' : '');
          ?>
          <?php /* O `title` diz o dia, a fase e a luz a quem passar o rato; o
                   mesmo texto vai no `aria-label` do `svg`, para quem ouve a
                   página receber a mesma coisa. */ ?>
          <div class="<?= $classe ?>">
            <svg viewBox="0 0 40 40" role="img"
                 aria-label="<?= e($d['dia'] . ' · ' . $nome . ' · ' . $d['luz'] . '%') ?>">
              <title><?= e($d['dia'] . ' · ' . $nome . ' · ' . $d['luz'] . '%') ?></title>
              <circle cx="20" cy="20" r="17" fill="#1d1724"></circle>
              <path d="<?= e($d['desenho']) ?>" data-fase="<?= number_format($d['fase'], 4, '.', '') ?>" fill="#e8cf98"></path>
              <circle class="lua__aro" cx="20" cy="20" r="17" fill="none" stroke-width="1.2"></circle>
              <circle class="lua__halo" cx="20" cy="20" r="21.5" fill="none" stroke-width="1"></circle>
            </svg>
            <span class="lua__dia"><?= (int)$d['dia'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
