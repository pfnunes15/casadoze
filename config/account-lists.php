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

return \Admedia\Viagens\Viagens::accountLists()
     + \Admedia\Shop\Shop::accountLists();
