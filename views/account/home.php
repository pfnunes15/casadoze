<?php
/**
 * A conta: o que esta pessoa comprou, e onde lhe entregamos.
 *
 * As listas vêm do Core a perguntar à base de dados o que ela tem — ver
 * Admedia\Core\Accounts\AccountHistory — e este ficheiro não sabe quais são
 * nem porquê. Hoje são as compras; o dia em que as marcações entrarem na
 * `config/account-lists.php`, aparecem aqui sem se tocar nisto.
 *
 * **A morada não é uma lista e está escrita à mão.** É uma coisa só, da própria
 * pessoa, e mostrá-la ao lado das compras é o que poupa a pergunta «para onde é
 * que isto vai?» — que é a pergunta que se faz antes de comprar outra vez.
 *
 * @var array $customer
 * @var array $lists
 */
use Admedia\Core\Auth;

$pageTitle  = 'A minha conta · ' . setting('site.name', 'Covil Ancestral');
$pageRobots = 'noindex, nofollow';

$nome  = trim((string)$customer['name']);
$email = trim((string)$customer['email']);

/* As mensagens desta página. O `$flashes` é o que a View passa a todas as
   vistas, e cada chave traz uma lista porque nada impede duas. */
$diz = static fn(string $chave): array => (array)(($flashes ?? [])[$chave] ?? []);

/* A morada, nas linhas em que foi escrita. Vazia quer dizer que esta pessoa
   ainda não comprou nada — a morada é guardada no checkout e não aqui.
   Com `??` porque quem monta esta linha é o pacote, e uma vista do site que
   rebenta quando o pacote deixa de trazer uma coluna é uma vista frágil. */
$dado   = static fn(string $k): string => trim((string)($customer[$k] ?? ''));
$morada = array_values(array_filter([
    $dado('ship_line1'),
    $dado('ship_line2'),
    trim($dado('ship_postcode') . ' ' . $dado('ship_city')),
], static fn(string $l): bool => $l !== ''));

/* O que cada estado de encomenda quer dizer a quem comprou. O `pending` é o que
   muda de sentido com a forma de pagamento — um cartão que não foi pago e uma
   transferência à espera são a mesma linha na base de dados — e por isso é lido
   com o `gateway` ao lado. */
