<?php
// Front controller: mọi request đi qua đây.
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

session_start();
$GLOBALS['config'] = require ROOT . '/config/config.php';

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
