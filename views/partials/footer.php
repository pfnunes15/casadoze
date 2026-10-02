<?php
/**
 * O rodapé, como está na maquete.
 *
 * Cinco andares: o boletim da lua, quatro colunas de ligações, o letreiro da
 * Casa em grande, a ressalva, e a barra do fundo com os direitos e as línguas.
 *
 * **Tudo o que aparece vem das Definições**, e não está escrito neste ficheiro.
 * Uma coluna sem nada escrito não é desenhada — um rodapé a que falta o telefone
 * não deve ter um título «Contacto» com um vazio por baixo.
 *
 * **O boletim não envia nada.** A maquete também não envia: carregar em
 * «Subscrever» agradece e mais nada. Para o fazer a sério era preciso um
 * endereço para onde enviar, uma lista onde guardar e o consentimento a ficar
 * registado — e nada disso existe neste site, que é uma página só. Escrever um
 * formulário que parece guardar e não guarda era pior do que isto: quem o
 * preenchesse ficava à espera de uma carta que nunca vinha. Ver js/site.js, que
 * é quem responde.
 *
 * @var \Admedia\Core\App $app
 */

$nome     = setting('site.name', (string)$app->config('app.name', 'Casa de Zé'));
$morada   = trim(setting('contact.address'));
$email    = trim(setting('contact.email'));
$telefone = trim(setting('contact.phone'));
$horário  = trim(setting('contact.hours'));

$menu = \Admedia\Cms\Models\MenuItem::publicTree('footer');

/* As categorias da loja, para a primeira coluna. Só onde há loja. */
$categorias = $app->hasShop() ? \Admedia\Shop\Models\Category::tree(true) : [];

/* A morada em linhas, como foi escrita. O `\R` apanha as três maneiras de mudar
   de linha, porque uma morada colada de outro sítio traz a do sistema de onde
   veio; as linhas vazias a meio saem, senão um Enter a mais abria um buraco. */
$linhasDaMorada = $morada === '' ? [] : array_values(array_filter(
    array_map('trim', preg_split('/\R/', $morada) ?: []),
    static fn(string $l): bool => $l !== ''
));

/* As ligações legais que a Casa tiver preenchido. Uma a uma, porque um endereço
   por escrever não deve desenhar uma ligação para lado nenhum. */
$ajuda = [];
foreach ([
    'legal.terms_url'   => 'footer.terms',
    'legal.privacy_url' => 'footer.privacy',
] as $chave => $frase) {
    $url = trim(setting($chave));
    if ($url !== '') {
        $ajuda[] = ['url' => $url, 'texto' => __($frase)];
    }
}

