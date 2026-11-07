<?php
// Test kịch bản validation đăng ký tài khoản (User model)
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/User.php';

echo "=== KIỂM THỬ VALIDATION MODEL USER ===" . PHP_EOL;

$passed = 0;
$total = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "[PASS] $name" . ($detail ? " -> $detail" : "") . PHP_EOL;
    } else {
        echo "[FAIL] $name" . ($detail ? " -> $detail" : "") . PHP_EOL;
    }
}

// 1. Họ tên hợp lệ (độ dài 2-100) và SĐT hợp lệ
$err1 = User::validateProfile('Nguyễn Văn A', '0901234567');
assertTest('Họ tên hợp lệ, SĐT 10 số (09...)', empty($err1), empty($err1) ? 'Không lỗi' : implode('; ', $err1));

// 2. Họ tên < 2 ký tự (lỗi biên dưới)
$err2 = User::validateProfile('A', '0901234567');
assertTest('Họ tên quá ngắn (< 2 ký tự)', count($err2) > 0 && str_contains($err2[0], '2 đến 100'), $err2[0] ?? '');

// 3. Họ tên rỗng (0 ký tự)
$err3 = User::validateProfile('', '');
assertTest('Họ tên rỗng', count($err3) > 0, $err3[0] ?? '');

// 4. Họ tên > 100 ký tự (lỗi biên trên)
$longName = str_repeat('A', 101);
$err4 = User::validateProfile($longName, '');
assertTest('Họ tên quá dài (> 100 ký tự)', count($err4) > 0, $err4[0] ?? '');

// 5. Chuẩn hóa và validate SĐT định dạng +84
$rawPhone = ' +84 901.234-567 ';
$normalizedPhone = User::normalizePhone($rawPhone);
assertTest('Chuẩn hóa SĐT (+84 901.234-567 -> +84901234567)', $normalizedPhone === '+84901234567', $normalizedPhone);

$err5 = User::validateProfile('Trần Thị B', $normalizedPhone);
assertTest('Validate SĐT chuẩn hóa (+84901234567)', empty($err5), empty($err5) ? 'Hợp lệ' : implode('; ', $err5));

// 6. SĐT không bắt buộc (chuỗi rỗng)
$err6 = User::validateProfile('Lê Văn C', '');
assertTest('SĐT rỗng (không bắt buộc)', empty($err6), 'Hợp lệ');

// 7. SĐT không hợp lệ (sai định dạng)
$err7 = User::validateProfile('Phạm Văn D', '012345');
assertTest('SĐT không hợp lệ (ít số)', count($err7) > 0, $err7[0] ?? '');

// 8. Mật khẩu hợp lệ và khớp confirm
$err8 = User::validatePassword('Admin@123', 'Admin@123');
assertTest('Mật khẩu hợp lệ và khớp confirm', empty($err8), 'Hợp lệ');

// 9. Mật khẩu quá ngắn (< 6)
$err9 = User::validatePassword('12345', '12345');
assertTest('Mật khẩu quá ngắn (< 6)', count($err9) > 0, $err9[0] ?? '');

// 10. Mật khẩu nhập lại không khớp
$err10 = User::validatePassword('Admin@123', 'Admin@456');
assertTest('Mật khẩu không khớp confirm', count($err10) > 0, $err10[0] ?? '');

// 11. Giả lập toàn bộ luồng AuthController::register()
$inputName = '  Nguyễn   Văn   E  ';
$name = trim(preg_replace('/\s+/u', ' ', $inputName));
$email = strtolower(trim(' user.test@example.com '));
$phone = User::normalizePhone('0912 345 678');
$pass = 'Secret@123';
$confirm = 'Secret@123';

$errors = User::validateProfile($name, $phone);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors[] = 'Email không hợp lệ.';
}
$errors = array_merge($errors, User::validatePassword($pass, $confirm));

assertTest('Toàn bộ luồng AuthController::register với input chuẩn', empty($errors), "Name: $name | Phone: $phone | Email: $email");

echo PHP_EOL . "Kết quả: $passed/$total test PASS." . PHP_EOL;
exit($passed === $total ? 0 : 1);
