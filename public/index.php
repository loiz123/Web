<?php
// Front controller: mọi request đi qua đây.
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
$GLOBALS['config'] = require ROOT . '/config/config.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'controllers', 'controllers/admin', 'helpers'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});
require APP . '/helpers/functions.php';

ini_set('display_errors', config('debug') ? '1' : '0');
Auth::validate();   // đăng xuất ngay nếu tài khoản bị khóa/xóa
(new App())->run();
