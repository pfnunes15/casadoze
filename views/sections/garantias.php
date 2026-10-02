<?php
/**
 * A fila do que a Casa promete: envios, pagamento, devoluções, embalagem.
 *
 * Fica por baixo da loja e por cima do rodapé, e é lida por quem está a decidir
 * se compra. A embalagem discreta é a que mais importa aqui, e é por isso que é
 * uma garantia e não uma linha perdida numa página de envios.
 *
 * Cada uma leva um símbolo a traço e o seu número em romano. Os símbolos estão
 * escritos aqui, pela ordem das garantias — o camião, o cadeado, a volta e a
 * caixa —, e são os da maquete. Acima de quatro garantias a quinta fica sem
 * símbolo: é feio, e é melhor do que repetir o camião.
 *
 * @var array $section
 * @var string $itemTag
 */
$itens = $section['items'] ?? [];

$símbolos = [
    'M4 14h20v14H4z M24 18h7l5 5v5H24 M10 31a3 3 0 1 0 0.01 0 M30 31a3 3 0 1 0 0.01 0',
    'M11 18V13a9 9 0 0 1 18 0v5 M8 18h24v16H8z M20 24v5',
    'M8 16a12 12 0 1 1 3 10 M8 8v8h8',
    'M6 12l14-6 14 6v16l-14 6-14-6z M6 12l14 6 14-6 M20 18v16',
];

$romanos = ['I', 'II', 'III', 'IV', 'V', 'VI'];
?>
<?php if ($itens !== []): ?>
<section class="secção secção--garantias"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>
         aria-label="<?= e((string)($section['heading'] ?: __('footer.help'))) ?>">
  <div class="miolo">
    <div class="garantias" style="--quantas: <?= max(1, count($itens)) ?>">
      <?php foreach ($itens as $índice => $item): ?>
        <div class="garantia" data-entra="sobe" data-atraso="<?= (int)$índice * 100 ?>">
          <?php if (isset($símbolos[$índice])): ?>
            <span class="garantia__selo">
              <svg viewBox="0 0 40 40" aria-hidden="true">
                <path d="<?= e($símbolos[$índice]) ?>" fill="none" stroke="currentColor"
                      stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </span>
          <?php endif; ?>

          <?php /* O numeral é ornamento: a ordem já se vê na fila, e um leitor de
                   ecrã que anuncie «três» antes de «Devoluções» só confunde. */ ?>
          <span class="garantia__ordinal" aria-hidden="true"><?= e($romanos[$índice] ?? (string)($índice + 1)) ?></span>

          <<?= $itemTag ?> class="garantia__nome"><?= e((string)$item['title']) ?></<?= $itemTag ?>>

          <?php if (trim((string)($item['body'] ?? '')) !== ''): ?>
            <span class="garantia__dito"><?= nl2br(e((string)$item['body'])) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
