<?php
// Copy to config/config.php and fill in the real values.
// config/config.php is gitignored and must never be committed.

return [
    'app' => [
        'name'     => "Casa de Zé",
        'url'      => 'https://example.com',   // no trailing slash
        'env'      => 'production',            // 'development' turns on full error reporting
        'debug'    => false,                   // must stay false when env is 'production'
        'timezone' => 'Europe/Lisbon',
        // The language the site is installed in. It is where the settings that
        // are not text are stored — which languages there are, which is the main
        // one — and from then on what rules is Settings > Languages, in the
        // backoffice. See config/locales.php and Admedia\Core\Locales.
        'locale'   => 'pt',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'casadoze',
        'user'    => 'CHANGE_ME',
        'pass'    => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name'      => 'casadoze_session',
        'lifetime'  => 7200,      // 2 hours
        'secure'    => true,      // HTTPS only; set false only for local HTTP
        'httponly'  => true,
        'samesite'  => 'Lax',
        // Kept out of the shared /tmp, where another account on the same host
        // could read the session files.
        'save_path' => __DIR__ . '/../storage/sessions',
    ],

    'security' => [
        // Long random string. Generate with:
        //   php -r "echo bin2hex(random_bytes(32));"
        'app_key' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',

        // Enables GET /_migrate, the migration runner for hosting with no
        // shell. Leave empty and the endpoint refuses to run. Generate one the
        // same way as app_key, and clear it once the site is live.
        'migration_token' => '',
    ],

    'uploads' => [
        // Public images. Resolved against the document root (CMS_PUBLIC), which
        // on cPanel is not necessarily this project's public/ directory.
        'public_dir'      => (defined('CMS_PUBLIC') ? CMS_PUBLIC : __DIR__ . '/../public') . '/assets/uploads',
        'public_url'      => '/assets/uploads',
        'public_max_size' => 8 * 1024 * 1024,   // 8 MB
        'public_mimes'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    ],

    // Where this site asks whether there is a new CMS. Empty turns the check
    // off. The default is in Admedia\Core\Version::MANIFEST and is a public
    // repository holding only the manifest — deliberately, so that no site needs
    // a read token to learn its own version.
    'cms' => [
        'update_manifest' => 'https://raw.githubusercontent.com/pfnunes15/admedia-cms-releases/main/latest.json',
    ],

    // Transactional e-mail (password reset, account creation).
    // from_email has no default: leave it empty and nothing is sent.
    // With smtp_host empty the server's mail() is used, which shared hosting
    // often disables — check before relying on it.
    'mail' => [
        'from_email'      => '',          // e.g. no-reply@example.com
        'from_name'       => '',          // defaults to app.name
        'smtp_host'       => '',          // empty = use mail()
        'smtp_port'       => 587,         // 587 (STARTTLS) or 465 (SSL)
        'smtp_encryption' => 'tls',       // 'tls' | 'ssl' | ''
        'smtp_user'       => '',
        'smtp_pass'       => '',
        'ehlo'            => '',          // defaults to the host of app.url
    ],
];
