<?php
/**
 * A mesa de tarot: tiragem simbólica de três cartas.
 *
 * Vive dentro do bloco das consultas, na coluna da esquerda. É o que faz a
 * secção ser uma mesa e não uma lista de preços: quem chega tira três cartas
 * antes de decidir se marca.
 *
 * **As vinte e duas cartas estão no documento desde o início**, viradas para
 * baixo e empilhadas. Baralhar, cortar e espalhar é mexer-lhes no `transform`;
 * nada vem do servidor depois de a página abrir. É isso que faz a mesa responder
 * no instante — e é também o que a mantém honesta: a carta que sai é a que o
 * acaso do browser deu, e não uma que o servidor escolheu.
 *
 * **Sem JavaScript não há tiragem**, e o que fica é a frase a dizer o que isto
 * é, mais o botão para marcar uma leitura a sério. Uma mesa de tarot que não
 * baralha não é uma mesa; mais vale dizê-lo do que desenhar cartas que não
 * viram.
 *
 * **É simbólica e gratuita, e está escrito.** O que se vende são as leituras com
 * o Zé, que estão ao lado.
 *
 * @var array $consulta  a consulta para onde leva o botão, ou null
 */

$nomes      = explode('|', __('tarot.names'));
$direitos   = explode('|', __('tarot.up'));
$invertidos = explode('|', __('tarot.rev'));
$lugares    = explode('|', __('tarot.pos'));

/* Os romanos dos Arcanos Maiores: do Louco, que é zero, ao Mundo, que é XXI. */
$romanos = ['0', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X',
            'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX', 'XXI'];

$quantas = min(count($nomes), count($direitos), count($invertidos));
?>
<?php /* As frases dos momentos vão em atributos e não dentro do JavaScript: são
         cinco línguas, e o catálogo não chega ao guião. */ ?>
<div class="mesa" data-mesa data-fase="parada"
     data-focar="<?= e(__('tarot.focus')) ?>"
     data-cortar="<?= e(__('tarot.cut')) ?>"
     data-escolher="<?= e(__('tarot.pick')) ?>"
     data-mostrar="<?= e(__('tarot.reveal')) ?>"
     data-invertida="<?= e(__('tarot.inv')) ?>">

  <div class="mesa__topo">
    <span class="mesa__título"><?= e(__('tarot.title')) ?></span>
    <button type="button" class="mesa__saltar" data-mesa-saltar hidden>
      <?= e(__('tarot.skip')) ?> <span aria-hidden="true">&#9197;</span>
    </button>
  </div>

  <?php /* O que a mesa diz em cada momento. `role="status"` para quem ouve a
           página acompanhar a tiragem sem ter de a ver. */ ?>
  <p class="mesa__dito" data-mesa-dito role="status"><?= e(__('tarot.note')) ?></p>

  <div class="mesa__lugares" aria-hidden="true">
    <?php foreach ($lugares as $i => $lugar): ?>
      <div class="lugar">
        <div class="lugar__vazio" data-lugar="<?= (int)$i ?>"></div>
        <div class="lugar__dizer">
          <span class="lugar__nome"><?= e($lugar) ?></span>
          <div class="lugar__carta" data-lugar-dizer="<?= (int)$i ?>">
            <span class="lugar__arcano"></span>
            <span class="lugar__sentido"></span>
            <span class="lugar__sentido lugar__significado"></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php /* O baralho. Cada carta tem duas faces: o verso, desenhado em SVG, e a
           frente, que só se escreve quando a carta é tirada — as vinte e duas
           frentes preenchidas de início eram vinte e duas vezes o mesmo texto no
           documento, e nenhuma delas visível. */ ?>
  <div class="baralho" data-baralho>
    <?php for ($i = 0; $i < $quantas; $i++): ?>
      <button type="button" class="carta-tarot" data-carta-tarot="<?= $i ?>" tabindex="-1" aria-hidden="true"
              data-nome="<?= e($nomes[$i]) ?>"
              data-romano="<?= e($romanos[$i] ?? (string)$i) ?>"
              data-direito="<?= e($direitos[$i]) ?>"
              data-invertido="<?= e($invertidos[$i]) ?>">
        <span class="carta-tarot__virar">
          <span class="carta-tarot__verso">
            <svg viewBox="0 0 70 116" preserveAspectRatio="none" aria-hidden="true">
              <rect x="4" y="4" width="62" height="108" fill="none" stroke="#d9b36c"
                    stroke-opacity=".45" stroke-width=".6"></rect>
              <path d="M35 30 L44 58 L35 86 L26 58Z" fill="none" stroke="#d9b36c"
                    stroke-opacity=".55" stroke-width=".7"></path>
              <circle cx="35" cy="58" r="13" fill="none" stroke="#d9b36c"
                      stroke-opacity=".35" stroke-width=".6"></circle>
              <circle cx="35" cy="58" r="2.2" fill="#d9b36c" fill-opacity=".8"></circle>
            </svg>
          </span>
          <span class="carta-tarot__frente">
            <span class="carta-tarot__romano"><?= e($romanos[$i] ?? (string)$i) ?></span>
            <span class="carta-tarot__nome"><?= e($nomes[$i]) ?></span>
          </span>
        </span>
      </button>
    <?php endfor; ?>
  </div>

  <div class="mesa__fim">
    <button type="button" class="botão botão--cheio mesa__começar" data-mesa-começar>
      <span><?= e(__('tarot.start')) ?></span>
      <span aria-hidden="true">&rarr;</span>
    </button>

    <button type="button" class="botão mesa__outra" data-mesa-outra hidden>
      <span><?= e(__('tarot.again')) ?></span>
      <span aria-hidden="true">&rarr;</span>
    </button>
  </div>
</div>
