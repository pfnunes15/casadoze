<?php
declare(strict_types=1);

/**
 * As linhas que esta casa pendura na barra do backoffice.
 *
 * O CMS sabe de páginas, menus, pedidos e loja. Não sabe de consultas de tarot,
 * e não deve saber: um pacote partilhado por oito sites não tem a agenda do Covil. É
 * aqui que os três ecrãs desta casa entram na barra, lidos por
 * Admedia\Cms\AdminNav.
 *
 * **Antes disto, a única maneira era copiar o `views/layouts/admin.php` do
 * pacote e editar a cópia.** Este site teve exactamente essa cópia, com o custo
 * escrito num comentário: cento e quarenta linhas bifurcadas para acrescentar
 * três, e uma cópia que deixa de receber o que o pacote faz ao backoffice. A
 * cópia saiu; ficou este ficheiro.
 *
 * O `count` é um closure e não um número: a consulta à base de dados só se paga
 * quando a barra é mesmo desenhada.
 */

use App\Models\Booking;

return [
    'Tarot' => [
        /* As marcações primeiro: é o ecrã a que se volta todos os dias, e o
           único com um número que interessa de relance. */
        [
            'label' => 'Marcações',
            'url'   => '/admin/marcacoes',
            'match' => ['/admin/marcacoes', '/admin/vagas'],
            'count' => static fn(): int => Booking::upcomingCount(),
        ],
        [
            'label' => 'Consultas',
            'url'   => '/admin/consultas',
            'match' => ['/admin/consultas', '/admin/horario'],
        ],
        [
            'label' => 'Encerramentos',
            'url'   => '/admin/encerramentos',
        ],
        /* Quem lê as cartas. Fica debaixo das consultas e não num grupo seu: é
           quem as dá, e separá-las era pôr duas entradas na barra para a mesma
           ida ao backoffice. */
        [
            'label' => 'A Sala',
            'url'   => '/admin/sala',
        ],
        /* O Grimório. Fica no mesmo grupo porque é a mesma pessoa que o escreve
           — quem grava as sessões é quem dá as consultas — e porque o que elas vendem é a loja, que já
           tem grupo seu logo abaixo. */
        [
            'label' => 'Grimório',
            'url'   => '/admin/grimorio',
        ],
    ],
];
