<?php
declare(strict_types=1);

/**
 * A tabela de rotas. Carregada pelo front controller depois do boot.
 *
 * A ordem conta: o router devolve o primeiro padrão que casa, e por isso o
 * apanha-tudo `/{slug}` fica REGISTADO EM ÚLTIMO. O que for acrescentado
 * depois dele é inalcançável.
 */

use Admedia\Cms\Controllers\Admin\BlockPreviewController;
use Admedia\Cms\Controllers\Admin\DashboardController;
use Admedia\Cms\Controllers\Admin\EditorController;
use Admedia\Cms\Controllers\Admin\EnquiryAdminController;
use Admedia\Cms\Controllers\Admin\MenuAdminController;
use Admedia\Cms\Controllers\Admin\PageAdminController;
use Admedia\Cms\Controllers\Admin\SectionAdminController;
use Admedia\Cms\Controllers\Admin\SettingsController;
use Admedia\Cms\Controllers\ContactController;
use Admedia\Cms\Controllers\PageController;
use Admedia\Core\Controllers\AuthController;
use Admedia\Core\Controllers\MigrationController;
use Admedia\Core\Controllers\UserAdminController;
use Admedia\Core\Middleware\AdminOnly;
use Admedia\Core\Middleware\AuthMiddleware;
use Admedia\Core\Middleware\CsrfMiddleware;
use Admedia\Core\Middleware\EditorOrAbove;
use Admedia\Shop\Shop;
use App\Controllers\Admin\BookingAdminController;
use App\Controllers\Admin\ConsultationAdminController;
use App\Controllers\Admin\SlotAdminController;
use App\Controllers\BookingController;

$r = \Admedia\Core\App::instance()->router();

/* O aviso do fornecedor de pagamento, FORA do grupo de CSRF.
   Quem chama não é um browser e não traz símbolo: o que o autentica é a
   assinatura sobre o corpo em bruto, verificada pelo próprio fornecedor — uma
   prova mais forte do que um símbolo de CSRF, porque mostra que quem envia tem
   o segredo do webhook e não apenas que o formulário veio daqui.

   Montado pelo pacote e não escrito aqui: o endereço está em config/shop.php. */
Shop::callbackRoutes($r);

