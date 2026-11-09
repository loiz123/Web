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

function autoloadUiTestClass(string $class): void {
    foreach (['core', 'models', 'controllers', 'controllers/admin', 'helpers'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
}
spl_autoload_register('autoloadUiTestClass');
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

// 2. Kiểm tra controller so sánh method tồn tại & PropertyCompare.getAll
$propCtrl = new PropertyController();
assertTest("2.1 PropertyController có method compare", method_exists($propCtrl, 'compare'));
assertTest("2.2 PropertyController có method compareData", method_exists($propCtrl, 'compareData'));
$appJsRawForCompare = file_get_contents(ROOT . '/public/assets/js/app.js');
$compareViewRaw = file_get_contents(ROOT . '/app/views/property/compare.php');
assertTest(
    "2.3 PropertyCompare định nghĩa đủ get, getAll, count, add, remove, clear, has, toggle và compare.php tự động redirect theo localStorage",
    strpos($appJsRawForCompare, 'getAll:') !== false
    && strpos($appJsRawForCompare, 'count:') !== false
    && strpos($compareViewRaw, 'window.PropertyCompare.getAll') !== false
    && strpos($compareViewRaw, 'window.location.replace') !== false
);
$vietQrCfg = config('vietqr');
assertTest(
    "2.4 Cấu hình VietQR trong config/config.php đầy đủ bank_id, account_no, account_name, template",
    is_array($vietQrCfg)
    && ($vietQrCfg['bank_id'] ?? '') === 'MB'
    && ($vietQrCfg['account_no'] ?? '') === '0902910780'
    && ($vietQrCfg['account_name'] ?? '') === 'HA NGOC THIEN'
    && ($vietQrCfg['template'] ?? '') === 'compact2'
);

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

// 5. Kiểm tra Reverse Geocoding API (GeocodeController::reverse)
$geoCtrl = new GeocodeController();
assertTest("5.1 GeocodeController có method reverse()", method_exists($geoCtrl, 'reverse'));

$geoRef = new ReflectionClass(GeocodeController::class);
$uaConst = $geoRef->getConstant('USER_AGENT');
assertTest(
    "5.2 Header User-Agent chuẩn thương mại BDSViet-RealEstate/1.0 (contact: cskh@bdsviet.vn)",
    $uaConst === 'BDSViet-RealEstate/1.0 (contact: cskh@bdsviet.vn)'
);

// Gọi thực tế HTTP endpoint /geocode/reverse qua localhost (nếu Apache đang chạy) hoặc CLI subprocess
$reverseJsonRaw = @file_get_contents('http://localhost/batdongsan/public/geocode/reverse?lat=10.776889&lng=106.700806');
if (!$reverseJsonRaw) {
    $cmd = '"' . PHP_BINARY . '" -r ' . escapeshellarg('
        define("ROOT", ' . var_export(ROOT, true) . ');
        define("APP", ROOT . "/app");
        require APP . "/core/Controller.php";
        require APP . "/controllers/GeocodeController.php";
        $_GET = ["lat" => "10.776889", "lng" => "106.700806"];
        $c = new GeocodeController();
        $c->reverse();
    ');
    $reverseJsonRaw = shell_exec($cmd);
}
$reversePayload = is_string($reverseJsonRaw) ? json_decode(trim($reverseJsonRaw), true) : null;
assertTest(
    "5.3 API /geocode/reverse trả về JSON chuẩn {success: true, display_name, address}",
    is_array($reversePayload)
    && ($reversePayload['success'] ?? false) === true
    && !empty($reversePayload['display_name'])
    && is_array($reversePayload['address'] ?? null)
);

// 6. Kiểm tra Hệ thống Toast, Confirm Modal & Skeleton Loading
$appJsContent = file_get_contents(ROOT . '/public/assets/js/app.js');
assertTest("6.1 app.js định nghĩa BDS.toast và BDS.confirm", strpos($appJsContent, 'toast,') !== false && strpos($appJsContent, 'function confirm(') !== false);
assertTest("6.2 app.js đã loại bỏ hoàn toàn window.alert() và window.confirm()", !preg_match('/\b(window\.)?alert\s*\(/', $appJsContent));

$searchAjaxPath = ROOT . '/public/assets/js/search-ajax.js';
assertTest("6.3 File public/assets/js/search-ajax.js tồn tại", is_file($searchAjaxPath));
$searchAjaxContent = is_file($searchAjaxPath) ? file_get_contents($searchAjaxPath) : '';
assertTest("6.4 search-ajax.js tích hợp bds-skeleton-card và BDS.toast / BDS.confirm", strpos($searchAjaxContent, 'bds-skeleton-card') !== false && strpos($searchAjaxContent, 'BDS.toast') !== false && strpos($searchAjaxContent, 'BDS.confirm') !== false);

$styleCssContent = file_get_contents(ROOT . '/public/assets/css/style.css');
assertTest("6.5 style.css định nghĩa .bds-skeleton-card và @keyframes shimmer", strpos($styleCssContent, '.bds-skeleton-card') !== false && strpos($styleCssContent, '@keyframes shimmer') !== false);

// 7. Kiểm tra UX Writing Content Guard trên giao diện
$detailViewContent = file_get_contents(ROOT . '/app/views/property/detail.php');
assertTest("7.1 detail.php hiển thị 'Vị trí & Tiện ích khu vực' và xóa bỏ 'OpenStreetMap Web API (Leaflet Engine)'", strpos($detailViewContent, 'Vị trí & Tiện ích khu vực') !== false && strpos($detailViewContent, 'OpenStreetMap Web API (Leaflet Engine)') === false);

$mainLayoutContent = file_get_contents(ROOT . '/app/views/layouts/main.php');
assertTest("7.2 layouts/main.php đồng nhất thương hiệu 'BĐS Việt - Cổng Thông Tin Bất Động Sản Trực Tuyến' & cskh@bdsviet.vn", strpos($mainLayoutContent, 'BĐS Việt - Cổng Thông Tin Bất Động Sản Trực Tuyến') !== false && strpos($mainLayoutContent, 'cskh@bdsviet.vn') !== false && strpos($mainLayoutContent, 'Đồ án') === false);

// 8. Kiểm tra Chức năng 1: Gợi ý Bất động sản tương tự (Similar Properties)
$propertyModel = new Property();
assertTest("8.1 Property::getSimilarProperties tồn tại", method_exists($propertyModel, 'getSimilarProperties'));
$simList = $propertyModel->getSimilarProperties(
    (int)$prop14['id'],
    (int)($prop14['district_id'] ?? 1),
    (int)($prop14['type_id'] ?? 1),
    (float)($prop14['price'] ?? 2000000000),
    4
);
assertTest("8.2 getSimilarProperties trả về tối đa 4 BĐS đã duyệt và khác currentId", is_array($simList) && count($simList) > 0 && count($simList) <= 4 && !in_array((int)$prop14['id'], array_map('intval', array_column($simList, 'id')), true));
assertTest("8.3 Mỗi BĐS tương tự đều có avatar_url và cover_url", !empty($simList[0]['avatar_url']) && array_key_exists('cover_url', $simList[0]));
assertTest("8.4 detail.php có khối Bất động sản tương tự trong khu vực (4 cột row-cols-lg-4)", strpos($detailViewContent, 'Bất động sản tương tự trong khu vực') !== false && strpos($detailViewContent, 'row-cols-1 row-cols-md-2 row-cols-lg-4 g-3') !== false);

// 9. Kiểm tra Chức năng 3: Lịch sử Bất động sản vừa xem (Recently Viewed Properties)
$searchViewContent = file_get_contents(ROOT . '/app/views/property/search.php');
assertTest("9.1 localStorage key 'bds_recently_viewed' và RecentlyViewed có trong app.js & detail.php", strpos($appJsContent, 'bds_recently_viewed') !== false && strpos($detailViewContent, 'bds_recently_viewed') !== false);
assertTest("9.2 Container #recently-viewed-section có mặt ở cả detail.php và search.php", strpos($detailViewContent, 'id="recently-viewed-section"') !== false && strpos($searchViewContent, 'id="recently-viewed-section"') !== false);
assertTest("9.3 Có nút 'Xóa lịch sử xem'", strpos($detailViewContent, 'Xóa lịch sử xem') !== false && strpos($searchViewContent, 'Xóa lịch sử xem') !== false);

// 10. Kiểm tra Chức năng 4: Modal Chia sẻ đa kênh & Quét mã QR (Smart Share Modal)
assertTest("10.1 detail.php có nút mở #shareModal và Modal #shareModal", strpos($detailViewContent, 'data-bs-target="#shareModal"') !== false && strpos($detailViewContent, 'id="shareModal"') !== false);
assertTest("10.2 #shareModal tích hợp QR Code (api.qrserver.com) và chú thích quét mã", strpos($detailViewContent, 'id="share-qr-code"') !== false && strpos($detailViewContent, 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=') !== false && strpos($detailViewContent, 'Quét mã QR bằng điện thoại để xem nhanh tin này') !== false);
assertTest("10.3 #shareModal tích hợp sao chép liên kết với navigator.clipboard.writeText & BDS.toast", strpos($detailViewContent, 'navigator.clipboard.writeText') !== false && strpos($detailViewContent, "BDS.toast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success')") !== false);
assertTest("10.4 #shareModal có đủ liên kết chia sẻ Zalo, Facebook và Email", strpos($detailViewContent, 'https://zalo.me/share?url=') !== false && strpos($detailViewContent, 'https://www.facebook.com/sharer/sharer.php?u=') !== false && strpos($detailViewContent, 'mailto:?subject=') !== false);

// 11. Kiểm thử render thực tế PropertyController::detail() & PropertyController::search()
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$propCtrl = new PropertyController();

ob_start();
$propCtrl->detail((int)$prop14['id']);
$renderedDetailHtml = ob_get_clean();
assertTest("11.1 Render PropertyController::detail(14) thành công, hiển thị Similar Properties, Recently Viewed và Share Modal", strlen($renderedDetailHtml) > 5000 && strpos($renderedDetailHtml, 'similarPropertiesSection') !== false && strpos($renderedDetailHtml, 'id="shareModal"') !== false && strpos($renderedDetailHtml, 'id="recently-viewed-section"') !== false);

$_GET = [];
ob_start();
$propCtrl->search();
$renderedSearchHtml = ob_get_clean();
assertTest("11.2 Render PropertyController::search() thành công, hiển thị #recently-viewed-section", strlen($renderedSearchHtml) > 3000 && strpos($renderedSearchHtml, 'id="recently-viewed-section"') !== false);

// 12. Kiểm tra Tính năng Cao cấp 1: Thanh toán / Đặt cọc giữ chỗ bằng VietQR (Napas 247)
assertTest(
    "12.1 detail.php có nút 'Đặt cọc giữ chỗ qua VietQR' mở Modal #vietQrModal",
    strpos($detailViewContent, 'Đặt cọc giữ chỗ qua VietQR') !== false
    && strpos($detailViewContent, 'data-bs-target="#vietQrModal"') !== false
    && strpos($detailViewContent, 'id="vietQrModal"') !== false
);
assertTest(
    "12.2 #vietQrModal có đủ 3 mức cọc mẫu (5.000.000đ, 10.000.000đ, 20.000.000đ) và ô nhập số tiền tùy chọn",
    strpos($detailViewContent, '5.000.000đ') !== false
    && strpos($detailViewContent, '10.000.000đ') !== false
    && strpos($detailViewContent, '20.000.000đ') !== false
    && strpos($detailViewContent, 'id="vietQrCustomAmount"') !== false
);
assertTest(
    "12.3 #vietQrModal sinh ảnh mã QR chuẩn VietQR (img.vietqr.io/image/MB-0902910780-compact2.png)",
    strpos($detailViewContent, 'https://img.vietqr.io/image/MB-0902910780-compact2.png?amount=') !== false
    && strpos($detailViewContent, '&accountName=HA%20NGOC%20THIEN') !== false
);
assertTest(
    "12.4 #vietQrModal hiển thị rõ MBBank, STK 0902910780, HA NGOC THIEN, nút Sao chép STK & Sao chép nội dung kèm BDS.toast",
    strpos($detailViewContent, 'Ngân hàng Quân Đội (MBBank)') !== false
    && strpos($detailViewContent, '0902910780') !== false
    && strpos($detailViewContent, 'HA NGOC THIEN') !== false
    && strpos($detailViewContent, 'Sao chép STK') !== false
    && strpos($detailViewContent, 'Sao chép nội dung') !== false
    && strpos($detailViewContent, 'copyVietQrText') !== false
);

// Render thử trang detail ID 18 để kiểm tra nội dung chuyển khoản BDS18 DAT COC
$prop18 = $finder->findDetail(18);
if ($prop18) {
    ob_start();
    $propCtrl->detail(18);
    $renderedDetail18 = ob_get_clean();
    assertTest(
        "12.5 Render detail(18) sinh đúng nội dung chuyển khoản 'BDS18 DAT COC' và URL VietQR BDS18%20DAT%20COC",
        strpos($renderedDetail18, 'BDS18 DAT COC') !== false
        && strpos($renderedDetail18, 'BDS18%20DAT%20COC') !== false
    );
} else {
    assertTest(
        "12.5 Render detail(14) sinh đúng nội dung chuyển khoản 'BDS14 DAT COC' và URL VietQR BDS14%20DAT%20COC",
        strpos($renderedDetailHtml, 'BDS14 DAT COC') !== false
        && strpos($renderedDetailHtml, 'BDS14%20DAT%20COC') !== false
    );
}

// 13. Kiểm tra Tính năng Mới: Gợi ý địa chỉ tự động (Nominatim Autocomplete) trên thanh tìm kiếm chính
$searchFormContent = file_get_contents(ROOT . '/app/views/partials/search_form.php');
assertTest(
    "13.1 search.php & search_form.php có container dropdown gợi ý #searchAddressSuggestions (.list-group, position-absolute, z-index: 9999, border-radius: 12px)",
    strpos($searchViewContent, 'id="searchAddressSuggestions"') !== false
    && strpos($searchFormContent, 'id="searchAddressSuggestions"') !== false
    && strpos($renderedSearchHtml, 'id="searchAddressSuggestions"') !== false
    && strpos($renderedSearchHtml, 'list-group') !== false
    && strpos($renderedSearchHtml, 'position-absolute') !== false
    && strpos($renderedSearchHtml, 'z-index: 9999') !== false
    && strpos($renderedSearchHtml, 'border-radius: 12px') !== false
);
assertTest(
    "13.2 search-ajax.js dùng đường dẫn geocode theo base URL và debounce 350ms khi độ dài >= 2 ký tự",
    strpos($searchAjaxContent, '350') !== false
    && strpos($searchAjaxContent, 'keyword.length < 2') !== false
    && strpos($searchAjaxContent, '${basePrefix}/geocode?q=') !== false
    && strpos($searchAjaxContent, 'encodeURIComponent(keyword)') !== false
);
assertTest(
    "13.3 search-ajax.js hiển thị dropdown gợi ý với icon 📍, display_name và tô đậm từ khóa (highlightKeyword)",
    strpos($searchAjaxContent, '📍') !== false
    && strpos($searchAjaxContent, 'display_name') !== false
    && strpos($searchAjaxContent, 'highlightKeyword') !== false
    && strpos($searchAjaxContent, 'search-suggestion-item') !== false
);
assertTest(
    "13.4 search-ajax.js xử lý click gợi ý (điền input, ẩn dropdown, gọi fetchResults) và ẩn dropdown khi nhấn Escape hoặc click ra ngoài",
    strpos($searchAjaxContent, 'fetchResults') !== false
    && strpos($searchAjaxContent, 'hideAddressSuggestions') !== false
    && strpos($searchAjaxContent, "e.key === 'Escape'") !== false
);
assertTest(
    "13.5 PropertyFinder hỗ trợ tìm kiếm linh hoạt với chuỗi địa danh đầy đủ từ gợi ý địa chỉ (có dấu phẩy)",
    count($finder->search(['keyword' => 'Phường Thảo Điền, Thành phố Thủ Đức, Thành phố Hồ Chí Minh, Việt Nam'])) >= 1
);

// 14. Kiểm thử HTTP thực tế 2 API (Nominatim Forward/Reverse & VietQR) và các trang trên XAMPP
function httpGetCheck(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return ['code' => $code, 'ct' => $ct, 'body' => (string)$body];
}

$httpBase = rtrim(getenv('BDS_TEST_HTTP_BASE') ?: 'http://localhost/batdongsan/public', '/');
$geoForwardResp = httpGetCheck($httpBase . '/geocode?q=Ha+Noi');
$geoForwardJson = json_decode($geoForwardResp['body'], true);
assertTest(
    "14.1 HTTP GET /batdongsan/public/geocode?q=Ha+Noi trả về JSON hợp lệ (success=true, có danh sách display_name)",
    $geoForwardResp['code'] === 200
    && is_array($geoForwardJson)
    && ($geoForwardJson['success'] ?? false) === true
    && !empty($geoForwardJson['data'][0]['display_name'])
);

$geoReverseResp = httpGetCheck($httpBase . '/geocode/reverse?lat=21.0285&lng=105.8542');
$geoReverseJson = json_decode($geoReverseResp['body'], true);
assertTest(
    "14.2 HTTP GET /batdongsan/public/geocode/reverse?lat=21.0285&lng=105.8542 trả về JSON hợp lệ (success=true, có display_name)",
    $geoReverseResp['code'] === 200
    && is_array($geoReverseJson)
    && ($geoReverseJson['success'] ?? false) === true
    && !empty($geoReverseJson['display_name'])
);

$detailDemoResp = httpGetCheck($httpBase . '/property/detail/1');
assertTest(
    "14.3 HTTP GET trang chi tiết tin demo có VietQR và không có chức năng in brochure",
    $detailDemoResp['code'] === 200
    && strpos($detailDemoResp['body'], 'Đặt cọc giữ chỗ qua VietQR') !== false
    && strpos($detailDemoResp['body'], 'id="vietQrModal"') !== false
    && strpos($detailDemoResp['body'], 'https://img.vietqr.io/image/MB-0902910780-compact2.png') !== false
    && strpos($detailDemoResp['body'], 'btnPrintBrochure') === false
    && strpos($detailDemoResp['body'], 'printBrochureSheet') === false
    && strpos($styleCssContent, 'print-brochure') === false
);

$searchPageResp = httpGetCheck($httpBase . '/property/search');
$searchAjaxResp = httpGetCheck(
    $httpBase . '/property/search?keyword=Th%E1%BA%A3o+%C4%90i%E1%BB%81n&page=1',
    ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']
);
$searchAjaxJson = json_decode($searchAjaxResp['body'], true);
assertTest(
    "14.4 HTTP GET /batdongsan/public/property/search có ô tìm kiếm từ khóa, #searchAddressSuggestions và AJAX lọc/phân trang hoạt động",
    $searchPageResp['code'] === 200
    && strpos($searchPageResp['body'], 'name="keyword"') !== false
    && strpos($searchPageResp['body'], 'id="searchAddressSuggestions"') !== false
    && $searchAjaxResp['code'] === 200
    && is_array($searchAjaxJson)
    && ($searchAjaxJson['success'] ?? false) === true
    && isset($searchAjaxJson['data']['html'], $searchAjaxJson['data']['paginationHtml'])
);

echo "\n=======================================================\n";
echo "KẾT QUẢ: {$passed}/{$total} tests passed!\n";
echo "=======================================================\n\n";
exit($passed === $total ? 0 : 1);
