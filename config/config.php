<?php
// Cấu hình mặc định. Mỗi thành viên/hosting tạo config/config.local.php để ghi đè (file này không commit).
$config = [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'batdongsan',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'base_url'     => '',      // để trống = tự nhận; hosting có thư mục con thì điền, vd '/batdongsan/public'
    'app_name'     => 'BĐS Việt',
    'use_routines' => true,    // false nếu hosting không cho tạo stored procedure
    'debug'        => true,    // tắt khi deploy
    'upload_dir'   => __DIR__ . '/../public/uploads/properties',
    'google_maps_api_key' => '', // Lưu ý: key cá nhân điền trong config.local.php, không commit lên GitHub
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
return $config;
