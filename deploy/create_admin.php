<?php
declare(strict_types=1);

// Chỉ chạy một lần từ CLI sau khi đã import database trống.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$email = strtolower(trim((string)getenv('ADMIN_EMAIL')));
$password = (string)getenv('ADMIN_PASSWORD');
$name = trim((string)(getenv('ADMIN_NAME') ?: 'Quản trị viên'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || strlen($password) < 12 || strlen($password) > 72 || $name === '' || strlen($name) > 100) {
    fwrite(STDERR, "Cần ADMIN_EMAIL hợp lệ, ADMIN_PASSWORD dài 12–72 ký tự và ADMIN_NAME tối đa 100 byte.\n");
    exit(1);
}
$root = dirname(__DIR__);
$GLOBALS['config'] = require $root . '/config/config.php';
require $root . '/app/helpers/functions.php';
require $root . '/app/core/Database.php';
require $root . '/app/core/Model.php';
require $root . '/app/models/User.php';

try {
    $users = new User();
    if ($users->findByEmail($email)) {
        fwrite(STDERR, "Email đã tồn tại; không thay đổi tài khoản hiện có.\n");
        exit(1);
    }
    if ($users->register($name, $email, '', $password, 'admin') <= 0) throw new RuntimeException('Không tạo được tài khoản.');
    echo "Đã tạo tài khoản quản trị.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Không thể tạo tài khoản quản trị; kiểm tra kết nối và quyền database.\n");
    exit(1);
}
