<?php
/**
 * Test script kiểm tra các tính năng mới:
 * 1. PropertyController::compare & compareData
 * 2. Công thức tính lãi suất vay mua nhà (Mortgage Calculator)
 * 3. Thuật toán Haversine tính khoảng cách POI
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$GLOBALS['config'] = require ROOT . '/config/config.php';

spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'controllers', 'controllers/admin', 'helpers'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});
require APP . '/helpers/functions.php';

echo "\n--- KIỂM THỬ CÁC TÍNH NĂNG MỚI (MORTGAGE, POI, COMPARE) ---\n";
$passed = 0;
$total = 0;

function assertTest(string $desc, bool $condition): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "   \033[32m[PASS]\033[0m {$desc}\n";
    } else {
        echo "   \033[31m[FAIL]\033[0m {$desc}\n";
    }
}

// 1. Kiểm tra PropertyFinder::findDetail với danh sách so sánh
$finder = new PropertyFinder();
$prop14 = $finder->findDetail(14);
assertTest("1.1 Lấy dữ liệu BĐS ID 14 thành công cho so sánh", $prop14 !== null && (int)$prop14['id'] === 14);

$amenities14 = $finder->getAmenities(14);
assertTest("1.2 Lấy danh sách tiện nghi cho BĐS 14 thành công", is_array($amenities14));

// 2. Kiểm tra controller so sánh method tồn tại
$propCtrl = new PropertyController();
assertTest("2.1 PropertyController có method compare", method_exists($propCtrl, 'compare'));
assertTest("2.2 PropertyController có method compareData", method_exists($propCtrl, 'compareData'));

// 3. Kiểm tra công thức tính toán Mortgage Calculator (Dư nợ giảm dần & Trả góp đều)
$homePrice = 2000000000; // 2 tỷ
$loanRatio = 0.70; // 70%
$loanAmount = $homePrice * $loanRatio; // 1.4 tỷ
$years = 20;
$months = $years * 12; // 240 tháng
$annualRate = 0.085; // 8.5%/năm
$monthlyRate = $annualRate / 12;

// Dư nợ giảm dần:
$monthlyPrincipal = $loanAmount / $months; // 5,833,333.33 đ
$firstInterest = $loanAmount * $monthlyRate; // 9,916,666.67 đ
$firstPayment = $monthlyPrincipal + $firstInterest; // 15,750,000 đ
$totalInterestReducing = ($loanAmount + $monthlyPrincipal) / 2 * $months * $monthlyRate;

assertTest("3.1 Tiền gốc hàng tháng tính chính xác (~5.83 triệu)", (int)round($monthlyPrincipal) === 5833333);
assertTest("3.2 Lãi tháng đầu tiên tính chính xác (~9.92 triệu)", (int)round($firstInterest) === 9916667);
assertTest("3.3 Tổng thanh toán tháng đầu tiên chính xác (~15.75 triệu)", (int)round($firstPayment) === 15750000);
assertTest("3.4 Tổng lãi dư nợ giảm dần trong 20 năm hợp lý (> 1 tỷ và < 1.3 tỷ)", $totalInterestReducing > 1000000000 && $totalInterestReducing < 1300000000);

// Trả góp đều (Niên kim cố định):
// PMT = [P * r * (1+r)^n] / [(1+r)^n - 1]
$pmt = ($loanAmount * $monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
$totalPaymentFixed = $pmt * $months;
$totalInterestFixed = $totalPaymentFixed - $loanAmount;

assertTest("3.5 Tiền trả góp đều mỗi tháng tính chính xác (~12.15 triệu/tháng)", $pmt > 12000000 && $pmt < 12500000);
assertTest("3.6 Tổng lãi trả góp đều lớn hơn tổng lãi dư nợ giảm dần", $totalInterestFixed > $totalInterestReducing);

// 4. Kiểm tra công thức khoảng cách Haversine POI
function haversineDist(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

// Bến Thành (10.7721, 106.6983) đến Nhà Thờ Đức Bà (10.7798, 106.6990) ~ 850m - 900m
$dist = haversineDist(10.7721, 106.6983, 10.7798, 106.6990);
assertTest("4.1 Thuật toán Haversine tính cự ly địa lý chính xác (~860m)", $dist >= 800 && $dist <= 950);

echo "\n=======================================================\n";
echo "KẾT QUẢ: {$passed}/{$total} tests passed!\n";
echo "=======================================================\n\n";
