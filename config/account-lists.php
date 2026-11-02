<?php
declare(strict_types=1);

/**
 * O que a conta de um cliente mostra neste site.
 *
 * Declarado e não adivinhado: o Core não sabe o que é uma reserva nem uma
 * encomenda, e adivinhar pelo nome da tabela já correu mal uma vez — há um
 * site com uma tabela `bookings` que é dele, das visitas à adega, com
 * outras colunas. Ver Admedia\Core\Accounts\AccountHistory.
 *
 * Cada camada instalada contribui com a sua lista; as que não estiverem
 * instaladas devolvem um array vazio e não aparecem.
 */

$listas = \Admedia\Viagens\Viagens::accountLists()
        + \Admedia\Shop\Shop::accountLists();

/* O título, na voz desta casa. O pacote escreve «As suas compras» e este site
   trata toda a gente por tu — «a tua conta», «o teu email», «ainda não
   compraste». Uma linha em você no meio disso lê-se como se fosse de outro
   sítio. Só o rótulo muda; quem vai buscar as linhas continua a ser o pacote. */
if (isset($listas['orders'])) {
    $listas['orders']['label'] = 'As tuas compras';
}

return $listas;