$idiomas = \Admedia\Core\Locales::many() ? \Admedia\Core\Locales::alternates() : [];
?>
<footer class="rodapé" id="contacto">
  <div class="miolo rodapé__miolo">

    <?php /* O boletim. Duas colunas que se partem em duas linhas: o dizer de um
             lado, o campo do outro, assentes na mesma base. */ ?>
    <div class="boletim">
      <div class="boletim__dizer">
        <h2 class="boletim__título" data-entra="sobe"><?= e(__('footer.nl_title')) ?></h2>
        <p class="boletim__texto" data-entra="sobe" data-atraso="100"><?= e(__('footer.nl_text')) ?></p>
        <span class="boletim__prenda" data-entra="sobe" data-atraso="140">
          <span aria-hidden="true">&#10022;</span> <?= e(__('footer.nl_gift')) ?>
        </span>
      </div>

      <form class="boletim__forma" data-boletim data-obrigado="<?= e(__('footer.nl_ok')) ?>"
            data-entra="sobe" data-atraso="180">
        <div class="boletim__linha">
          <label class="fora-do-ecrã" for="boletim-email"><?= e(__('footer.nl_place')) ?></label>
          <input class="boletim__campo" id="boletim-email" type="email" name="email" required
                 placeholder="<?= e(__('footer.nl_place')) ?>" autocomplete="email">
          <button class="boletim__botão" type="submit">
            <?= e(__('footer.nl_send')) ?> <span aria-hidden="true">&rarr;</span>
          </button>
        </div>
        <?php /* O sítio da resposta existe desde o início e ocupa a sua altura,
                 para a linha de cima não saltar quando ela aparecer. */ ?>
        <span class="boletim__resposta" data-boletim-resposta role="status"></span>
      </form>
    </div>

    <div class="rodapé__colunas">
      <?php if ($categorias !== []): ?>
      <div data-entra="sobe">
        <h2 class="rodapé__título"><?= e(__('footer.shop')) ?></h2>
        <?php foreach ($categorias as $c): ?>
          <a href="#loja"><?= e((string)$c['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($menu !== []): ?>
      <div data-entra="sobe" data-atraso="80">
        <h2 class="rodapé__título"><?= e(__('footer.house')) ?></h2>
        <?php foreach ($menu as $item): ?>
          <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($ajuda !== []): ?>
      <div data-entra="sobe" data-atraso="160">
        <h2 class="rodapé__título"><?= e(__('footer.help')) ?></h2>
        <?php foreach ($ajuda as $l): ?>
          <a href="<?= e($l['url']) ?>"><?= e($l['texto']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php
      $redes = array_filter([
          'social.instagram', 'social.facebook', 'social.linkedin', 'social.whatsapp',
          'social.tripadvisor', 'social.google', 'social.youtube',
      ], static fn(string $k): bool => setting($k) !== '');
      ?>
      <?php if ($email !== '' || $telefone !== '' || $linhasDaMorada !== [] || $horário !== '' || $redes !== []): ?>
      <div data-entra="sobe" data-atraso="240">
        <h2 class="rodapé__título"><?= e(__('footer.contact')) ?></h2>

        <?php if ($email !== ''): ?>
          <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        <?php endif; ?>

        <?php if ($telefone !== ''): ?>
          <a href="tel:<?= e(\Admedia\Cms\Phone::dial($telefone)) ?>"><?= e($telefone) ?></a>
        <?php endif; ?>

        <?php foreach ($linhasDaMorada as $linha): ?>
          <span class="rodapé__tenue"><?= e($linha) ?></span>
        <?php endforeach; ?>

        <?php if ($horário !== ''): ?>
          <span class="rodapé__tenue"><?= e($horário) ?></span>
        <?php endif; ?>

        <?php if ($redes !== []): ?>
          <div class="rodapé__redes"><?php partial('partials/social'); ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php /* O letreiro, em grande, a fechar a página. `alt` vazio e `aria-hidden`
           porque o nome da Casa já foi dito no cabeçalho e nos direitos aqui
           em baixo: lê-lo uma terceira vez só atrapalha quem ouve. */ ?>
  <div class="rodapé__letreiro">
    <img src="<?= asset('img/faixa.png') ?>" alt="" aria-hidden="true"
         loading="lazy" decoding="async" data-entra="sobe">
  </div>

  <?php /* A ressalva. Dita aqui **e** onde se marca: aqui porque tem de estar em
           todas as páginas, e lá porque é no momento de marcar que importa. */ ?>
  <p class="rodapé__ressalva"><?= e(__('consultas.disclaimer')) ?></p>

  <div class="rodapé__barra">
    <div class="miolo rodapé__fim">
      <span>&copy; <?= date('Y') ?> <?= e($nome) ?> &middot; <?= e(__('footer.rights')) ?></span>

      <?php if ($idiomas !== []): ?>
      <div class="rodapé__línguas">
        <?php foreach ($idiomas as $idioma): ?>
          <a href="<?= e($idioma['url']) ?>"<?= $idioma['current'] ? ' aria-current="true"' : '' ?>>
            <?= e($idioma['short']) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php /* O Livro de Reclamações Eletrónico é obrigatório num site que vende
               em Portugal, e é por isso que está aqui e não numa página de
               termos: tem de estar alcançável de qualquer página. */ ?>
      <a href="https://www.livroreclamacoes.pt/inicio" rel="noopener" target="_blank">
        <?= e(__('footer.complaints')) ?>
      </a>
    </div>
  </div>
</footer>
