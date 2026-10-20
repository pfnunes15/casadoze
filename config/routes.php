<?php
declare(strict_types=1);

/**
 * A tabela de rotas. Carregada pelo front controller depois do boot.
 *
 * **A parte pública tem a porta de entrada, a loja e as consultas.** O site
 * chegou a estar reduzido a uma página; a loja voltou, e as consultas também —
 * com a agenda, que a maquete antiga não tinha e a nova tem.
 *
 * **O backoffice está ligado.** Páginas, blocos, menus, pedidos, definições,
 * utilizadores e a loja. Vem todo do pacote: os controladores, as vistas e o
 * layout são de lá, e este ficheiro só diz em que endereços vivem. É isso que
 * devolve à Casa a possibilidade de editar o que está no ar.
 *
 * **O backoffice das consultas também está**, e esse é desta casa e não do
 * pacote: marcações, vagas, horários e encerramentos. Um CMS partilhado não tem
 * consultas de tarot.
 *
 * O que continua fora é o resto do desenho: a Sala das tarólogas, o Grimório, a
 * Pesquisa, a Conta do cliente e o Painel. Ver `docs/maquete/NOTAS.md`.
 *
 * O apanha-tudo `/{slug}` voltou, e está **no fim de tudo**: qualquer endereço
 * que não seja nenhum dos de cima é procurado como página do CMS, e uma rota
 * escrita depois dele nunca seria alcançada — nem o `/admin`.
 */

