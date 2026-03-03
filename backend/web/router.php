<?php
/**
 * Router script para PHP built-in server.
 * Uso: php8.2 -S 192.168.1.82:8095 -t backend/web backend/web/router.php
 *
 * Permite pretty URLs sem .htaccess (que o built-in server não suporta).
 */

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $url;

// Se o arquivo existe fisicamente (CSS, JS, imagens, etc), servir direto
if (is_file($file)) {
    return false;
}

// Tudo o mais vai para o index.php (Yii2 cuida do roteamento)
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