$estados = [
    'paid'      => ['Paga',       'conta__selo--bom'],
    'shipped'   => ['Enviada',    'conta__selo--bom'],
    'cancelled' => ['Cancelada',  'conta__selo--morto'],
    'refunded'  => ['Devolvida',  'conta__selo--morto'],
];
?>
<section class="secção conta">
  <div class="miolo">

    <header class="conta__topo">
      <p class="sobrescrita">A tua conta</p>
      <h1 class="conta__título"><?= e($nome !== '' ? $nome : $email) ?></h1>

      <div class="conta__linha">
        <?php if ($nome !== ''): ?>
          <span class="conta__email"><?= e($email) ?></span>
        <?php endif; ?>
        <form method="post" action="<?= e(locale_url(Auth::doors()['customer_logout'] ?? '/login/sair')) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="conta__sair">Terminar sessão</button>
        </form>
      </div>
    </header>

    <?php foreach ($diz('account.ok') as $m): ?>
      <p class="aviso aviso--bom" role="status"><?= e($m) ?></p>
    <?php endforeach; ?>

    <div class="conta__par">

      <?php foreach ($lists as $lista): ?>
        <section class="conta__caixa">
          <h2 class="conta__caixa-título"><?= e($lista['label']) ?></h2>

          <?php if (!$lista['rows']): ?>
            <?php /* Um vazio que diz o que fazer a seguir. «Ainda não tem
                     nenhuma» e mais nada deixa a pessoa num ecrã sem saída. */ ?>
            <p class="conta__vazio">Ainda não compraste nada por aqui.</p>
            <a class="botão botão--ouro" href="<?= e(locale_url('/loja')) ?>">
              <span>Ver a loja</span>
              <span aria-hidden="true">&rarr;</span>
            </a>

          <?php else: ?>
            <ul class="conta__lista">
              <?php foreach ($lista['rows'] as $r): ?>
                <?php
                $estado = (string)($r['status'] ?? '');
                [$diz_estado, $classe] = $estados[$estado] ?? ['A aguardar pagamento', 'conta__selo--espera'];
                ?>
                <li class="conta__item">
                  <span class="conta__ref"><?= e((string)$r['reference']) ?></span>
                  <span class="conta__quando"><?= e(format_date((string)$r['created_at'], 'd/m/Y')) ?></span>
                  <span class="conta__selo <?= e($classe) ?>"><?= e($diz_estado) ?></span>
                  <span class="conta__valor"><?= e(money((int)$r['total_cents'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

      <?php /* Os dados, para mudar aqui. Antes só se mostravam e a página
               mandava a pessoa esperar pelo checkout da compra seguinte — o que
               é verdade e é pouco: quem muda de casa não quer esperar por uma
               encomenda para o dizer.

               Um formulário aberto e não um «editar» que o abre: são seis
               campos e escondê-los atrás de um botão é um clique a mais para
               corrigir uma letra no código postal. */ ?>
      <section class="conta__caixa conta__caixa--lado">
        <h2 class="conta__caixa-título">Os teus dados</h2>

        <?php if ($morada === []): ?>
          <p class="conta__vazio">
            Ainda não temos morada tua. Escreve-a aqui e fica guardada para as
            próximas compras.
          </p>
        <?php endif; ?>

        <form class="conta__forma" method="post"
              action="<?= e(locale_url((Auth::doors()['customer_login'] ?? '/login') . '/dados')) ?>">
          <?= csrf_field() ?>

          <p class="porta__campo">
            <label class="campo__rótulo" for="d-name">Nome</label>
            <input class="campo" type="text" id="d-name" name="name" maxlength="190"
                   value="<?= e((string)old('name', $dado('name'))) ?>" autocomplete="name">
          </p>

          <p class="porta__campo">
            <label class="campo__rótulo" for="d-line1">Morada</label>
            <input class="campo" type="text" id="d-line1" name="ship_line1" maxlength="190"
                   value="<?= e((string)old('ship_line1', $dado('ship_line1'))) ?>"
                   autocomplete="address-line1">
          </p>

          <p class="porta__campo">
            <label class="campo__rótulo" for="d-line2">
              Mais da morada <span class="campo__opcional">(opcional)</span>
            </label>
            <input class="campo" type="text" id="d-line2" name="ship_line2" maxlength="190"
                   value="<?= e((string)old('ship_line2', $dado('ship_line2'))) ?>"
                   autocomplete="address-line2">
          </p>

          <div class="conta__dupla">
            <p class="porta__campo">
              <label class="campo__rótulo" for="d-postcode">Código postal</label>
              <input class="campo" type="text" id="d-postcode" name="ship_postcode" maxlength="30"
                     value="<?= e((string)old('ship_postcode', $dado('ship_postcode'))) ?>"
                     autocomplete="postal-code">
            </p>

            <p class="porta__campo">
              <label class="campo__rótulo" for="d-city">Cidade</label>
              <input class="campo" type="text" id="d-city" name="ship_city" maxlength="120"
                     value="<?= e((string)old('ship_city', $dado('ship_city'))) ?>"
                     autocomplete="address-level2">
            </p>
          </div>

          <p class="porta__campo">
            <label class="campo__rótulo" for="d-country">País</label>
            <?php /* Uma lista e não uma caixa de escrever: a coluna guarda duas
                     letras. A lista vem do controlador, que é quem a valida —
                     duas listas destas são duas listas que têm de concordar. */ ?>
            <select class="campo" id="d-country" name="ship_country" autocomplete="country">
              <?php
              $atual  = strtoupper((string)old('ship_country', $dado('ship_country')));
              $países = ['' => '—'] + \App\Controllers\ContaController::PAISES;
              ?>
              <?php foreach ($países as $código => $nome): ?>
                <option value="<?= e($código) ?>" <?= $atual === $código ? 'selected' : '' ?>>
                  <?= e($nome) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </p>

          <p class="porta__campo">
            <label class="campo__rótulo" for="d-phone">
              Telefone <span class="campo__opcional">(opcional)</span>
            </label>
            <input class="campo" type="tel" id="d-phone" name="phone" maxlength="60"
                   value="<?= e((string)old('phone', $dado('phone'))) ?>" autocomplete="tel">
          </p>

          <button type="submit" class="botão botão--cheio botão--largo">
            <span>Guardar</span>
          </button>
        </form>
      </section>

    </div>
  </div>
</section>
