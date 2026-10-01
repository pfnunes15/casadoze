<?php
declare(strict_types=1);

/**
 * Front controller. Todos os requests passam por aqui.
 * Configuração: /config/config.php (copiar de config.example.php).
 */

// Pasta servida pela web (docroot). Usada pelo asset() para o cache-buster e
// pelo config para saber onde ficam os uploads públicos.
define('CMS_PUBLIC', __DIR__);

// Raiz da aplicação (src, views, config, storage). Normalmente é a pasta acima
// do docroot. Em alojamentos com docroot fixo (cPanel: public_html), a app fica
// noutra pasta fora da web — nesse caso apontar aqui, ex.:
//   $root = dirname(__DIR__) . '/cms';
$root = dirname(__DIR__);
// O CMS e os helpers vêm ambos do Composer: o autoload "files" do pacote
// carrega src/Core/helpers.php, por isso não há aqui nada a incluir à mão.
if (!is_file($root . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('<h1>Instalação incompleta</h1><p>Falta a pasta <code>vendor/</code>.</p>');
}
require $root . '/vendor/autoload.php';

$configPath = $root . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo '<h1>Configuração em falta</h1>';
    echo '<p>Copie <code>config/config.example.php</code> para <code>config/config.php</code> e configure os valores.</p>';
    exit;
}
$config = require $configPath;

// Onde o site vive. O CMS está em vendor/, por isso não consegue descobrir a
// raiz a partir de si próprio — dirname() de dentro do pacote aponta para o
// pacote. Quem sabe é este ficheiro, que está na pasta do site.
$config['app']['base_path'] = $root;

try {
    $app = \Admedia\Core\App::boot($config);
    require $root . '/config/routes.php';
    $app->run();
} catch (\Throwable $e) {
    http_response_code(500);
    if (($config['app']['debug'] ?? false) === true) {
        echo '<pre style="padding:2rem;font-family:monospace;background:#fef2f2;color:#7f1d1d;">';
        echo htmlspecialchars($e::class . ': ' . $e->getMessage() . "\nin " . $e->getFile() . ':' . $e->getLine());
        echo "\n\n" . htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        error_log('[CMS boot] ' . $e->getMessage());
        echo '<h1>Erro</h1><p>O site está temporariamente indisponível.</p>';
    }
}
