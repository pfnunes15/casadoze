<?php
/**
 * As consultas da Casa: a lista numerada, ao lado da mesa de tarot.
 *
 * **Mostra consultas; não as guarda.** Estão em `consultations`, com a duração, o
 * preço e o modo, e é de lá que esta lista vem — ver App\Models\Consultation.
 *
 * A composição é a da maquete: duas colunas que se partem em duas linhas quando
 * a largura acaba. À esquerda a mesa de tarot — ver views/partials/tarot.php —, à
 * direita a sobrescrita, o título, a frase de entrada e as consultas numeradas em
 * romanos, separadas por riscos e não por caixas: uma lista e não uma grelha de
 * cartões.
 *
 * **Não há agenda aqui.** A maquete não a tem: cada consulta leva um botão, e
 * marcar é uma conversa que começa noutro lado. A versão anterior desenhava um
 * selector de dia e hora dentro de cada cartão, que é desenho que a maquete
 * nunca pediu.
 *
 * **Sem preço diz «Sob consulta»**, que é o que a maquete escreve nos trabalhos
 * espirituais — e é a frase certa para o que ainda não tem número. Quem o
 * escrever em Consultas vê-o aparecer aqui.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */

use App\Models\Consultation;

$consultas = Consultation::published();

/* Os romanos. Escritos e não calculados: são quatro, a maquete mostra quatro, e
   um conversor de numeração romana para uma lista que nunca passa de uma dúzia é
   código a mais. Acima do que a tabela tem, a lista continua com o número
   árabe — é feio, e é melhor do que uma linha sem ordinal nenhum. */
$romanos = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

// Onde levar quem carrega no botão. Vazio não desenha botão nenhum: um botão
// sem destino é um botão que não faz nada.
$urlDaConversa = trim((string)setting('readings.talk_url'));
?>
<section class="secção secção--consultas"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">
    <div class="consultas__par">

      <?php /* A mesa, à esquerda. É o que faz esta secção ser uma mesa e não uma
               lista de preços: quem chega tira três cartas antes de decidir se
               marca. Ver views/partials/tarot.php. */ ?>
      <div class="consultas__mesa" data-entra="sobe">
        <?php partial('partials/tarot'); ?>
      </div>

      <div class="consultas__dizer">
        <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
          <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
        <?php endif; ?>

        <?php if (trim((string)$section['heading']) !== ''): ?>
          <<?= $headingTag ?> class="consultas__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
        <?php endif; ?>

        <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
          <div class="consultas__entrada" data-entra="sobe" data-atraso="160"><?= (string)$section['body'] ?></div>
        <?php endif; ?>

        <?php if ($consultas !== []): ?>
        <div class="consultas__lista">
          <?php foreach ($consultas as $i => $consulta): ?>
            <?php
            $modo     = (string)($consulta['mode'] ?? 'ambos');
            $duração  = (int)($consulta['duration_min'] ?? 0);
            $preço    = $consulta['price_cents'] ?? null;
            $marcável = (int)($consulta['is_bookable'] ?? 0) === 1;
            ?>
            <?php /* A luz que segue o rato vem do js/site.js, que escreve `--rato-x`
                     e `--rato-y`. Sem JavaScript fica no sítio de origem, fora da
                     linha, e não se vê — que é o que deve acontecer a uma luz. */ ?>
            <article class="consulta" data-entra="sobe" data-atraso="<?= (int)$i * 120 ?>">
              <span class="consulta__ordinal" aria-hidden="true"><?= e($romanos[$i + 1] ?? (string)($i + 1)) ?></span>

              <div class="consulta__corpo">
                <p class="consulta__meta">
                  <?php /* A duração só aparece quando há duração: os trabalhos
                           espirituais não duram meia hora, duram o que durarem. */ ?>
                  <?php if ($duração > 0): ?>
                    <span><?= e(duration_text($duração)) ?></span>
                    <span aria-hidden="true">&middot;</span>
                  <?php endif; ?>
                  <span><?= e(__('consultas.mode.' . $modo)) ?></span>
                </p>

                <<?= $itemTag ?> class="consulta__nome"><?= e((string)$consulta['name']) ?></<?= $itemTag ?>>

                <?php if (trim((string)($consulta['summary'] ?? '')) !== ''): ?>
                  <p class="consulta__dito"><?= e((string)$consulta['summary']) ?></p>
                <?php endif; ?>
              </div>

              <div class="consulta__fim">
                <span class="consulta__preço">
                  <?= $preço !== null ? e(money((int)$preço)) : e(__('consultas.on_request')) ?>
                </span>
                <?php if ($urlDaConversa !== ''): ?>
                  <a class="consulta__botão" href="<?= e($urlDaConversa) ?>">
                    <?= e(__($marcável ? 'consultas.book' : 'consultas.talk')) ?>
                  </a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="consultas__pé" data-entra="sobe">
          <p class="consultas__nota"><?= e(__('consultas.disclaimer')) ?></p>
          <?php if ($urlDaConversa !== ''): ?>
            <a class="consultas__agenda" href="<?= e($urlDaConversa) ?>">
              <?= e(__('consultas.agenda')) ?> <span aria-hidden="true">&rarr;</span>
            </a>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>
