<?php
declare(strict_types=1);

/**
 * A tabela de rotas. Carregada pelo front controller depois do boot.
 *
 * **A parte pública é uma página só.** A loja, o checkout, as páginas de
 * produto e as marcações saíram quando o site foi reduzido à porta de entrada.
 * O que ficou são os dados que ela lê — os produtos da montra, as etiquetas das
 * intenções, as consultas da lista —, que continuam na base de dados.
 *
 * **O backoffice está ligado.** Páginas, blocos, menus, pedidos, definições,
 * utilizadores e a loja. Vem todo do pacote: os controladores, as vistas e o
 * layout são de lá, e este ficheiro só diz em que endereços vivem. É isso que
 * devolve à Casa a possibilidade de editar o que está no ar.
 *
 * **O backoffice das consultas não está.** Marcações, vagas, horários e
 * encerramentos eram deste projecto e foram apagados com o motor que os servia.
 * Para os ter de volta é preciso repor src/Models e src/Services do histórico —
 * é outro trabalho, e maior.
 *
 * O apanha-tudo `/{slug}` continua fora. Com uma página pública só não há
 * segundo `slug` para apanhar, e deixá-lo de pé era servir a mesma página em
 * qualquer endereço escrito à sorte.
 */

use Admedia\Cms\Controllers\Admin\BlockPreviewController;
use Admedia\Cms\Controllers\Admin\DashboardController;
use Admedia\Cms\Controllers\Admin\EditorController;
use Admedia\Cms\Controllers\Admin\EnquiryAdminController;
use Admedia\Cms\Controllers\Admin\MenuAdminController;
use Admedia\Cms\Controllers\Admin\PageAdminController;
use Admedia\Cms\Controllers\Admin\SectionAdminController;
use Admedia\Cms\Controllers\Admin\SettingsController;
use Admedia\Cms\Controllers\PageController;
use Admedia\Core\Controllers\AuthController;
use Admedia\Core\Controllers\MigrationController;
use Admedia\Core\Controllers\UserAdminController;
use Admedia\Core\Middleware\AdminOnly;
use Admedia\Core\Middleware\AuthMiddleware;
use Admedia\Core\Middleware\CsrfMiddleware;
use Admedia\Core\Middleware\EditorOrAbove;
use Admedia\Shop\Shop;

$r = \Admedia\Core\App::instance()->router();

