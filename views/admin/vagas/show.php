<?php
/**
 * Uma vaga: quem vem, e o que se lhe pode fazer.
 *
 * As acções vivem aqui e não na grelha da semana. Na grelha estavam repetidas
 * cinquenta vezes — um campo de lotação e dois botões por linha — e uma parede
 * de campos não se lê. Aqui há uma vaga só, e ao lado dela está a lista de quem
 * fica sem consulta se ela for fechada. É a informação de que se precisa no
 * momento em que se decide.
 *
 * @var array      $vaga
 * @var array      $marcacoes
 * @var array|null $choque  a marcação viva que se cruza com esta hora, se houver
 * @var array      $modos   onde esta consulta pode acontecer
 */
$pageTitle = 'Vaga de ' . date('d/m/Y H:i', strtotime((string)$vaga['starts_at']));

$inicio = new DateTimeImmutable((string)$vaga['starts_at']);
$fim    = $inicio->modify('+' . (int)$vaga['duration_min'] . ' minutes');
$dias   = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];

$cap    = max(1, (int)$vaga['capacity']);
$tomado = (int)$vaga['seats_taken'];
$pct    = min(100, (int)round($tomado / $cap * 100));
$aberta = !empty($vaga['is_open']);
$estado = !$aberta ? 'is-fechada' : ($tomado >= $cap ? 'is-cheia' : ($pct >= 70 ? 'is-quase' : ''));

$vivas = 0;
foreach ($marcacoes as $r) { if ($r['status'] !== 'cancelada') { $vivas++; } }
?>

<div class="cz">

<header class="admin-header">
    <h1><?= e((string)$vaga['consultation_name']) ?></h1>
    <a href="/admin/marcacoes?de=<?= e(substr((string)$vaga['starts_at'], 0, 10)) ?>"
       class="btn-admin btn-admin--ghost">← Voltar ao calendário</a>
</header>

<div class="cz-barra">
    <div class="cz-barra__quando">
        <?= e($dias[(int)$inicio->format('N')]) ?>, <?= e($inicio->format('d/m/Y')) ?>
        <small>
            das <?= e($inicio->format('H:i')) ?> às <?= e($fim->format('H:i')) ?>
            <?php if ($vaga['source'] === 'avulsa'): ?> · data escrita à mão<?php endif; ?>
            <?php if (trim((string)($vaga['note'] ?? '')) !== ''): ?> · <?= e((string)$vaga['note']) ?><?php endif; ?>
        </small>
    </div>
    <span class="cz-selo<?= $aberta ? '' : ' is-cancelada' ?>"><?= $aberta ? 'Aberta' : 'Fechada' ?></span>
</div>

<?php /* Onde é. Está aqui porque é aqui que o telefone é atendido: quem marca
         tem de conseguir dizer à pessoa para onde é que ela vai.

         A morada é das Definições e não da consulta: a Casa é uma só, e uma
         coluna de morada por consulta deixava duas verdades sobre o mesmo sítio
         — no dia em que a Casa mudasse, uma ficava velha e ninguém saberia qual.
         O que varia por consulta é se é presencial, online, ou as duas. */ ?>
<?php
$onde = array_values(array_filter(
    array_map('trim', preg_split('/\R/', setting('contact.address')) ?: []),
    static fn(string $l): bool => $l !== ''
));
?>
<p class="cz-onde cz-onde--grande">
    <?= e(implode(', ', array_map(static fn(string $m): string => __('consultas.mode.' . $m), $modos))) ?>
    <?php if ($onde !== [] && in_array('presencial', $modos, true)): ?>
        &middot; <?= implode(' · ', array_map('e', $onde)) ?>
    <?php endif; ?>
</p>

<?php /* Com o que esta hora se cruza, se se cruzar com alguma coisa.

         É a informação que falta a quem vai marcar por telefone: a vaga pode ter
         lugar e a hora estar tomada por **outra** consulta, porque o Zé é um só.
         Sem este aviso, quem atende prometia a hora e só descobria o choque
         quando o formulário a recusasse — já depois de desligar. */ ?>
