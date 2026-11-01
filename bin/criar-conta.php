<?php
declare(strict_types=1);

/**
 * Criar — ou repor — uma conta, de qualquer das duas portas.
 *
 *   CONTA_EMAIL=alguem@exemplo.pt CONTA_PASSWORD='…' \
 *   php bin/criar-conta.php equipa  "Nome de Quem É"
 *
 *   CONTA_EMAIL=alguem@exemplo.pt CONTA_PASSWORD='…' \
 *   php bin/criar-conta.php cliente "Nome de Quem É"
 *
 * `equipa` escreve na tabela `users` e dá acesso ao backoffice; `cliente`
 * escreve em `customers` e dá acesso à conta de quem compra. São tabelas
 * diferentes e sessões diferentes — ver config/auth.php.
 *
 * **A palavra-passe vem do ambiente e não de um argumento.** Um argumento fica
 * no `ps` enquanto o comando corre e no histórico da shell para sempre. Pelo
 * ambiente, e com um espaço antes do comando, não fica em sítio nenhum.
 *
 * Ao contrário do `seed-admin.php` do pacote, isto não se recusa a correr com a
 * tabela cheia: serve para repor a palavra-passe de uma conta que já existe, que
 * é metade do que se precisa dele. Por isso **não é para produção com clientes a
 * sério** sem se pensar duas vezes: reescreve a palavra-passe de quem lá estiver
 * com aquele email.
 */

use Admedia\Core\App;
use Admedia\Core\Auth;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script corre só na linha de comandos.\n");
    exit(1);
}

$porta = $argv[1] ?? '';
$nome  = trim((string)($argv[2] ?? ''));

if (!in_array($porta, ['equipa', 'cliente'], true)) {
    fwrite(STDERR, "Uso: CONTA_EMAIL=… CONTA_PASSWORD=… php bin/criar-conta.php equipa|cliente [Nome]\n");
    exit(1);
}

$email = strtolower(trim((string)getenv('CONTA_EMAIL')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "CONTA_EMAIL tem de ser um endereço válido.\n");
    exit(1);
}

$password = (string)getenv('CONTA_PASSWORD');
if (strlen($password) < 8) {
    fwrite(STDERR, "CONTA_PASSWORD tem de ter pelo menos 8 caracteres.\n");
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
    $db = App::boot($config)->db();

    /* O hash é o do pacote para a equipa e o `password_hash` do PHP para os
       clientes, porque é o que cada um dos dois lados verifica — ver
       Admedia\Core\Auth::verify e Accounts\CustomerAuth::attempt. */
    if ($porta === 'equipa') {
        $tabela = 'users';
        $hash   = Auth::hashPassword($password);
        $extra  = ['role' => 'admin', 'name' => $nome ?: 'Administração'];
    } else {
        $tabela = 'customers';
        $hash   = password_hash($password, PASSWORD_DEFAULT);
        $extra  = ['name' => $nome];
    }

    $existe = $db->fetch("SELECT id FROM {$tabela} WHERE email = :email LIMIT 1", ['email' => $email]);

    if ($existe) {
        $db->query(
            "UPDATE {$tabela} SET password_hash = :hash, active = 1 WHERE id = :id",
            ['hash' => $hash, 'id' => $existe['id']]
        );
        echo "Palavra-passe reposta: {$email} ({$porta}).\n";
    } else {
        $db->insert($tabela, ['email' => $email, 'password_hash' => $hash, 'active' => 1] + $extra);
        echo "Conta criada: {$email} ({$porta}).\n";
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Falhou: ' . $e->getMessage() . "\n");
    exit(1);
}
