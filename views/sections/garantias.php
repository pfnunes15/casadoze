<?php
/**
 * A fila do que a Casa promete: envios, pagamento, devoluções, embalagem.
 *
 * Fica por baixo da loja e por cima do rodapé, e é lida por quem está a decidir
 * se compra. A embalagem discreta é a que mais importa aqui, e é por isso que é
 * uma garantia e não uma linha perdida numa página de envios.
 *
 * @var array $section
 * @var string $itemTag
 */
$itens = $section['items'] ?? [];
?>
<?php if ($itens !== []): ?>
<section class="secção"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">
    <div class="grelha">
      <?php foreach ($itens as $item): ?>
        <div class="cartão">
          <<?= $itemTag ?> class="cartão__nome" style="font-size: 22px"><?= e((string)$item['title']) ?></<?= $itemTag ?>>
          <?php if (trim((string)($item['body'] ?? '')) !== ''): ?>
            <p class="cartão__dito"><?= nl2br(e((string)$item['body'])) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
