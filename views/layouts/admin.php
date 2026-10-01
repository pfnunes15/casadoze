<?php
/*
 * ATENÇÃO: cópia do views/layouts/admin.php do pacote.
 *
 * Está aqui por uma razão só — pôr «Consultas» na barra lateral, que é desta
 * Casa e não do CMS. Procure por «ACRESCENTADO NESTE SITE»; é a única diferença.
 *
 * **O custo está aqui dito:** quando o pacote mexer nesta vista, esta cópia não
 * acompanha. Ao actualizar o CMS, compare os dois ficheiros. Se um dia o pacote
 * ganhar um sítio onde um site possa pendurar entradas suas, isto passa a ser
 * três linhas nesse sítio e este ficheiro desaparece.
 */
/**
 * Admin layout. The sidebar lists only routes that exist — every entry here has
 * a matching registration in config/routes.php.
 *
 * @var string $content
 */
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = fn(string $prefix): string => str_starts_with($path, $prefix) ? 'active' : '';
$user   = $auth->user();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Gestão') ?> — <?= e((string)$app->config('app.name', 'CMS')) ?></title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="stylesheet" href="<?= asset('css/brand.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <?php /* ACRESCENTADO NESTE SITE: os ecrãs das consultas. À parte da
             admin.css porque essa vem do pacote e é copiada por cima a cada
             `php bin/cms-publish.php` — uma regra escrita lá dentro
             desaparecia na primeira actualização. */ ?>
    <link rel="stylesheet" href="<?= asset('css/consultas-admin.css') ?>">
