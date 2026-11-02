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

      <section class="conta__caixa conta__caixa--lado">
        <h2 class="conta__caixa-título">Onde entregamos</h2>

        <?php if ($morada === []): ?>
          <p class="conta__vazio">
            Ainda não temos morada tua. Escreve-a na primeira compra e fica
            guardada para as seguintes.
          </p>
        <?php else: ?>
          <address class="conta__morada">
            <?php foreach ($morada as $linha): ?>
              <span><?= e($linha) ?></span>
            <?php endforeach; ?>
            <?php if ($dado('phone') !== ''): ?>
              <span class="conta__tenue"><?= e($dado('phone')) ?></span>
            <?php endif; ?>
          </address>
          <p class="conta__nota">
            Muda-se no checkout da próxima compra — é lá que é precisa, e é lá
            que se confere antes de a encomenda seguir.
          </p>
        <?php endif; ?>
      </section>

    </div>
  </div>
</section>
