<?php
/**
 * O rodapé.
 *
 * Quatro colunas que se arrumam sozinhas: onde é a Casa, como se fala com ela, o
 * menu do rodapé e as redes. Uma coluna sem nada escrito não é desenhada — o
 * rodapé de um site a que falta o telefone não deve ter um título «Falar com a
 * Casa» com um vazio por baixo.
 *
 * Tudo o que aqui aparece vem das Definições, e não está escrito neste ficheiro.
 * Era: a morada e o telefone estavam aqui em texto, e isso queria dizer que um
 * segundo projecto não podia reaproveitar o rodapé sem o editar.
 *
 * @var \Admedia\Core\App $app
 */

$nome     = setting('site.name', (string)$app->config('app.name', 'Casa de Zé'));
$morada   = trim(setting('contact.address'));
$email    = trim(setting('contact.email'));
$telefone = trim(setting('contact.phone'));
$horário  = trim(setting('contact.hours'));

$menu = \Admedia\Cms\Models\MenuItem::publicTree('footer');

/* A morada em linhas, como foi escrita. O `\R` apanha as três maneiras de mudar
   de linha, porque uma morada colada de outro sítio traz a do sistema de onde
   veio; as linhas vazias a meio saem, senão um Enter a mais abria um buraco no
   meio da morada. */
$linhasDaMorada = $morada === '' ? [] : array_values(array_filter(
    array_map('trim', preg_split('/\R/', $morada) ?: []),
    static fn(string $l): bool => $l !== ''
));
?>
<footer class="rodapé" id="contacto">
  <div class="miolo">

    <div class="rodapé__colunas">

      <?php if ($linhasDaMorada !== []): ?>
      <div>
        <h2><?= e(__('footer.where')) ?></h2>
        <p>
          <?php foreach ($linhasDaMorada as $i => $linha): ?>
            <?= $i > 0 ? '<br>' : '' ?><?= e($linha) ?>
          <?php endforeach; ?>
        </p>
      </div>
      <?php endif; ?>

      <?php if ($email !== '' || $telefone !== ''): ?>
      <div>
        <h2><?= e(__('footer.contact')) ?></h2>
        <ul>
          <?php if ($email !== ''): ?>
            <li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
          <?php endif; ?>
          <?php if ($telefone !== ''): ?>
            <?php /* O `tel:` leva o número só com algarismos, que é o que um
                     telefone sabe marcar; o que se lê fica como a casa o
                     escreveu. Ver Admedia\Cms\Phone::dial. */ ?>
            <li><a href="tel:<?= e(\Admedia\Cms\Phone::dial($telefone)) ?>"><?= e($telefone) ?></a></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($horário !== ''): ?>
      <div>
        <h2><?= e(__('footer.hours')) ?></h2>
        <p><?= nl2br(e($horário)) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($menu !== []): ?>
      <div>
        <h2><?= e($nome) ?></h2>
        <ul>
          <?php foreach ($menu as $item): ?>
            <li><a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php
      /* As redes sociais vêm do pacote: a lista e os ícones são iguais em todos
         os sites, e o que muda são os endereços, que estão nas Definições.

         O parcial já não desenha nada quando não há rede nenhuma, mas o **título**
         é deste ficheiro — e um «Segue a Casa» com um vazio por baixo é pior do
         que não ter coluna. Daí perguntar aqui, pelas mesmas chaves que o
         parcial lê. */
      $redes = array_filter([
          'social.instagram', 'social.facebook', 'social.linkedin', 'social.whatsapp',
          'social.tripadvisor', 'social.google', 'social.youtube',
      ], static fn(string $k): bool => setting($k) !== '');
      ?>
      <?php if ($redes !== []): ?>
      <div>
        <h2><?= e(__('footer.follow')) ?></h2>
        <?php partial('partials/social'); ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="rodapé__fim">
      <span>&copy; <?= date('Y') ?> <?= e($nome) ?> — <?= e(__('footer.rights')) ?></span>
      <?php /* O Livro de Reclamações Eletrónico é obrigatório num site que vende
               em Portugal, e é por isso que está aqui e não numa página de
               termos: tem de estar alcançável de qualquer página. */ ?>
      <a href="https://www.livroreclamacoes.pt/inicio" rel="noopener" target="_blank">
        <?= e(__('footer.complaints')) ?>
      </a>
      <?php if (trim(setting('legal.privacy_url')) !== ''): ?>
        <a href="<?= e(setting('legal.privacy_url')) ?>"><?= e(__('footer.privacy')) ?></a>
      <?php endif; ?>
      <?php if (trim(setting('legal.terms_url')) !== ''): ?>
        <a href="<?= e(setting('legal.terms_url')) ?>"><?= e(__('footer.terms')) ?></a>
      <?php endif; ?>
    </div>

    <?php /* A ressalva, no fim de tudo. Dita aqui **e** onde se marca: aqui
             porque tem de estar em todas as páginas, e lá porque é no momento de
             marcar que importa ler. */ ?>
    <p class="ressalva" style="margin-top: 24px"><?= e(__('consultas.disclaimer')) ?></p>
  </div>
</footer>
