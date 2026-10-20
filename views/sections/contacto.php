<?php
/**
 * Falar com a Casa: o formulário, e o que a Casa tem de dizer ao lado.
 *
 * O que for escrito fica em Pedidos, no backoffice. Quem o recebe é o
 * `Admedia\Cms\Controllers\ContactController` do pacote: verifica que não é um
 * robô, verifica os campos, **escreve** e só depois tenta mandar o aviso por
 * e-mail. Um pedido guardado e não enviado é um incómodo; um pedido enviado
 * para uma caixa que ninguém vê, e guardado em lado nenhum, é um cliente
 * perdido.
 *
 * **As frases de erro são desta vista e não do controlador.** As do pacote
 * estão escritas em inglês, para os sites que ele serve nessa língua, e este é
 * servido em cinco. O que o controlador guarda na sessão é *em que campo* está
 * o erro; o que se lê é escrito aqui, pelo catálogo das línguas.
 *
 * O formulário volta sempre à página onde está — não tem página própria, é um
 * bloco e um bloco senta-se em qualquer uma.
 *
 * @var array  $section
 * @var string $headingTag
 * @var \Admedia\Core\App $app
 * @var array $flashes
 */
$assuntos = array_values(array_filter(array_map(
    static fn(array $i): string => trim((string)($i['title'] ?? '')),
    $section['items'] ?? []
)));

/* As mensagens do formulário vêm em `enquiry.ok` e `enquiry.bad`, e não em
   `success`/`error`: uma mensagem de página é uma banda desenhada por cima de
   tudo, e aqui empurrava o formulário para fora do sítio. O prefixo mantém-nas
   fora do parcial das mensagens de página — ver views/layouts/public.php — e é
   este bloco que as desenha, ao lado do formulário a que pertencem. */
$erros  = errors();
$feito  = ($flashes['enquiry.ok'] ?? []) !== [];
$falhou = ($flashes['enquiry.bad'] ?? []) !== [];

$morada  = trim((string)setting('contact.address', ''));
$email   = trim((string)setting('contact.email', ''));
$tel     = trim((string)setting('contact.phone', ''));
$horário = trim((string)setting('contact.hours', ''));
?>
<section class="secção secção--contacto"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo contacto">

    <div class="contacto__dizer">
      <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
        <p class="sobrescrita" data-entra="sobe"><?= e((string)$section['eyebrow']) ?></p>
      <?php endif; ?>
      <?php if (trim((string)$section['heading']) !== ''): ?>
        <<?= $headingTag ?> class="contacto__título" data-entra="sobe" data-atraso="80"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
      <?php endif; ?>
      <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
        <div class="contacto__prosa" data-entra="sobe" data-atraso="140"><?= (string)$section['body'] ?></div>
      <?php endif; ?>

      <dl class="contacto__dados">
        <?php if ($morada !== ''): ?>
          <div><dt><?= e(__('contacto.where')) ?></dt><dd><?= nl2br(e($morada)) ?></dd></div>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
          <div><dt><?= e(__('marcacao.email')) ?></dt><dd><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></dd></div>
        <?php endif; ?>
        <?php if ($tel !== ''): ?>
          <div><dt><?= e(__('marcacao.phone')) ?></dt><dd><?= e($tel) ?></dd></div>
        <?php endif; ?>
        <?php if ($horário !== ''): ?>
          <div><dt><?= e(__('contacto.hours')) ?></dt><dd><?= nl2br(e($horário)) ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>

    <div class="contacto__caixa" data-entra="sobe" data-atraso="200">
      <?php if ($feito): ?>

        <div class="contacto__feito" role="status">
          <span class="contacto__feitoTítulo"><?= e(__('contacto.sent')) ?></span>
          <p class="contacto__feitoDito"><?= e(__('contacto.sent.body')) ?></p>
        </div>

      <?php else: ?>

        <?php if ($falhou): ?>
          <p class="contacto__erro" role="alert"><?= e(__('contacto.slow_down')) ?></p>
        <?php elseif ($erros !== []): ?>
          <p class="contacto__erro" role="alert"><?= e(__('marcacao.missing')) ?></p>
        <?php endif; ?>

        <form class="contacto__forma" method="post" action="<?= e(locale_url('/contacto')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="source" value="<?= e((string)($section['heading'] ?? 'contacto')) ?>">

          <?php if ($assuntos !== []): ?>
            <fieldset class="contacto__assuntos">
              <legend class="campo__rótulo"><?= e(__('contacto.subject')) ?></legend>
              <?php foreach ($assuntos as $a): ?>
                <label class="hora hora--modo">
                  <input type="checkbox" name="services[]" value="<?= e($a) ?>">
                  <span class="hora__valor"><?= e($a) ?></span>
                </label>
              <?php endforeach; ?>
            </fieldset>
          <?php endif; ?>

          <label class="campo__rótulo" for="c-nome"><?= e(__('marcacao.name')) ?></label>
          <input class="campo<?= isset($erros['name']) ? ' campo--errado' : '' ?>" id="c-nome" type="text"
                 name="name" required maxlength="190" autocomplete="name" value="<?= e((string)old('name')) ?>">
          <?php if (isset($erros['name'])): ?>
            <span class="campo__erro"><?= e(__('marcacao.err.name')) ?></span>
          <?php endif; ?>

          <label class="campo__rótulo" for="c-email"><?= e(__('marcacao.email')) ?></label>
          <input class="campo<?= isset($erros['email']) ? ' campo--errado' : '' ?>" id="c-email" type="email"
                 name="email" required maxlength="190" autocomplete="email" value="<?= e((string)old('email')) ?>">
          <?php if (isset($erros['email'])): ?>
            <span class="campo__erro"><?= e(__('marcacao.err.email')) ?></span>
          <?php endif; ?>

          <label class="campo__rótulo" for="c-tel">
            <?= e(__('marcacao.phone')) ?>
            <span class="campo__opcional"><?= e(__('marcacao.phone_optional')) ?></span>
          </label>
          <input class="campo" id="c-tel" type="tel" name="phone" maxlength="60"
                 autocomplete="tel" value="<?= e((string)old('phone')) ?>">

          <label class="campo__rótulo" for="c-msg"><?= e(__('contacto.message')) ?></label>
          <textarea class="campo campo--prosa<?= isset($erros['message']) ? ' campo--errado' : '' ?>"
                    id="c-msg" name="message" rows="5" required><?= e((string)old('message')) ?></textarea>
          <?php if (isset($erros['message'])): ?>
            <span class="campo__erro"><?= e(__('contacto.err.message')) ?></span>
          <?php endif; ?>

          <?php /* O alçapão. Uma pessoa nunca o vê; um robô preenche-o por o
                   encontrar no documento. Ver ContactController::store. */ ?>
          <div class="fora-do-ecrã" aria-hidden="true">
            <label for="c-website">Website</label>
            <input id="c-website" type="text" name="website" tabindex="-1" autocomplete="off">
          </div>

          <label class="aceito">
            <input type="checkbox" name="consent" value="1" required>
            <span class="aceito__marca" aria-hidden="true"></span>
            <span><?= e(__('contacto.consent')) ?></span>
          </label>

          <button type="submit" class="agenda__botão"><?= e(__('contacto.send')) ?></button>
        </form>

      <?php endif; ?>
    </div>
  </div>
</section>
