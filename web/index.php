<?php

/**
 * Router web/index.php untuk `ainstruct webui` (php -S -t web web/index.php).
 *
 * Dengan mode router, script ini dipanggil untuk SETIAP request. File statis
 * yang benar-benar ada di bawah docroot dilayani server bawaan PHP lewat
 * `return false` (MIME otomatis benar: application/javascript, text/css,
 * image/svg+xml, dst). Router ini hanya menangani:
 *   - /api/*          → kernel HTTP WebUI (JSON)
 *   - selain itu      → shell SPA (index.html)
 *
 * Autoload di-resolve untuk dua tata letak seperti bin/ainstruct:
 *   - checkout pengembangan repo : web/../vendor/autoload.php
 *   - terpasang sebagai dependency : vendor/lace/ainstruct/web/index.php
 *     → vendor/../../../autoload.php
 */

use Illuminate\Container\Container;
use Lace\Ainstruct\Bootstrap\AppServiceProvider;
use Lace\Ainstruct\Web\Kernel;
use Lace\Ainstruct\Web\Request;

$autoloadCandidates = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
];

$autoloaded = false;

foreach ($autoloadCandidates as $file) {
    if (is_file($file)) {
        require $file;
        $autoloaded = true;
        break;
    }
}

if (! $autoloaded) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Autoloader tidak ditemukan. Jalankan `composer install` lebih dulu.\n";
    exit(1);
}

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

if (str_starts_with($path, '/api/')) {
    $container = new Container;
    (new AppServiceProvider($container))->register();

    $kernel = new Kernel($container);
    $kernel->handle(Request::fromGlobals())->send();
    exit(0);
}

// File statis yang ada di docroot diserahkan ke server bawaan (return false)
// agar MIME-nya benar. Blokir traversal: path dengan ".." tidak pernah
// dianggap file statis; jatuh ke shell SPA.
$clean = str_replace('\\', '/', $path);

if (! str_contains($clean, '..')) {
    $file = __DIR__.$clean;

    if (is_file($file)) {
        return false;
    }
}

$index = __DIR__.'/index.html';

if (is_file($index)) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    readfile($index);
    exit(0);
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Satu halaman WebUI tidak ditemukan (index.html).\n";
exit(1);
