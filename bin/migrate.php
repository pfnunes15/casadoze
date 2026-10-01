<?php
declare(strict_types=1);

/**
 * Apply pending migrations. Local and any host with shell access.
 *
 *   php bin/migrate.php            apply everything pending
 *   php bin/migrate.php --pending  list what would run, change nothing
 *
 * On hosting without a shell, use the web endpoint instead — see
 * MigrationController and the route that reaches it.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script runs from the command line only.\n");
    exit(1);
}

$root = dirname(__DIR__);
// O Composer quando existe; o autoloader próprio quando o site foi instalado
// por zip. O CMS vem sempre do vendor/, por isso sem ele não há nada a fazer.
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    fwrite(STDERR, "vendor/ em falta — corra composer install.\n");
    exit(1);
}

$configFile = $root . '/config/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Missing config/config.php — copy it from config/config.example.php.\n");
    exit(1);
}
$config = require $configFile;

try {
    $config['app']['base_path'] = $root;
    $app      = \Admedia\Core\App::boot($config);
    $migrator = new \Admedia\Core\Migrator($app->db(), $app->migrationPaths());

    $label = static fn(array $m): string => $m['source'] . '/' . $m['filename'];

    // Marcar uma sequência como aplicada sem correr uma linha dela. Para adoptar
    // o CMS num site cujas tabelas já existem — ver Migrator::baseline.
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--baseline=')) {
            // --baseline=cms/0001_initial.sql — a sequência e onde parar.
            [$source, $through] = array_pad(explode('/', substr($arg, 11), 2), 2, '');
            if ($through === '') {
                fwrite(STDERR, "Diga onde parar: --baseline=<sequência>/<ficheiro>,\n"
                             . "por exemplo --baseline=cms/0001_initial.sql\n");
                exit(1);
            }
            $recorded = $migrator->baseline($source, $through);
            echo $recorded
                ? "baseline  " . implode("\nbaseline  ", array_map($label, $recorded)) . "\nDone.\n"
                : "Nothing to baseline for '{$source}'.\n";
            exit(0);
        }
    }

    $pending = $migrator->pending();

    if (in_array('--pending', $argv, true)) {
        echo $pending ? "Pending:\n  " . implode("\n  ", array_map($label, $pending)) . "\n"
                      : "Nothing pending.\n";
        exit(0);
    }

    if (!$pending) {
        echo "Nothing pending — the database is up to date.\n";
        exit(0);
    }

    foreach ($migrator->run() as $migration) {
        echo 'applied  ' . $label($migration) . "\n";
    }
    echo "Done.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
