<?php
/**
 * Router script para o PHP built-in server.
 * Uso: php -S 192.168.1.82:8095 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Tenta servir arquivos estáticos de backend/web (assets, css, js, imagens, etc.)
$backendWebFile = __DIR__ . '/backend/web' . $uri;
if ($uri !== '/' && is_file($backendWebFile)) {
    // Define o content-type correto para arquivos estáticos
    $ext = pathinfo($backendWebFile, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
        'ico'  => 'image/x-icon',
        'json' => 'application/json',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($backendWebFile);
    return true;
}

// Para tudo mais, passa para o index.php (Yii2)
require __DIR__ . '/index.php';
