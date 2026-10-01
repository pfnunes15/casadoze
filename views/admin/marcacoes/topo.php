<?php
/**
 * O cabeçalho das Marcações, com as duas vistas.
 *
 * As duas metades da mesma pergunta: **onde há lugar** (o calendário, onde se
 * marca) e **quem vem** (a lista, que se imprime de manhã). Um par de abas e
 * não duas entradas na barra lateral, porque quem está a atender uma chamada
 * salta de uma para a outra a meio da frase.
 *
 * Está num partial porque as duas vistas o desenham igual, e o `partial()`
 * corre em âmbito fechado — o que ele precisa vem por argumento e mais nada.
 *
 * @var string $vista   'calendario' ou 'lista'
 * @var int    $porVir  marcações por acontecer, ao todo
 */
?>
<header class="admin-header">
    <h1>Marcações<?php if ($porVir > 0): ?>
        <span class="cz-conta"><?= (int)$porVir ?> por vir</span>
    <?php endif; ?></h1>
</header>

<nav class="cz-abas">
    <a href="/admin/marcacoes" class="<?= $vista === 'calendario' ? 'is-on' : '' ?>">
        Calendário<small>onde há lugar</small>
    </a>
    <a href="/admin/marcacoes?vista=lista" class="<?= $vista === 'lista' ? 'is-on' : '' ?>">
        Lista<small>quem vem</small>
    </a>
</nav>