use Admedia\Cms\Controllers\Admin\CmsUpdateController;
use Admedia\Cms\Controllers\Admin\FeatureController;
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
use App\Controllers\IntentController;
use App\Controllers\KitController;
use App\Controllers\CheckoutPageController;
use App\Controllers\GavetaController;
use App\Controllers\BookingController;
use App\Controllers\ConsultationController;
use App\Controllers\Admin\BookingAdminController;
use App\Controllers\Admin\ConsultationAdminController;
use App\Controllers\Admin\SlotAdminController;

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

    // ---------- A loja, do lado de fora ----------
    /* Montra, categoria, ficha, cesto e checkout. Tudo do pacote: os
       controladores, os modelos e o motor do dinheiro são de lá, e este site
       só põe as vistas e os endereços — `config/shop.php`, em `paths`.
     *
     * A ordem é do pacote e conta: a montra, depois a categoria, e só então a
     * ficha. O padrão da ficha é `/loja/{seja o que for}` e engolia a palavra
     * «categoria» se viesse primeiro.
     *
     * **Fica dentro do grupo do CSRF**: olhar é um GET e não muda nada, mas
     * tudo o que mexe no cesto é um POST e tem de trazer o símbolo.
     *
     * As contas de cliente — o `Shop::accountRoutes` — **não estão aqui, de
     * propósito**: por omissão o pacote põe-nas em `/login` e `/logout`, que é
     * exactamente onde está a porta de quem trabalha na Casa. Entram quando a
     * `Conta` for construída, e com endereços próprios declarados. */
    Shop::routes($r);

    /* A loja vista por uma intenção. **Não é do pacote**: um CMS partilhado
       arruma por categorias, e esta casa vende por razões — proteção, amor,
       sono. O bloco «Comprar por intenção» da porta de entrada já apontava
       para cá antes de este endereço existir, e dava 404 a quem lhe
       carregasse. Ver App\Controllers\IntentController. */
    $r->get('/intencao/{slug}', [IntentController::class, 'show']);

    /* Juntar ao cesto, de uma vez, o que a Casa usa com uma peça. O do pacote
       leva uma peça de cada vez, e está certo — isto é outra coisa, e chama o
       mesmo Cart::add() por dentro. Ver App\Controllers\KitController. */
    $r->post('/carrinho/kit', [KitController::class, 'add']);

    /* A página do checkout. O pacote só tem o `POST /checkout` — no desenho
       dele o cesto já pede o e-mail e a zona, e o botão manda tudo de uma vez.
       O desenho desta casa parte isso em dois, e esta é a metade que faltava.
       **Mostra; não cobra**: o formulário submete para o mesmo POST de sempre.
       Tem de ficar antes do `Shop::routes`? Não — são métodos diferentes no
       mesmo endereço, e o router distingue-os. */
    $r->get('/checkout', [CheckoutPageController::class, 'show']);

    /* O que está no cesto, como pedaço de página, para a gaveta lateral que a
       maquete abre sem tirar ninguém de onde está. Devolve HTML e não JSON:
       quem desenha o cesto é a vista em PHP, e um JSON obrigava a escrever a
       gaveta uma segunda vez em JavaScript. Ver App\Controllers\GavetaController. */
    $r->get('/carrinho/gaveta', [GavetaController::class, 'show']);

    // ---------- As consultas, do lado de fora ----------
    /* **Voltaram.** Saíram na redução, e a razão escrita na altura era que a
       maquete não tinha agenda na porta de entrada. O desenho novo tem — ver
       `docs/maquete/Consultas.dc.html`, com calendário, vagas e remarcação.
     *
     * A ordem conta: `/consultas/vagas` é literal e tem de vir antes de
     * `/consultas/{slug}`, que de outra maneira o engolia e tentava abrir uma
     * consulta chamada «vagas».
     *
     * O `/marcar` é um POST e não tem GET: o formulário vive dentro da ficha da
     * consulta, e um GET para ele seria uma segunda página a pedir o mesmo. */
    $r->get ('/consultas',         [ConsultationController::class, 'index']);
    $r->get ('/consultas/vagas',   [BookingController::class, 'slots']);
    $r->get ('/consultas/{slug}',  [ConsultationController::class, 'show']);

    $r->post('/marcar',                    [BookingController::class, 'store']);
    $r->get ('/marcacao/{token}',          [BookingController::class, 'show']);
    $r->post('/marcacao/{token}/cancelar', [BookingController::class, 'cancel']);

    // ---------- O que alguém escreve à Casa ----------
    /* Só o POST: o formulário é um bloco e vive dentro de uma página — a dos
       Contactos, hoje —, e volta sempre à página de onde saiu. Um GET para ele
       seria uma segunda página a pedir a mesma coisa. */
    $r->post('/contacto', [ContactController::class, 'store']);

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
        
        /* Um bloco desta página, com o conteúdo dela, para a tela do editor
           desenhar a página em vez de a descrever. Sem esta rota os quadros
           da tela caem no apanha-tudo e cada bloco aparece como a página
           inteira. Ver views/admin/partials/composer.php. */
        $r->get('/sections/{id}/preview', [BlockPreviewController::class, 'section']);

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
        /* Um campo, escrito directamente na folha do editor em vez de num
           formulário. Responde JSON — nada navega. Ver Admedia\Cms\LiveEdit. */
        $r->post  ('/sections/{id}/field',           [SectionAdminController::class, 'field']);
        $r->delete('/sections/{id}',                 [SectionAdminController::class, 'destroy']);

        /* A loja no backoffice: produtos, encomendas, categorias, características
           e portes. Endereços do pacote, porque o backoffice é o mesmo em todo o
           lado e as vistas dele escrevem-nos tal e qual.

           **A loja pública não existe** — saiu com a redução à porta de entrada.
           O que isto dá é a edição do catálogo que a montra mostra: os nomes, os
           preços, as fotografias e as existências. */
        Shop::adminRoutes($r);

        // ---------- As consultas ----------
        /* Três ecrãs, e não quatro. Vive aqui e não no pacote porque é desta
           casa: um CMS partilhado não tem consultas de tarot.

             Marcações     o calendário (onde marcar) e a lista (quem vem)
             Consultas     o que é, quanto dura, quanto custa, e quando é
             Encerramentos os dias em que o Zé não atende

           O ecrã de uma vaga não é um quarto ecrã: é o detalhe de um dia do
           calendário, e abre-se de lá.

           **Só o backoffice.** A marcação do lado de fora — o /marcar, o
           /consultas/vagas e o /marcacao/{token} — continua fora, porque a
           porta de entrada não tem agenda: a maquete não a tem, e a secção das
           consultas leva botões e não um selector de dia e hora. */

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

            /* O que o site tem ligado, e o que pode ligar. Instalar escreve
               na pasta config/ e mexe no esquema — por isso o controlador
               exige administrador, e não se fia só em a barra lateral o
               esconder. Ver Admedia\Cms\Features. */
            /* Actualizar o CMS sem FTP: o pacote é enviado daqui, nunca
               descarregado pelo site. Ver Admedia\Cms\Updater. */
            $r->get ('/cms',          [CmsUpdateController::class, 'index']);
            $r->post('/cms',          [CmsUpdateController::class, 'upload']);
            $r->post('/cms/terminar', [CmsUpdateController::class, 'finish']);
            
            $r->get ('/features',                [FeatureController::class, 'index']);
            $r->post('/features/{key}/install',  [FeatureController::class, 'install']);
            $r->post('/features/{key}/disable',  [FeatureController::class, 'disable']);

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

    /* ---------- O apanha-tudo ----------
     *
     * **Tem de ser a última.** Qualquer endereço que não seja nenhum dos de
     * cima é procurado como página do CMS, e uma rota escrita depois desta
     * nunca é alcançada.
     *
     * Saiu com a redução, e a razão escrita na altura era que com uma página
     * pública só não havia segundo `slug` para apanhar — deixá-lo de pé era
     * servir a porta de entrada em qualquer endereço escrito à sorte. Agora há
     * páginas: A Casa, os Contactos, o Legal. */
    $r->get('/{slug}', [PageController::class, 'show']);
});

/* ---------- O aviso de quem recebeu o dinheiro ----------
 *
 * **Fora do grupo do CSRF**, e é por isso que é uma chamada à parte: quem bate
 * a esta porta não é um browser e não traz símbolo nenhum. O que a autentica é
 * a assinatura sobre o corpo do pedido, verificada pela biblioteca do próprio
 * fornecedor — uma prova mais forte do que um símbolo de CSRF, porque prova que
 * quem envia tem o segredo, e não apenas que um formulário saiu daqui.
 *
 * **É isto que faz uma encomenda passar a paga**, e não o comprador voltar ao
 * site: quem volta pode ter fechado a janela antes de pagar, e quem paga pode
 * fechá-la depois. */
Shop::callbackRoutes($r);
