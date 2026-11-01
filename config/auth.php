<?php
declare(strict_types=1);

/**
 * As duas portas desta casa.
 *
 * Substitui o ficheiro do pacote inteiro — o `App::configFile()` devolve o
 * primeiro que encontrar e não junta os dois —, por isso estão aqui todas as
 * chaves e não só as que mudam.
 *
 * **A porta da equipa saiu de `/login`.** O que por omissão está na raiz do site
 * passou para debaixo do `/admin`, que é onde quem a procura vai primeiro: escreve
 * `/admin`, não tem sessão, e o `AuthMiddleware` encaminha-o para `/admin/entrar`;
 * com sessão, `/admin` é o painel. Os dois não podem ser o mesmo endereço — a
 * porta a redirecionar para si própria é um ciclo sem fim.
 *
 * A porta de quem compra ficou com o `/login` que a equipa largou. É a porta
 * do site e é por ela que passa quem lá chega; a outra é uma ferramenta de
 * trabalho e mora onde se trabalha. São outra tabela, outra sessão e outra
 * gente — ver Admedia\Core\Accounts\CustomerAuth e as duas vistas,
 * views/auth/login.php e views/account/login.php.
 *
 * A conta do cliente inteira mora debaixo do `/login`, como a da equipa mora
 * debaixo do `/admin`. Um prefixo por porta: quem olha para um endereço sabe
 * de qual das duas se trata, e não há um `/conta` e um `/login` a falar da
 * mesma coisa.
 */

return [

    // ---------------------------------------------------------------- a equipa
    'admin_login'   => '/admin/entrar',
    'admin_home'    => '/admin',
    'admin_forgot'  => '/admin/recuperar',
    'admin_reset'   => '/admin/nova-palavra-passe',
    'admin_profile' => '/admin/perfil',
    'admin_logout'  => '/admin/sair',

    // ------------------------------------------------------------- os clientes
    'customer_login'  => '/login',
    'customer_logout' => '/login/sair',
    'customer_set'    => '/login/definir',
    'customer_forgot' => '/login/recuperar',
    'customer_reset'  => '/login/nova-palavra-passe',
    'customer_signup' => '/login/registar',
    'customer_home'   => '/account',
];
