<?php
declare(strict_types=1);

/**
 * Encher o calendário das consultas a partir do horário.
 *
 *   php bin/gerar-vagas.php           até ao horizonte (90 dias)
 *   php bin/gerar-vagas.php 180       até onde se disser
 *
 * Correr as vezes que forem precisas não faz mal: o índice único de consulta
 * mais hora torna isto idempotente, e o que já existe fica com as marcações que
 * tiver. Ver App\Services\GeradorDeVagas.
 *
 * Para um cron de madrugada:
 *
 *   17 4 * * *  cd /caminho/do/site && php bin/gerar-vagas.php >> storage/logs/vagas.log 2>&1
 *
 * Sem cron o site desenrasca-se — o gerador corre sozinho quando o calendário
 * fica a menos de duas semanas do fim —, mas aí é quem abre o backoffice a pagar
 * a geração.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script corre só na linha de comandos.\n");
    exit(1);
}

$root = dirname(__DIR__);
if (!is_file($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "vendor/ em falta — corra composer install.\n");
    exit(1);
}
require $root . '/vendor/autoload.php';

$configFile = $root . '/config/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Falta o config/config.php — copie-o do config.example.php.\n");
    exit(1);
}

$config = require $configFile;
$config['app']['base_path'] = $root;

try {
    \Admedia\Core\App::boot($config);

    $dias    = isset($argv[1]) ? (int)$argv[1] : \App\Services\GeradorDeVagas::HORIZONTE_DIAS;
    $criadas = \App\Services\GeradorDeVagas::correr(null, $dias);

    echo date('Y-m-d H:i:s'), '  ', $criadas, " vaga(s) criada(s).\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'Falhou: ' . $e->getMessage() . "\n");
    exit(1);
}