</head>
<body class="admin">
<div class="admin-layout">

    <aside class="admin-sidebar">
        <?php
        // The site's own mark, so an editor managing several sites can tell at a
        // glance which one they are in. The name is the fallback and the alt
        // text, never a second thing to read beside the logo.
        $adminLogo = setting('site.logo', setting('site.logo_light'));
        $adminName = setting('site.name', (string)$app->config('app.name', 'CMS'));
        ?>
        <a class="admin-sidebar__brand" href="/admin">
            <?php if ($adminLogo !== ''): ?>
                <img src="<?= e(media_url($adminLogo)) ?>" alt="<?= e($adminName) ?>">
            <?php else: ?>
                <span><?= e($adminName) ?></span>
            <?php endif; ?>
        </a>

        <?php if (\Admedia\Core\Locales::many()): ?>
            <?php
            /* Which language one is editing in. Everything below this — pages,
               blocks, menus, site texts — is per language, and this is what
               decides which. Buttons and not a dropdown: with two or three
               languages the choice is one click, and with no JavaScript at
               all. */
            $editing = \Admedia\Core\Locales::editing();
            ?>
            <form class="admin-locale" method="post" action="/admin/locale">
                <?= csrf_field() ?>
                <span class="admin-locale__label">A editar em</span>
                <div class="admin-locale__row">
                    <?php foreach (\Admedia\Core\Locales::enabled() as $code): ?>
                        <?php $lang = \Admedia\Core\Locales::get($code); ?>
                        <button type="submit" name="locale" value="<?= e($code) ?>"
                                title="<?= e($lang['label']) ?>"
                                class="admin-locale__pick<?= $code === $editing ? ' is-current' : '' ?>"
                                <?= $code === $editing ? 'aria-current="true"' : '' ?>><?= e($lang['short']) ?></button>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php endif; ?>

        <nav class="admin-sidebar__nav">
            <a href="/admin" class="<?= $path === '/admin' ? 'active' : '' ?>">Painel</a>

            <h6>O site</h6>
            <a href="/admin/pages" class="<?= $active('/admin/pages') ?>">Páginas</a>
            <a href="/admin/menus" class="<?= $active('/admin/menus') ?>">Menus</a>
            <?php /* The unread count is what makes anyone open this. Without
                     it, the enquiries piled up in a tab nobody had any reason
                     to visit. */ ?>
            <?php $porLer = \Admedia\Cms\Models\Enquiry::unreadCount(); ?>
            <a href="/admin/enquiries" class="<?= $active('/admin/enquiries') ?>">Pedidos<?php
                if ($porLer > 0): ?> <span class="nav-count"><?= (int)$porLer ?></span><?php endif; ?></a>

            <?php /* The shop only appears where it exists: the signal is the
                     site having a config/shop.php of its own — see
                     App::hasShop. Without this condition, a site with no shop
                     showed "Produtos" and "Encomendas" pointing at routes
                     nobody had registered. */ ?>
            <?php /* -------------------------------------------------------
                     ACRESCENTADO NESTE SITE: os ecrãs das consultas.
                     ------------------------------------------------------- */ ?>
            <h6>Consultas</h6>
            <?php /* A contagem do que está por vir é o que faz alguém abrir isto
                     de manhã. */ ?>
            <?php $porVir = class_exists(\App\Models\Booking::class) ? \App\Models\Booking::upcomingCount() : 0; ?>
            <?php /* Três entradas e não quatro. O calendário das vagas era a
                     quarta, e a divisão era do programa e não de quem o usa: uma
                     vaga só existe para ser marcada. É agora a vista
                     «Calendário» das Marcações — e o ecrã de uma vaga, que abre
                     de lá, não é uma entrada, é um detalhe. */ ?>
            <a href="/admin/marcacoes" class="<?= $active('/admin/marcacoes') . $active('/admin/vagas') ?>">Marcações<?php
                if ($porVir > 0): ?> <span class="nav-count"><?= (int)$porVir ?></span><?php endif; ?></a>
            <a href="/admin/consultas" class="<?= $active('/admin/consultas') ?>">Consultas</a>
            <a href="/admin/encerramentos" class="<?= $active('/admin/encerramentos') ?>">Dias encerrados</a>

            <?php if ($app->hasShop()): ?>
                <h6>A loja</h6>
                <a href="/admin/products" class="<?= $active('/admin/products') ?>">Produtos</a>
                <a href="/admin/orders" class="<?= $active('/admin/orders') ?>">Encomendas</a>
                <a href="/admin/shop/categories" class="<?= $active('/admin/shop/categories') ?>">Categorias</a>
                <a href="/admin/shop/attributes" class="<?= $active('/admin/shop/attributes') ?>">Características</a>
                <a href="/admin/shop/shipping" class="<?= $active('/admin/shop/shipping') ?>">Portes</a>
            <?php endif; ?>

            <?php if ($auth->hasRole('admin')): ?>
                <h6>Definições</h6>
                <a href="/admin/settings" class="<?= $active('/admin/settings') ?>">Definições do site</a>
                <a href="/admin/users" class="<?= $active('/admin/users') ?>">Utilizadores</a>
            <?php endif; ?>

            <h6>&nbsp;</h6>
            <a href="/" target="_blank" rel="noopener">Ver o site</a>
        </nav>

        <div class="admin-sidebar__user">
            <div class="admin-sidebar__user-avatar"><?= e(mb_strtoupper(mb_substr((string)$user['name'], 0, 1))) ?></div>
            <div>
                <strong><?= e($user['name']) ?></strong>
                <small><?= e($user['role']) ?></small>
                <form method="post" action="/logout">
                    <?= csrf_field() ?>
                    <button type="submit">Terminar sessão</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        <?php /* Pela cadeia das vistas, e não por `__DIR__`: esta cópia vive em
                 views/ do site, onde não há partials/flash.php — esse é do
                 pacote, e um caminho relativo a partir daqui procurava-o na pasta
                 errada.

                 O $flashes vai explícito porque o `partial()` corre num âmbito
                 fechado — só vê o que lhe passam —, e sem isto nenhuma mensagem
                 do backoffice aparecia em sítio nenhum. */ ?>
        <?php partial('partials/flash', ['flashes' => $flashes ?? []]); ?>
        <?= $content ?>
    </main>
</div>

<div id="admin-modal" class="admin-modal" hidden>
    <div class="admin-modal__box" role="dialog" aria-modal="true" aria-labelledby="admin-modal-title">
        <h3 id="admin-modal-title" class="admin-modal__title">Confirmar</h3>
        <p class="admin-modal__msg"></p>
        <div class="admin-modal__actions">
            <button type="button" class="btn-admin btn-admin--outline" data-modal-cancel>Cancelar</button>
            <button type="button" class="btn-admin btn-admin--danger" data-modal-ok>Eliminar</button>
        </div>
    </div>
</div>

<script src="<?= asset('js/admin.js') ?>" defer></script>
<?php /* Self-hosted: a CDN script with no integrity attribute is third-party
         code in an authenticated session, and the CSP forbids it. */ ?>
<script src="<?= asset('vendor/tinymce/tinymce.min.js') ?>"></script>
<script src="<?= asset('js/editor.js') ?>" defer></script>
<?php clear_old(); ?>
</body>
</html>
