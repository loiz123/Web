<?php
// Copy thành config.local.php rồi sửa theo máy/hosting của bạn.
return [
    'db' => ['host' => 'sqlxxx.epizy.com', 'name' => 'epiz_xxx_batdongsan', 'user' => 'epiz_xxx', 'pass' => '***'],
    'debug' => false,
    // Thêm thông tin thật trong config.local.php (được Git bỏ qua).
    'mail' => [
        'transport' => 'resend',
        'resend_api_key' => '',
        'from_email' => '', // Địa chỉ gửi thuộc domain đã xác minh trong Resend.
        'from_name' => 'BĐS Việt',
    ],
    'google_maps_api_key' => 'YOUR_GOOGLE_MAPS_API_KEY_HERE',
];
