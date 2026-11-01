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
 * A porta de quem compra fica onde estava, em `/conta/entrar`. É outra tabela,
 * outra sessão e outra gente — ver Admedia\Core\Accounts\CustomerAuth e as duas
 * vistas, views/auth/login.php e views/account/login.php.
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
    'customer_login'  => '/conta/entrar',
    'customer_logout' => '/conta/sair',
    'customer_set'    => '/conta/definir',
    'customer_forgot' => '/conta/recuperar',
    'customer_reset'  => '/conta/nova-palavra-passe',
    'customer_signup' => '/conta/registar',
    'customer_home'   => '/account',
];