$r->group(['middleware' => [CsrfMiddleware::class]], function ($r) {

    // ---------- A porta de entrada ----------
    $r->get('/', [PageController::class, 'home']);

    // ---------- As migrações por HTTP ----------
    /* Para alojamento sem shell, que é o caso: o servidor é partilhado e não há
       linha de comandos onde correr o bin/migrate.php.
     *
     * Fechada por um símbolo longo e aleatório em `security.migration_token`, no
     * config.php, e **inerte enquanto ele estiver vazio** — sem símbolo o
     * endereço recusa-se a agir. Limpar o símbolo assim que o site estiver no
     * ar; apagar esta linha tira o endereço de vez.
     *
     *     https://o-site/_migrate?token=O_SÍMBOLO&dry=1   listar
     *     https://o-site/_migrate?token=O_SÍMBOLO         aplicar
     *
     * O mesmo endereço cria o primeiro administrador, que não pode ser uma
     * migração em SQL porque precisa de gerar uma senha:
     *
     *     https://o-site/_migrate?token=O_SÍMBOLO&seed-admin=o@email.pt&name=Nome
     */
    $r->get('/_migrate', [MigrationController::class, 'run']);

    // ---------- Entrar ----------
    $r->get ('/login',           [AuthController::class, 'showLogin']);
    $r->post('/login',           [AuthController::class, 'login']);
    $r->post('/logout',          [AuthController::class, 'logout']);
    $r->get ('/password/forgot', [AuthController::class, 'showForgot']);
    $r->post('/password/forgot', [AuthController::class, 'sendReset']);
    $r->get ('/password/reset',  [AuthController::class, 'showReset']);
    $r->post('/password/reset',  [AuthController::class, 'doReset']);

    // ---------- A conta de quem entrou ----------
    $r->group(['middleware' => [AuthMiddleware::class]], function ($r) {
        $r->get ('/profile',          [AuthController::class, 'profile']);
        $r->post('/profile/password', [AuthController::class, 'changePassword']);
    });

    // ---------- O backoffice ----------
    $r->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class, EditorOrAbove::class]], function ($r) {
        $r->get('', [DashboardController::class, 'index']);

        // Em que língua se está a editar. Uma escolha, não um endereço: o
        // backoffice é o mesmo para todas — ver Admedia\Core\Locales::editing.
        $r->post('/locale', [DashboardController::class, 'setLocale']);
        // Voltar a perguntar já se há CMS novo, sem esperar pelo dia seguinte.
        $r->post('/verificar-cms', [DashboardController::class, 'checkUpdates']);

        // Imagens metidas pelo editor de texto.
        $r->post('/editor/upload', [EditorController::class, 'uploadImage']);

        // Como é um bloco, para quem o está a escolher. Desenhado com a folha de
        // estilo do site, e por isso vai num iframe e não para dentro da página
        // do backoffice.
        $r->get('/blocks/{type}/preview', [BlockPreviewController::class, 'show']);

        // Páginas. Uma página são os seus dados mais os blocos de que é feita, e
        // ambos se editam em /pages/{id}/edit.
        $r->get   ('/pages',              [PageAdminController::class, 'index']);
        $r->get   ('/pages/new',          [PageAdminController::class, 'create']);
        $r->post  ('/pages',              [PageAdminController::class, 'store']);
        $r->get   ('/pages/{id}/edit',    [PageAdminController::class, 'edit']);
        $r->post  ('/pages/{id}',         [PageAdminController::class, 'update']);
        $r->post  ('/pages/{id}/publish', [PageAdminController::class, 'togglePublish']);
        $r->post  ('/pages/{id}/menu',    [PageAdminController::class, 'addToMenu']);
        $r->delete('/pages/{id}',         [PageAdminController::class, 'destroy']);

        // Blocos. Um bloco grava num pedido só — os campos dele e todos os itens
        // que tem —, por isso não há endereços por item a alcançar.
        $r->get   ('/pages/{pageId}/sections',       [SectionAdminController::class, 'index']);
        $r->post  ('/pages/{pageId}/sections',       [SectionAdminController::class, 'store']);
        $r->post  ('/pages/{pageId}/sections/order', [SectionAdminController::class, 'reorder']);
        $r->get   ('/sections/{id}/edit',            [SectionAdminController::class, 'edit']);
        $r->post  ('/sections/{id}',                 [SectionAdminController::class, 'update']);
        $r->post  ('/sections/{id}/move',            [SectionAdminController::class, 'move']);
        $r->post  ('/sections/{id}/publish',         [SectionAdminController::class, 'togglePublish']);
        $r->delete('/sections/{id}',                 [SectionAdminController::class, 'destroy']);

        /* A loja no backoffice: produtos, encomendas, categorias, características
           e portes. Endereços do pacote, porque o backoffice é o mesmo em todo o
           lado e as vistas dele escrevem-nos tal e qual.

           **A loja pública não existe** — saiu com a redução à porta de entrada.
           O que isto dá é a edição do catálogo que a montra mostra: os nomes, os
           preços, as fotografias e as existências. */
        Shop::adminRoutes($r);

        // Pedidos. Ler e apagar, e mais nada — um registo que se pode editar não
        // é um registo.
        $r->get   ('/enquiries',      [EnquiryAdminController::class, 'index']);
        $r->get   ('/enquiries/{id}', [EnquiryAdminController::class, 'show']);
        $r->delete('/enquiries/{id}', [EnquiryAdminController::class, 'destroy']);

        // Menus
        $r->get   ('/menus',           [MenuAdminController::class, 'index']);
        $r->get   ('/menus/novo',      [MenuAdminController::class, 'create']);
        $r->post  ('/menus',           [MenuAdminController::class, 'store']);
        $r->get   ('/menus/{id}/edit', [MenuAdminController::class, 'edit']);
        $r->post  ('/menus/{id}',      [MenuAdminController::class, 'update']);
        $r->delete('/menus/{id}',      [MenuAdminController::class, 'destroy']);

        // ---------- Só o administrador ----------
        $r->group(['middleware' => [AdminOnly::class]], function ($r) {
            $r->get ('/settings', [SettingsController::class, 'edit']);
            $r->post('/settings', [SettingsController::class, 'update']);

            $r->get   ('/users',               [UserAdminController::class, 'index']);
            $r->get   ('/users/new',           [UserAdminController::class, 'create']);
            $r->post  ('/users',               [UserAdminController::class, 'store']);
            $r->get   ('/users/{id}/edit',     [UserAdminController::class, 'edit']);
            $r->post  ('/users/{id}',          [UserAdminController::class, 'update']);
            $r->post  ('/users/{id}/active',   [UserAdminController::class, 'toggleActive']);
            $r->post  ('/users/{id}/reinvite', [UserAdminController::class, 'resendInvite']);
            $r->delete('/users/{id}',          [UserAdminController::class, 'destroy']);
        });
    });
});
