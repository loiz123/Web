<?php
// Front controller: mọi request đi qua đây.
declare(strict_types=1);
date_default_timezone_set('Asia/Ho_Chi_Minh');
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
header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');

spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'controllers', 'controllers/admin', 'helpers'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});
require APP . '/helpers/functions.php';

ini_set('display_errors', config('debug') ? '1' : '0');

try {
    Auth::validate();   // đăng xuất ngay nếu tài khoản bị khóa/xóa hoặc đổi mật khẩu ở phiên khác
    (new App())->run();
} catch (Throwable $e) {
    error_log(sprintf('[Uncaught %s] %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (config('debug')) {
        echo '<div style="font-family:system-ui,sans-serif;max-width:960px;margin:2rem auto;padding:1.5rem;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:12px;">';
        echo '<h2 style="margin-top:0;">Lỗi hệ thống (Chế độ Debug)</h2>';
        echo '<p><strong>' . e(get_class($e)) . ':</strong> ' . e($e->getMessage()) . '</p>';
        echo '<p><small>' . e($e->getFile()) . ':' . (int)$e->getLine() . '</small></p>';
        echo '<pre style="background:#fff;padding:1rem;border-radius:8px;overflow:auto;font-size:0.85rem;color:#212529;">' . e($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    } else {
        $title = 'Dịch vụ tạm thời gián đoạn';
        ob_start();
        require APP . '/views/errors/500.php';
        $content = ob_get_clean();
        if (is_file(APP . '/views/layouts/main.php')) {
            require APP . '/views/layouts/main.php';
        } else {
            echo $content;
        }
    }
}
