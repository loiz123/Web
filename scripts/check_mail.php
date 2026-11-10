<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT . '/app/helpers/functions.php';
require ROOT . '/app/helpers/Mailer.php';
$GLOBALS['config'] = require ROOT . '/config/config.php';
$mailer = new Mailer();
if (!$mailer->isEnabled()) {
    fwrite(STDERR, "Chưa đủ cấu hình gửi mail. Kiểm tra MAIL_TRANSPORT, RESEND_API_KEY và MAIL_FROM_EMAIL; hoặc cấu hình SMTP.\n");
    exit(1);
}
echo "Đã có cấu hình gửi mail. Không hiển thị API key hoặc mật khẩu.\n";
$options = getopt('', ['to:']);
if (!isset($options['to'])) {
    echo "Chưa gửi thư. Để thử: php scripts/check_mail.php --to=email-cua-ban@example.com\n";
    exit(0);
}
$recipient = (string)$options['to'];
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email nhận không hợp lệ.\n");
    exit(1);
}
if (!$mailer->send($recipient, '[BĐS Việt] Kiểm tra gửi email', '<p>Đây là email kiểm tra cấu hình gửi thư của BĐS Việt.</p>')) {
    fwrite(STDERR, $mailer->getLastError() . "\n");
    exit(1);
}
echo "Nhà cung cấp đã tiếp nhận thư. Hãy kiểm tra hộp thư đến và thư rác.\n";
