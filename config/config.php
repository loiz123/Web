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
    'debug'        => true,    // tắt khi deploy
    'upload_dir'   => __DIR__ . '/../public/uploads/properties',
    'image_storage' => 'local',
    'cloudinary' => ['cloud_name' => '', 'api_key' => '', 'api_secret' => ''],
    // Google Maps Platform API Key (Để trống khi không sử dụng - Hệ thống web hiện tại chuyển hoàn toàn sang Leaflet OpenStreetMap Web API miễn phí, mã nguồn mở, không cần thẻ tín dụng)
    'google_maps_api_key' => '',
    'vietqr' => [
        'bank_id'      => 'MB',
        'account_no'   => '0902910780',
        'account_name' => 'HA NGOC THIEN',
        'template'     => 'compact2',
    ],
    'smtp' => [
        'enabled'   => false, // Mặc định false nếu chưa điền tài khoản, bật true khi điền
        'host'      => 'smtp.gmail.com',
        'port'      => 587,
        'username'  => '',
        'password'  => '', // App Password 16 ký tự của Gmail
        'from_name' => 'Bất Động Sản',
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

// Cấu hình hosting qua biến môi trường; không lưu thông tin kết nối trong mã nguồn.
foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_NAME' => 'name', 'DB_USER' => 'user', 'DB_PASSWORD' => 'pass', 'DB_SSL_CA' => 'ssl_ca'] as $env => $key) {
    $value = getenv($env);
    if ($value !== false) $config['db'][$key] = $value;
}
if (($value = getenv('APP_DEBUG')) !== false) $config['debug'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
if (($value = getenv('APP_BASE_URL')) !== false) $config['base_url'] = $value;
if (($value = getenv('IMAGE_STORAGE')) !== false) $config['image_storage'] = $value;
foreach (['CLOUDINARY_CLOUD_NAME' => 'cloud_name', 'CLOUDINARY_API_KEY' => 'api_key', 'CLOUDINARY_API_SECRET' => 'api_secret'] as $env => $key) {
    if (($value = getenv($env)) !== false) $config['cloudinary'][$key] = $value;
}
if (($value = getenv('SMTP_ENABLED')) !== false) $config['smtp']['enabled'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
if (($value = getenv('SMTP_HOST')) !== false) $config['smtp']['host'] = $value;
if (($value = getenv('SMTP_PORT')) !== false) $config['smtp']['port'] = (int)$value;
if (($value = getenv('SMTP_USER')) !== false) $config['smtp']['username'] = $value;
if (($value = getenv('SMTP_PASS')) !== false) $config['smtp']['password'] = $value;
if (($value = getenv('SMTP_FROM_NAME')) !== false) $config['smtp']['from_name'] = $value;
return $config;
