<?php
declare(strict_types=1);

/**
 * As linhas que esta casa pendura na barra do backoffice.
 *
 * O CMS sabe de páginas, menus, pedidos e loja. Não sabe de consultas de tarot,
 * e não deve saber: um pacote partilhado por oito sites não tem agenda do Zé. É
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
    ],
];
