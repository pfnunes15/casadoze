<?php
declare(strict_types=1);

/**
 * A tabela de rotas. Carregada pelo front controller depois do boot.
 *
 * **Dois endereços: a porta de entrada e as migrações.** O site foi reduzido à homepage: a
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
use Admedia\Core\Controllers\MigrationController;
use Admedia\Core\Middleware\CsrfMiddleware;

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
     * Saiu por engano quando a tabela foi reduzida a uma linha, e o README
     * continuava a mandar usá-la. */
    $r->get('/_migrate', [MigrationController::class, 'run']);
});