$r->group(['middleware' => [CsrfMiddleware::class]], function ($r) {

    // ---------- A porta de entrada ----------
    $r->get('/', [PageController::class, 'home']);

    // ---------- A loja ----------
    /* Registada antes do apanha-tudo `/{slug}`, ou «loja» era procurada como se
       fosse uma página. Ver é GET e não muda nada; tudo o que mexe no cesto é um
       POST que reencaminha, para uma recarga não comprar duas vezes.

       Os endereços estão em config/shop.php e a ordem por que se registam é do
       pacote — `/loja/categoria/{slug}` tem de vir antes de `/loja/{slug}`, ou
       «categoria» é procurada como se fosse um produto. Isso é conhecimento da
       loja e não deste ficheiro. */
    Shop::routes($r);

    // ---------- As marcações de consulta ----------
    /* Escolhe-se uma vaga que existe e o lugar fica seguro no instante. Não há
       aqui um GET para o formulário: ele vive dentro do bloco das consultas, na
       página onde a casa o puser, e a resposta volta a essa mesma página.

       O endereço de uma marcação leva o símbolo e não o código: o código diz-se
       ao telefone e é curto de propósito, e um endereço adivinhável dava a
       qualquer pessoa a marcação de outra — com o nome, o telefone e a razão por
       que vem, que numa casa destas é o que menos se quer à mostra.

       As vagas livres vão a JSON porque a maquete escolhe o dia sem recarregar a
       página. GET, e sem nada que identifique quem pergunta: só diz que às 15h00
       de quinta há lugar. */
    $r->get ('/consultas/vagas',            [BookingController::class, 'slots']);
    $r->post('/marcar',                     [BookingController::class, 'store']);
    $r->get ('/marcacao/{token}',           [BookingController::class, 'show']);
    $r->post('/marcacao/{token}/cancelar',  [BookingController::class, 'cancel']);

    // ---------- O formulário de contacto ----------
    // Só POST: o formulário vive dentro de um bloco, na página onde o editor o
    // pôs, e volta a essa mesma página. Não há aqui nada a GET.
    $r->post('/enquiry', [ContactController::class, 'store']);

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
           lado e as vistas dele escrevem-nos tal e qual. */
        Shop::adminRoutes($r);

        // ---------- As consultas ----------
        /* Três ecrãs, e não quatro. Vive aqui e não no pacote porque é desta
           casa: um CMS partilhado não tem consultas de tarot. Ver o README.

             Marcações     o calendário (onde marcar) e a lista (quem vem)
             Consultas     o que é, quanto dura, quanto custa, e quando é
             Encerramentos os dias em que a Débora não atende

           O ecrã de uma vaga não é um quarto ecrã: é o detalhe de um dia do
           calendário, e abre-se de lá. */

        $r->get ('/marcacoes',               [BookingAdminController::class, 'index']);
        // Marcar por telefone. O formulário vive no ecrã da vaga, que é onde se
        // está a olhar quando o telefone toca.
        $r->post('/marcacoes',               [BookingAdminController::class, 'store']);
        $r->get ('/marcacoes/{id}',          [BookingAdminController::class, 'show']);
        $r->post('/marcacoes/{id}/cancelar', [BookingAdminController::class, 'cancel']);

        $r->get   ('/consultas',               [ConsultationAdminController::class, 'index']);
        $r->get   ('/consultas/nova',          [ConsultationAdminController::class, 'create']);
        $r->post  ('/consultas',               [ConsultationAdminController::class, 'store']);
        $r->get   ('/consultas/{id}/edit',     [ConsultationAdminController::class, 'edit']);
        $r->post  ('/consultas/{id}',          [ConsultationAdminController::class, 'update']);
        $r->post  ('/consultas/{id}/publicar', [ConsultationAdminController::class, 'togglePublish']);
        $r->delete('/consultas/{id}',          [ConsultationAdminController::class, 'destroy']);
        $r->post  ('/consultas/{id}/horario',  [ConsultationAdminController::class, 'storeSchedule']);
        $r->post  ('/horario/{scheduleId}',    [ConsultationAdminController::class, 'updateSchedule']);
        $r->delete('/horario/{scheduleId}',    [ConsultationAdminController::class, 'destroySchedule']);

        $r->get   ('/encerramentos',             [ConsultationAdminController::class, 'closures']);
        $r->post  ('/encerramentos',             [ConsultationAdminController::class, 'storeClosure']);
        $r->delete('/encerramentos/{closureId}', [ConsultationAdminController::class, 'destroyClosure']);

        // Uma vaga. `/vagas` sem id fica de pé só para reencaminhar: há-de
        // acabar em marcadores, e um endereço que deixa de existir é um erro que
        // ninguém percebe.
        $r->get   ('/vagas',            [SlotAdminController::class, 'index']);
        $r->post  ('/vagas',            [SlotAdminController::class, 'store']);
        $r->get   ('/vagas/{id}',       [SlotAdminController::class, 'show']);
        $r->post  ('/vagas/{id}',       [SlotAdminController::class, 'update']);
        $r->post  ('/vagas/{id}/abrir', [SlotAdminController::class, 'toggle']);
        $r->delete('/vagas/{id}',       [SlotAdminController::class, 'destroy']);

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

    // As migrações por HTTP, para alojamento sem shell. Fechadas por um símbolo
    // longo e aleatório em config.php; inertes enquanto ele estiver vazio.
    // Apagar esta linha tira o endereço de vez.
    $r->get('/_migrate', [MigrationController::class, 'run']);

    // ---------- As páginas do CMS (apanha-tudo — fica em último) ----------
    $r->get('/{slug}', [PageController::class, 'show']);
});
