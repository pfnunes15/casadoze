<?php
declare(strict_types=1);

/**
 * Create the first administrator.
 *
 * Credentials come from the environment, never from this file:
 *
 *   ADMIN_EMAIL=you@example.com \
 *   ADMIN_NAME="Your Name" \
 *   php bin/seed-admin.php
 *
 * ADMIN_PASSWORD is optional. Left unset, a strong one is generated and printed
 * once — which is the better habit, since it never sits in a shell history.
 *
 * Refuses to run when any user already exists, so it cannot be used to graft an
 * administrator onto a live site.
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

$email = trim((string)getenv('ADMIN_EMAIL'));
$name  = trim((string)getenv('ADMIN_NAME')) ?: 'Administrator';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "ADMIN_EMAIL must be set to a valid e-mail address.\n");
    exit(1);
}

$password = (string)getenv('ADMIN_PASSWORD');
$generated = $password === '';
if ($generated) {
    $password = \Admedia\Core\Auth::generatePassword();
} elseif (strlen($password) < 12) {
    fwrite(STDERR, "ADMIN_PASSWORD must be at least 12 characters.\n");
    exit(1);
}

try {
    $db = new \Admedia\Core\Database($config['db']);

    if ((int)$db->fetchColumn('SELECT COUNT(*) FROM users') > 0) {
        fwrite(STDERR, "Refusing to run: the users table is not empty.\n");
        exit(1);
    }

    $db->insert('users', [
        'email'         => strtolower($email),
        'name'          => $name,
        'password_hash' => \Admedia\Core\Auth::hashPassword($password),
        'role'          => 'admin',
        'active'        => 1,
    ]);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Administrator created: {$email}\n";
if ($generated) {
    echo "Password: {$password}\n";
    echo "Shown once. Store it now, then change it after signing in.\n";
}
