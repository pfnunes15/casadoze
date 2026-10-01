<?php
/**
 * As consultas da Casa, com a agenda em cada cartão.
 *
 * **Mostra consultas; não as guarda.** Estão em Consultas, com a duração, o preço
 * e o horário, e é de lá que esta lista vem — ver App\Models\Consultation e a nota
 * no topo de config/blocks.php.
 *
 * Três coisas que este parcial decide, e porquê:
 *
 * **As horas não são escritas no HTML — vêm por JSON.** A página pode estar numa
 * cache, e as horas mudam a cada marcação: uma página guardada que mostrasse horas
 * já tomadas punha as pessoas a carregar em botões que recusam. O `js/site.js` vai
 * buscá-las a /consultas/vagas quando a secção entra no ecrã.
 *
 * **Sem JavaScript continua a dar para marcar.** O `<noscript>` desenha a mesma
 * agenda como um `<select>` com as horas todas, feito com os dados que o servidor
 * já tem à mão. É mais feio e não é mais lento; o que não se pode é não existir.
 *
 * **Uma consulta sem agenda mostra um botão em vez de horas.** São os trabalhos
 * espirituais: preparam-se depois de uma conversa, e não há horas para escolher.
 * Ver a coluna `is_bookable`.
 *
 * @var array  $section
 * @var string $headingTag
 * @var string $itemTag
 */
$opções = \Admedia\Cms\Models\PageSection::options($section);
$comAgenda = ($opções['agenda'] ?? 'inline') !== 'linked';

$consultas = \App\Models\Consultation::published();

/* De onde se voltou, para um erro de preenchimento aparecer ao pé do formulário
   que o causou e não no topo de uma página qualquer. */
$voltarPara = \Admedia\Core\Locales::path();

// As mensagens desta secção. Não são mensagens de página — pertencem ao
// formulário, e no topo do ecrã ficariam longe do campo que falhou.
$bem = $flashes['marcacao.ok'][0] ?? null;
$mal = $flashes['marcacao.bad'][0] ?? null;

