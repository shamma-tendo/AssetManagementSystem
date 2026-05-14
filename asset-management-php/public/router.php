<?php

declare(strict_types=1);

/**
 * PHP built-in server router: php -S 127.0.0.1:8080 -t public public/router.php
 */
if (PHP_SAPI !== 'cli-server') {
    http_response_code(403);
    echo 'Use the built-in dev server or point the web root at public/.';
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;
if ($path !== '/' && $path !== '/index.php' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
