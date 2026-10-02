<?php
declare(strict_types=1);

/**
 * A tabela de rotas. Carregada pelo front controller depois do boot.
 *
 * **Um endereço, e é a porta de entrada.** O site foi reduzido à homepage: a
 * loja, o checkout, as marcações, a entrada e o backoffice saíram, e com eles as
 * rotas que lhes davam acesso. O que ficou são os dados que a porta de entrada
 * lê — os produtos da montra, as etiquetas das intenções, as consultas da lista
 * —, que continuam na base de dados e continuam a ser lidos.
 *
 * Quem voltar a querer o resto tira-o do histórico: está no GitHub, no commit
 * anterior a esta redução.
 *
 * O apanha-tudo `/{slug}` também saiu. Com uma página só não há segundo `slug`
 * para apanhar, e deixá-lo de pé era servir a mesma página em qualquer endereço
 * escrito à sorte — o que um motor de busca lê como o site inteiro duplicado.
 */

use Admedia\Cms\Controllers\PageController;
use Admedia\Core\Middleware\CsrfMiddleware;

$r = \Admedia\Core\App::instance()->router();

$r->group(['middleware' => [CsrfMiddleware::class]], function ($r) {

    // ---------- A porta de entrada ----------
    $r->get('/', [PageController::class, 'home']);
});