// Onde levar o «Falar com o Zé», das consultas que não têm agenda.
$urlDaConversa = trim(setting('readings.talk_url'));
?>
<section class="secção"<?= $section['anchor'] !== '' ? ' id="' . e($section['anchor']) . '"' : '' ?>>
  <div class="miolo">

    <?php if (trim((string)($section['eyebrow'] ?? '')) !== ''): ?>
      <p class="sobrescrita"><?= e((string)$section['eyebrow']) ?></p>
    <?php endif; ?>

    <?php if (trim((string)$section['heading']) !== ''): ?>
      <<?= $headingTag ?> style="margin-block: 18px 20px"><?= heading_html($section['heading']) ?></<?= $headingTag ?>>
    <?php endif; ?>

    <?php if (trim((string)($section['body'] ?? '')) !== ''): ?>
      <div style="max-width: 680px; color: var(--letra-fraca)"><?= (string)$section['body'] ?></div>
    <?php endif; ?>

    <?php if ($bem !== null): ?><p class="aviso aviso--bom" style="margin-top: 24px"><?= e($bem) ?></p><?php endif; ?>
    <?php if ($mal !== null): ?><p class="aviso aviso--mau" style="margin-top: 24px"><?= e($mal) ?></p><?php endif; ?>

    <?php if ($consultas === []): ?>
      <p class="agenda__vazia" style="margin-top: 28px"><?= e(__('consultas.none')) ?></p>
    <?php else: ?>

    <div class="grelha grelha--larga grelha--solta" style="margin-top: 36px">
      <?php foreach ($consultas as $n => $consulta): ?>
        <?php
        $marcável = !empty($consulta['is_bookable']);
        $modos    = \App\Models\Consultation::modos($consulta);
        /* As horas livres, para o `<noscript>` e para saber se há alguma.

           A mesma janela que o JSON usa — ver BookingController::DIAS_NA_PAGINA —
           e não o horizonte todo: as duas listas têm de dizer o mesmo, ou quem
           tem JavaScript vê quatro semanas e quem não tem vê três meses. */
        $vagas = $marcável
            ? \App\Models\ConsultationSlot::bookable(
                  (int)$consulta['id'],
                  \App\Controllers\BookingController::DIAS_NA_PAGINA
              )
            : [];
        ?>
        <article class="cartão">
          <span class="cartão__ordinal"><?= e(['I', 'II', 'III', 'IV', 'V', 'VI'][$n] ?? (string)($n + 1)) ?></span>

          <<?= $itemTag ?> class="cartão__nome"><?= e((string)$consulta['name']) ?></<?= $itemTag ?>>

          <?php if (trim((string)($consulta['summary'] ?? '')) !== ''): ?>
            <p class="cartão__dito"><?= e((string)$consulta['summary']) ?></p>
          <?php endif; ?>

          <p class="cartão__linha">
            <?= e(\App\Models\Consultation::linha($consulta)) ?>
            &middot; <?= e(__('consultas.mode.' . (string)$consulta['mode'])) ?>
          </p>

          <?php if (!$marcável): ?>
            <?php /* Sem agenda: um botão para a conversa. Sem endereço escrito nas
                     Definições não se desenha botão nenhum — um botão que não leva
                     a sítio nenhum é pior do que a sua falta. */ ?>
            <?php if ($urlDaConversa !== ''): ?>
              <div class="cartão__pé">
                <a class="botão botão--cheio" href="<?= e($urlDaConversa) ?>"><?= e(__('consultas.talk')) ?></a>
              </div>
            <?php endif; ?>

          <?php elseif (!$comAgenda): ?>
            <div class="cartão__pé">
              <a class="botão botão--cheio" href="#marcar-<?= (int)$consulta['id'] ?>"><?= e(__('consultas.book')) ?></a>
            </div>

          <?php elseif ($vagas === []): ?>
            <p class="agenda__vazia"><?= e(__('consultas.none')) ?></p>

          <?php else: ?>
            <form method="post" action="<?= e(locale_url('/marcar')) ?>" id="marcar-<?= (int)$consulta['id'] ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="source" value="<?= e($voltarPara) ?>">

              <?php /* O alçapão: um campo que uma pessoa nunca vê e que um robô
                       preenche por o encontrar no documento. Fora do ecrã e não
                       `display:none`, que há robôs que sabem saltar. */ ?>
              <div class="alçapão" aria-hidden="true">
                <label for="website-<?= (int)$consulta['id'] ?>">Website</label>
                <input type="text" id="website-<?= (int)$consulta['id'] ?>" name="website" tabindex="-1" autocomplete="off">
              </div>

              <?php /* A agenda, montada pelo JavaScript. Fica `hidden` até as horas
                       chegarem: uma caixa vazia com dois títulos dentro, à espera,
                       parece uma coisa partida. */ ?>
              <div class="agenda" data-agenda data-consulta="<?= (int)$consulta['id'] ?>"
                   data-fonte="<?= e(locale_url('/consultas/vagas')) ?>" hidden>
                <div class="agenda__passo">
                  <span class="agenda__rótulo"><?= e(__('consultas.pick_day')) ?></span>
                  <div class="agenda__fila" data-dias></div>
                </div>
                <div class="agenda__passo">
                  <span class="agenda__rótulo"><?= e(__('consultas.pick_time')) ?></span>
                  <div class="agenda__fila" data-horas></div>
                </div>
                <input type="hidden" name="slot_id" data-vaga value="">
              </div>

              <p class="agenda__vazia" data-vazia hidden><?= e(__('consultas.none')) ?></p>

              <?php /* A mesma escolha sem JavaScript. Um `<select>` com as horas
                       das próximas quatro semanas: uma lista comprida não é
                       desenho, mas é uma marcação que se faz. O `name` é o mesmo,
                       por isso o controlador não sabe nem precisa de saber por
                       qual das duas vias é que a vaga chegou. */ ?>
              <noscript>
                <div class="campo">
                  <label for="vaga-<?= (int)$consulta['id'] ?>"><?= e(__('consultas.pick_time')) ?></label>
                  <select id="vaga-<?= (int)$consulta['id'] ?>" name="slot_id" required>
                    <?php foreach ($vagas as $vaga): ?>
                      <option value="<?= (int)$vaga['id'] ?>">
                        <?= e(format_date((string)$vaga['starts_at'], 'l, j \d\e F')) ?>
                        &middot; <?= e(date('H:i', strtotime((string)$vaga['starts_at']))) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </noscript>

              <div style="margin-top: 22px">
                <div class="campo <?= errors('name') ? 'campo--dito' : '' ?>">
                  <label for="nome-<?= (int)$consulta['id'] ?>"><?= e(__('marcacao.name')) ?></label>
                  <input type="text" id="nome-<?= (int)$consulta['id'] ?>" name="name"
                         value="<?= e((string)old('name')) ?>" autocomplete="name" required>
                  <?php foreach (errors('name') as $dito): ?><span class="dito"><?= e($dito) ?></span><?php endforeach; ?>
                </div>

                <div class="campo <?= errors('email') ? 'campo--dito' : '' ?>">
                  <label for="email-<?= (int)$consulta['id'] ?>"><?= e(__('marcacao.email')) ?></label>
                  <input type="email" id="email-<?= (int)$consulta['id'] ?>" name="email"
                         value="<?= e((string)old('email')) ?>" autocomplete="email" required>
                  <?php foreach (errors('email') as $dito): ?><span class="dito"><?= e($dito) ?></span><?php endforeach; ?>
                </div>

                <div class="campo <?= errors('phone') ? 'campo--dito' : '' ?>">
                  <label for="tel-<?= (int)$consulta['id'] ?>">
                    <?= e(__('marcacao.phone')) ?>
                    <span class="opcional">(<?= e(__('marcacao.phone_optional')) ?>)</span>
                  </label>
                  <input type="tel" id="tel-<?= (int)$consulta['id'] ?>" name="phone"
                         value="<?= e((string)old('phone')) ?>" autocomplete="tel">
                  <?php foreach (errors('phone') as $dito): ?><span class="dito"><?= e($dito) ?></span><?php endforeach; ?>
                </div>

                <?php /* O «onde» só se pergunta às consultas que são as duas
                         coisas. Num selector de uma opção só, a escolha já está
                         feita — e perguntá-la era pedir uma decisão que não
                         existe. O controlador usa a mesma regra. */ ?>
                <?php if (count($modos) > 1): ?>
                <div class="campo <?= errors('mode') ? 'campo--dito' : '' ?>">
                  <label for="modo-<?= (int)$consulta['id'] ?>"><?= e(__('marcacao.mode')) ?></label>
                  <select id="modo-<?= (int)$consulta['id'] ?>" name="mode" required>
                    <?php foreach ($modos as $modo): ?>
                      <option value="<?= e($modo) ?>"<?= old('mode') === $modo ? ' selected' : '' ?>>
                        <?= e(__('consultas.mode.' . $modo)) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php foreach (errors('mode') as $dito): ?><span class="dito"><?= e($dito) ?></span><?php endforeach; ?>
                </div>
                <?php else: ?>
                  <input type="hidden" name="mode" value="<?= e($modos[0]) ?>">
                <?php endif; ?>

                <?php /* Quantas pessoas, só onde cabe mais do que uma. Numa leitura
                         de uma pessoa, um campo com um «1» que não se pode mudar é
                         um campo a ocupar espaço. */ ?>
                <?php if ((int)$consulta['max_party'] > 1): ?>
                <div class="campo <?= errors('people') ? 'campo--dito' : '' ?>">
                  <label for="quantas-<?= (int)$consulta['id'] ?>"><?= e(__('marcacao.people')) ?></label>
                  <input type="number" id="quantas-<?= (int)$consulta['id'] ?>" name="people"
                         min="1" max="<?= (int)$consulta['max_party'] ?>"
                         value="<?= e((string)(old('people') ?: 1)) ?>" required>
                  <?php foreach (errors('people') as $dito): ?><span class="dito"><?= e($dito) ?></span><?php endforeach; ?>
                </div>
                <?php else: ?>
                  <input type="hidden" name="people" value="1">
                <?php endif; ?>

                <div class="campo">
                  <label for="nota-<?= (int)$consulta['id'] ?>"><?= e(__('marcacao.note')) ?></label>
                  <textarea id="nota-<?= (int)$consulta['id'] ?>" name="note" rows="3"><?= e((string)old('note')) ?></textarea>
                  <span class="ajuda"><?= e(__('marcacao.note_help')) ?></span>
                </div>

                <div class="campo campo--caixa <?= errors('consent') ? 'campo--dito' : '' ?>">
                  <input type="checkbox" id="ok-<?= (int)$consulta['id'] ?>" name="consent" value="1" required>
                  <label for="ok-<?= (int)$consulta['id'] ?>"><?= e(__('marcacao.consent')) ?></label>
                </div>
                <?php foreach (errors('consent') as $dito): ?><span class="dito"><?= e($dito) ?></span><?php endforeach; ?>

                <button type="submit" class="botão botão--cheio botão--largo">
                  <?= e(__('marcacao.submit')) ?>
                </button>
              </div>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <?php /* A ressalva, debaixo de onde se marca — e não só no rodapé. É no
             momento de marcar que importa lê-la, e é uma exigência de quem vende
             serviços desta natureza. Vem do catálogo e não deste ficheiro, para
             existir nas cinco línguas. */ ?>
    <p class="ressalva" style="margin-top: 32px"><?= e(__('consultas.disclaimer')) ?></p>

    <?php endif; ?>
  </div>
</section>
