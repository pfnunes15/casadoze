<?php
declare(strict_types=1);

/**
 * Copy the CMS's own public assets into this site's public/ folder.
 *
 * The backoffice stylesheet, its script and the rich-text editor live in the
 * package, but a browser can only fetch what is under the document root — and
 * vendor/ deliberately is not. So they are copied here, and copied again after
 * every update of the package.
 *
 * Only ever writes files the package owns. A site file with the same name is
 * overwritten, which is the point: to override a CMS asset, give it a different
 * name and reference that instead.
 *
 *   php bin/cms-publish.php          copy what has changed
 *   php bin/cms-publish.php --force  copy everything again
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script runs from the command line only.\n");
    exit(1);
}

$root    = dirname(__DIR__);
$package = $root . '/vendor/admedia/cms/public/assets';
$target  = $root . '/public/assets';

if (!is_dir($package)) {
    fwrite(STDERR, "The CMS package is not installed — run composer install first.\n");
    exit(1);
}

$force   = in_array('--force', $argv, true);
$copied  = 0;
$skipped = 0;

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($package, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    $relative    = substr($file->getPathname(), strlen($package) + 1);
    $destination = $target . '/' . $relative;

    if ($file->isDir()) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        continue;
    }

    // Unchanged files are left alone, so the timestamp the cache-buster reads
    // does not move and browsers keep what they already have.
    if (!$force && is_file($destination) && filesize($destination) === $file->getSize()
        && filemtime($destination) >= $file->getMTime()) {
        $skipped++;
        continue;
    }

    if (!is_dir(dirname($destination))) {
        mkdir(dirname($destination), 0755, true);
    }
    copy($file->getPathname(), $destination);
    $copied++;
}

printf("%d ficheiro(s) copiado(s), %d já estavam actualizados.\n", $copied, $skipped);
