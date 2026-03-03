<?php
// Serve arquivos estáticos de backend/web quando usar php -S
if (PHP_SAPI === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $staticFile = __DIR__ . '/backend/web' . $uri;
    if ($uri !== '/' && is_file($staticFile)) {
        $mimeTypes = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject', 'json' => 'application/json',
            'map' => 'application/json',
        ];
        $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($staticFile);
        return true;
    }
}

// Aponta para o backend
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/backend/config/main.php',
    require __DIR__ . '/backend/config/main-local.php',
    [
        // Corrige os caminhos pois o entry script está na raiz e não em backend/web
        'components' => [
            'assetManager' => [
                'basePath' => __DIR__ . '/backend/web/assets',
                'baseUrl' => '/assets',
            ],
            'request' => [
                'scriptFile' => __DIR__ . '/index.php',
                'scriptUrl' => '/index.php',
            ],
        ],
    ]
);

$app = new yii\web\Application($config);
Yii::setAlias('@webroot', __DIR__ . '/backend/web');
$app->run();
