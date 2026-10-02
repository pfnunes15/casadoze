<?php
/**
 * A abertura: a fotografia com que a Casa se apresenta.
 *
 * Passa por baixo do cabeçalho, que nesta página é transparente. O retrato é
 * esbatido nas bordas em redondo — a máscara está na folha de estilo —, e por
 * cima dele sobem brasas desenhadas num canvas.
 *
 * **Não tem texto por cima da fotografia**, e é de propósito: o nome da Casa
 * está desenhado dentro da própria imagem, e escrevê-lo outra vez ao lado era
 * dizer o mesmo duas vezes. O título continua no documento — uma página sem `h1`
 * é uma página que um leitor de ecrã não sabe nomear —, mas fora do ecrã.
 *
 * Os botões ficam numa coluna encostada à margem esquerda, a meia altura, e é
 * essa a razão de o retrato poder ser do tamanho do ecrã: não há uma coluna de
 * texto a disputar-lhe a largura.
 *
 * @var array  $section
 * @var string $headingTag
 * @var bool   $eager
 */
$opções = \Admedia\Cms\Models\PageSection::options($section);

$brasas = max(0, min(200, (int)($opções['embers'] ?? 70)));

// Dois botões, e cada um só existe com texto **e** endereço. Um botão com texto
// e sem destino é um botão que não faz nada, e quem carrega nele não percebe
// porquê.
$botões = [];
foreach ([['cta_label', 'cta_url', true], ['cta2_label', 'cta2_url', false]] as [$chaveTexto, $chaveUrl, $doBloco]) {
    $texto = trim((string)($doBloco ? ($section[$chaveTexto] ?? '') : ($opções[$chaveTexto] ?? '')));
    $url   = trim((string)($doBloco ? ($section[$chaveUrl]   ?? '') : ($opções[$chaveUrl]   ?? '')));
    if ($texto !== '' && $url !== '') {
        $botões[] = ['texto' => $texto, 'url' => $url];
    }
}

$imagem = trim((string)$section['image']);
?>
<section class="abertura"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>

  <?php if ($imagem !== ''): ?>
  <div class="abertura__retrato">
    <?php /* A fotografia é o fundo deste `div` e não um `<img>` porque leva uma
             máscara radial, e uma máscara num `<img>` deixava o texto
             alternativo sem onde viver. Daí este `role="img"` com o rótulo:
             quem ouve a página recebe a descrição, e quem a vê recebe a
             fotografia mascarada.

             O endereço vem do bloco e não da folha de estilo: trocar a
             fotografia no backoffice tem de chegar ao ecrã, e uma folha de
             estilo com o nome do ficheiro lá dentro só se troca com um
             programador. */ ?>
    <div class="abertura__foto" role="img"
         aria-label="<?= e($section['image_alt'] ?: ($section['heading'] ?? '')) ?>"
         style="background-image: url('<?= e(media_url($imagem)) ?>')"></div>
  </div>
  <?php endif; ?>

  <?php if ($brasas > 0): ?>
    <?php /* As brasas sobem por todo o ecrã e não só por cima do retrato — é o
             que a maquete mostra, e é o que faz o fundo parecer fundo e não um
             rectângulo preto à volta de uma fotografia.

             `aria-hidden` porque são decoração: um leitor de ecrã que anuncie
             «canvas» no meio da abertura só atrapalha. */ ?>
    <canvas class="abertura__brasas" data-brasas="<?= $brasas ?>" aria-hidden="true"></canvas>
  <?php endif; ?>

  <div class="abertura__véu" aria-hidden="true"></div>

  <?php if (trim((string)$section['heading']) !== ''): ?>
    <<?= $headingTag ?> class="fora-do-ecrã"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
  <?php endif; ?>

  <?php if ($botões !== []): ?>
  <div class="abertura__botões" data-entra="sobe" data-atraso="200">
    <?php foreach ($botões as $i => $botão): ?>
      <a class="botão<?= $i === 0 ? ' botão--cheio' : '' ?>" href="<?= e($botão['url']) ?>">
        <span><?= e($botão['texto']) ?></span>
        <?php /* A seta é decoração: já está escrito para onde o botão vai. Um
                 leitor de ecrã que leia «seta para a direita» a seguir ao
                 texto só acrescenta ruído. */ ?>
        <span aria-hidden="true">&rarr;</span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php /* O sinal de que a página continua. Só aparece onde os botões saíram da
           coluna do meio — num telefone a abertura acaba à vista e não é preciso
           dizer a ninguém que role. */ ?>
  <div class="abertura__desce" aria-hidden="true">
    <span><?= e(__('site.scroll')) ?></span>
    <span class="abertura__risco"><i></i></span>
  </div>
</section>
