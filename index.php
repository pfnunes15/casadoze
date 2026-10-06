<?php
/**
 * O encaminhador do servidor embutido do PHP.
 *
 *     php -S 127.0.0.1:8000 index.php
 *
 * Serve os ficheiros estáticos de `public/` directamente e encaminha tudo o
 * resto para o front controller, que é o `public/index.php`.
 *
 * **Isto é código de desenvolvimento.** Em produção o docroot é `public/` e quem
 * serve os estáticos é o Apache ou o nginx; este ficheiro nunca é alcançado.
 *
 * Chama-se `index.php` por convenção entre os projectos e não porque o servidor
 * o exija — o nome é o que vai escrito na linha de comandos. Como está na raiz,
 * um docroot mal apontado para a raiz em vez de `public/` passa a encontrar aqui
 * um ponto de entrada que funciona, em vez de falhar à vista. É a troco disso
 * que se ganha o nome igual em todo o lado.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicDir = __DIR__ . '/public';
$filePath  = $publicDir . $uri;

// Servir ficheiro estático se existir
if ($uri !== '/' && is_file($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'pdf'   => 'application/pdf',
        'map'   => 'application/json',
        // O vídeo de fundo das aberturas. Sem o tipo certo o browser recusa-se
        // a tocá-lo — chega-lhe como «ficheiro», não como vídeo — e vê-se a
        // fotografia parada em vez do filme. Em produção quem trata disto é o
        // servidor a sério; isto é só para o servidor embutido do PHP não
        // mentir sobre o que está a servir.
        'webm'  => 'video/webm',
        'mp4'   => 'video/mp4',
    ];
    $mime = $mimes[$ext] ?? 'application/octet-stream';
    $tamanho = filesize($filePath);
    header('Content-Type: ' . $mime);

    /* PEDIDOS POR TROÇOS, que é como um browser pede um vídeo.
       O leitor de vídeo do Chrome não pede o ficheiro todo: pede «dá-me destes
       bytes até àqueles» e espera um 206 com o troço. O servidor embutido do
       PHP não sabe o que é isso — devolvia sempre o ficheiro inteiro com um
       200, e o leitor ficava à espera de uma resposta que nunca chegava: um
       quadrado preto com 0:00 e mais nada.

       Isto é código de desenvolvimento e mais nada — em produção quem trata
       disto é o Apache ou o nginx, que o fazem desde sempre. Está aqui para o
       que se vê na máquina de quem faz o site ser o que se vê no site. */
    $inicio = 0;
    $fim    = $tamanho - 1;
    $troco  = isset($_SERVER['HTTP_RANGE'])
           && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $pedido);

    header('Accept-Ranges: bytes');

    if ($troco) {
        if ($pedido[1] !== '') {
            $inicio = (int)$pedido[1];
            if ($pedido[2] !== '') { $fim = min((int)$pedido[2], $fim); }
        } elseif ($pedido[2] !== '') {
            // «bytes=-500»: os últimos 500.
            $inicio = max(0, $tamanho - (int)$pedido[2]);
        }
        if ($inicio > $fim) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            header('Content-Range: bytes */' . $tamanho);
            exit;
        }
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $inicio . '-' . $fim . '/' . $tamanho);
    }

    header('Content-Length: ' . ($fim - $inicio + 1));

    if ($inicio === 0 && $fim === $tamanho - 1) {
        readfile($filePath);
    } else {
        $f = fopen($filePath, 'rb');
        fseek($f, $inicio);
        $porLer = $fim - $inicio + 1;
        while ($porLer > 0 && !feof($f)) {
            echo fread($f, min(65536, $porLer));
            $porLer -= 65536;
        }
        fclose($f);
    }
    exit;
}

// Encaminhar para o front controller
require $publicDir . '/index.php';
