<?php
/**
 * Perguntas frequentes: a lista que se abre.
 *
 * `<details>` e `<summary>`, e não um botão com JavaScript. O browser já sabe
 * abrir e fechar, já o diz a quem ouve a página, e já deixa procurar dentro do
 * que está fechado com o Ctrl+F — três coisas que um botão com `aria-expanded`
 * tem de reimplementar, e reimplementar mal.
 *
 * **A primeira abre.** Uma lista inteira fechada parece uma lista vazia, e a
 * primeira aberta mostra o que lá está sem obrigar a carregar.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */
$itens = $section['items'] ?? [];

if ($itens === []) {
    return;
}
?>
<section class="secção secção--perguntas"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo perguntas">

    <div class="perguntas__dizer">
      <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
        <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
      <?php endif; ?>
      <?php if (trim((string)$section['heading']) !== ''): ?>
        <<?= $headingTag ?> class="perguntas__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
      <?php endif; ?>
    </div>

    <div class="perguntas__lista" data-entra="sobe" data-atraso="140">
      <?php foreach ($itens as $i => $item): ?>
        <details class="pergunta"<?= $i === 0 ? ' open' : '' ?>>
          <summary class="pergunta__título">
            <<?= $itemTag ?> class="pergunta__nome"><?= e((string)($item['title'] ?? '')) ?></<?= $itemTag ?>>
            <span class="pergunta__sinal" aria-hidden="true"></span>
          </summary>
          <?php if (trim((string)($item['body'] ?? '')) !== ''): ?>
            <p class="pergunta__resposta"><?= nl2br(e((string)$item['body'])) ?></p>
          <?php endif; ?>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