<?php if ($choque !== null): ?>
    <p class="cz-nada" style="border-left:3px solid var(--cz-cheio);padding-left:.75rem">
        <strong>Esta hora cruza-se com outra marcação.</strong>
        <?= e((string)$choque['consultation_name']) ?>
        às <?= e(date('H:i', strtotime((string)$choque['starts_at']))) ?>,
        de <?= e((string)$choque['name']) ?> (<?= e((string)$choque['code']) ?>).
        Marcar aqui vai ser recusado — cancele essa primeiro, ou escolha outra hora.
    </p>
<?php endif; ?>

<div class="cz-resumo">
    <div><strong><?= $tomado ?>/<?= (int)$vaga['capacity'] ?></strong><span>Lugares</span></div>
    <div><strong><?= max(0, $cap - $tomado) ?></strong><span>Livres</span></div>
    <div><strong><?= $vivas ?></strong><span>Marcação<?= $vivas === 1 ? '' : 's' ?></span></div>
</div>

<div class="cz-barrinha <?= $estado ?>" style="max-width:28rem;margin-bottom:1.5rem">
    <i style="width: <?= $pct ?>%"></i>
</div>

<section class="cz-seccao" style="margin-top:0">
    <h2>Mexer nesta vaga</h2>
    <p>
        Fechar impede marcações novas e <strong>não</strong> apaga as que já existem —
        se houver gente marcada, é preciso avisá-la. Baixar a lotação nunca desmarca
        ninguém: pára no que já está marcado.
    </p>

    <div class="cz-forma">
        <form class="inline-form" method="post" action="/admin/vagas/<?= (int)$vaga['id'] ?>">
            <?= csrf_field() ?>
            <label>Lotação
                <input type="number" name="capacity" min="1" max="999" value="<?= (int)$vaga['capacity'] ?>">
            </label>
            <button type="submit" class="btn-admin btn-admin--sm">Guardar</button>
        </form>

        <form class="inline-form" method="post" action="/admin/vagas/<?= (int)$vaga['id'] ?>/abrir">
            <?= csrf_field() ?>
            <button type="submit" class="btn-admin btn-admin--sm"><?= $aberta ? 'Fechar vaga' : 'Abrir vaga' ?></button>
        </form>

        <?php if ($vivas === 0): ?>
            <form class="inline-form" method="post" action="/admin/vagas/<?= (int)$vaga['id'] ?>"
                  data-confirm="Eliminar esta vaga?">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Eliminar</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if ($aberta && $tomado < $cap): ?>
    <?php /* O formulário está aqui e não numa página à parte porque a razão de
             ele existir é a chamada: alguém liga, quem atende já está a olhar
             para esta vaga, e mandá-lo a outro ecrã é fazê-lo perder o sítio.
             Aberto por omissão, e não escondido atrás de um botão — quando o
             telefone toca, um clique a menos é um clique a menos. */ ?>
    <section class="cz-seccao">
        <h2>Marcar por telefone</h2>
        <p>
            Fica confirmada de imediato e desconta os lugares. O código aparece
            a seguir, para o ditar antes de desligar. <strong>Nenhum e-mail sai
            daqui</strong> — quem está ao telefone já está a ser avisado.
        </p>

        <form method="post" action="/admin/marcacoes" class="cz-forma cz-forma--larga">
            <?= csrf_field() ?>
            <input type="hidden" name="slot_id" value="<?= (int)$vaga['id'] ?>">

            <label>Quem
                <input type="text" name="name" required maxlength="190"
                       value="<?= e((string)old('name')) ?>" placeholder="Nome de quem vem">
            </label>
            <?php /* Onde, só nas consultas que são as duas coisas. Numa que é só
                     presencial ou só online não há nada a perguntar, e o
                     controlador usa a mesma regra. */ ?>
            <?php if (count($modos) > 1): ?>
            <label>Onde
                <select name="mode">
                    <?php foreach ($modos as $m): ?>
                        <option value="<?= e($m) ?>"<?= old('mode') === $m ? ' selected' : '' ?>>
                            <?= e(__('consultas.mode.' . $m)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php else: ?>
                <input type="hidden" name="mode" value="<?= e($modos[0]) ?>">
            <?php endif; ?>

            <?php /* Pessoas, só onde cabe mais do que uma. O máximo é o que a vaga
                     tem livre e não o «máximo por marcação» da consulta: esse
                     existe para travar o site, e quem telefona é precisamente o
                     caso que o site não serve. */ ?>
            <?php if ($cap > 1): ?>
            <label>Pessoas
                <input type="number" name="people" required min="1" max="<?= $cap - $tomado ?>"
                       value="<?= e((string)old('people', '1')) ?>">
            </label>
            <?php else: ?>
                <input type="hidden" name="people" value="1">
            <?php endif; ?>
            <label>Telefone
                <input type="text" name="phone" maxlength="60"
                       value="<?= e((string)old('phone')) ?>" placeholder="+351 …">
            </label>
            <label>E-mail
                <input type="email" name="email" maxlength="190"
                       value="<?= e((string)old('email')) ?>" placeholder="opcional">
            </label>
            <label>Nota
                <input type="text" name="note" maxlength="190"
                       value="<?= e((string)old('note')) ?>" placeholder="O que a traz">
            </label>

            <button type="submit" class="btn-admin btn-admin--primary">
                Marcar (<?= $cap - $tomado ?> livre<?= $cap - $tomado === 1 ? '' : 's' ?>)
            </button>
        </form>
    </section>
<?php endif; ?>

<section class="cz-seccao">
    <h2>Quem vem</h2>

    <?php if (!$marcacoes): ?>
        <p class="cz-nada">Ainda não há marcações nesta vaga.</p>
    <?php else: ?>
        <div class="cz-slot">
            <ul class="cz-pessoas">
                <?php foreach ($marcacoes as $r): ?>
                    <li class="cz-pessoa<?= $r['status'] === 'cancelada' ? ' is-cancelada' : '' ?>">
                        <span class="cz-pessoa__pax"><?= (int)$r['people'] ?></span>
                        <span class="cz-pessoa__nome">
                            <a href="/admin/marcacoes/<?= (int)$r['id'] ?>"><?= e((string)$r['name']) ?></a>
                            <small><?= e((string)$r['code']) ?><?= $r['status'] === 'cancelada' ? ' · cancelada' : '' ?></small>
                        </span>
                        <span class="cz-pessoa__contacto">
                            <?php /* Um `mailto:` vazio é uma ligação que abre o
                                     programa de e-mail sem destinatário. Quem marca
                                     por telefone pode não dar e-mail — ver
                                     BookingAdminController::store. */ ?>
                            <?php if (trim((string)$r['email']) !== ''): ?>
                                <a href="mailto:<?= e((string)$r['email']) ?>"><?= e((string)$r['email']) ?></a>
                            <?php endif; ?>
                            <?php if (trim((string)$r['phone']) !== ''): ?>
                                <?= trim((string)$r['email']) !== '' ? '&middot;' : '' ?>
                                <a href="tel:<?= e(\Admedia\Cms\Phone::dial((string)$r['phone'])) ?>"><?= e((string)$r['phone']) ?></a>
                            <?php endif; ?>
                        </span>
                        <?php if ($r['status'] !== 'cancelada'): ?>
                            <form class="inline-form" method="post"
                                  action="/admin/marcacoes/<?= (int)$r['id'] ?>/cancelar"
                                  data-confirm="Cancelar a marcação de <?= e((string)$r['name']) ?>? Os lugares voltam à vaga.">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Cancelar</button>
                            </form>
                        <?php endif; ?>
                        <?php if (trim((string)($r['note'] ?? '')) !== ''): ?>
                            <span class="cz-pessoa__nota"><?= nl2br(e((string)$r['note'])) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

</div>
