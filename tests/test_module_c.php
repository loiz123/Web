<?php
/**
 * Test Suite tự động cho Module C (Thiện - Tìm kiếm, Chi tiết, Bản đồ, Yêu thích, Đánh giá)
 * Chạy bằng PHP CLI: php tests/test_module_c.php
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/batdongsan';
$_SERVER['SCRIPT_NAME'] = '/batdongsan/index.php';

$GLOBALS['config'] = require ROOT . '/config/config.php';

$testDatabase = getenv('BDS_TEST_DB');
if ($testDatabase && preg_match('/^batdongsan_test_[a-z0-9_]+$/', $testDatabase)) {
    $GLOBALS['config']['db']['name'] = $testDatabase;
}

function loadModuleCTestClass(string $class): void {
    foreach (['core', 'models', 'controllers', 'controllers/admin', 'helpers'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require_once $file; return; }
    }
}
spl_autoload_register('loadModuleCTestClass');
require_once APP . '/helpers/functions.php';

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $message, string $details = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m[PASS]\033[0m {$message}\n";
        } else {
            $this->failed++;
            $this->failures[] = "{$message} " . ($details ? "({$details})" : '');
            echo "  \033[31m[FAIL]\033[0m {$message}\n";
            if ($details) {
                echo "         Chi tiết: {$details}\n";
            }
        }
    }

    public function report(): void
    {
        echo "\n=======================================================\n";
        echo "KẾT QUẢ KIỂM THỬ: ";
        if ($this->failed === 0) {
            echo "\033[32mTHÀNH CÔNG RỰC RỠ ({$this->passed}/{$this->passed} tests passed)\033[0m\n";
        } else {
            echo "\033[31mCÓ {$this->failed} TEST FAILED (Passed: {$this->passed}, Failed: {$this->failed})\033[0m\n";
            foreach ($this->failures as $f) {
                echo " - {$f}\n";
            }
        }
        echo "=======================================================\n\n";
    }

    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }
}

$t = new TestRunner();
$finder = new PropertyFinder();

echo "\n--- BẮT ĐẦU KIỂM THỬ MODULE C (THIỆN) ---\n\n";

// =========================================================================
// SUITE 1: TÌM KIẾM, BỘ LỌC ĐA TIÊU CHÍ & SẮP XẾP
// =========================================================================
echo "SUITE 1: TÌM KIẾM, BỘ LỌC ĐA TIÊU CHÍ & SẮP XẾP\n";

// 1.1 Tìm kiếm theo từ khóa
$resKw = $finder->search(['keyword' => 'Thảo Điền']);
$t->assert(!empty($resKw), "1.1 Tìm kiếm theo từ khóa 'Thảo Điền' trả về kết quả", "Số lượng: " . count($resKw));

// 1.2 Lọc theo hình thức Mua bán (sale)
$resSale = $finder->search(['listing_type' => 'sale']);
$t->assert(count($resSale) > 0, "1.2 Lọc hình thức 'sale' (bán) thành công", "Tìm thấy: " . count($resSale));
$allSale = true;
foreach ($resSale as $p) { if ($p['listing_type'] !== 'sale') $allSale = false; }
$t->assert($allSale, "1.2.1 Tất cả tin trả về đều là listing_type = 'sale'");

// 1.3 Lọc theo hình thức Cho thuê (rent)
$resRent = $finder->search(['listing_type' => 'rent']);
$t->assert(count($resRent) > 0, "1.3 Lọc hình thức 'rent' (cho thuê) thành công", "Tìm thấy: " . count($resRent));
$allRent = true;
foreach ($resRent as $p) { if ($p['listing_type'] !== 'rent') $allRent = false; }
$t->assert($allRent, "1.3.1 Tất cả tin trả về đều là listing_type = 'rent'");

// 1.4 Lọc theo loại BĐS (type_id)
$resType = $finder->search(['type_id' => 1]); // Căn hộ chung cư
$t->assert(count($resType) > 0, "1.4 Lọc theo loại BĐS type_id=1 (Căn hộ) thành công", "Tìm thấy: " . count($resType));

// 1.5 Lọc theo khoảng diện tích: <30m2
$resArea30 = $finder->search(['area_range' => '0-30']);
$t->assert(count($resArea30) > 0, "1.5 Lọc diện tích < 30m2 thành công", "Tìm thấy: " . count($resArea30));
$check30 = true;
foreach ($resArea30 as $p) { if ((float)$p['area'] >= 30) $check30 = false; }
$t->assert($check30, "1.5.1 Diện tích của tất cả tin đều < 30m²");

// 1.6 Lọc theo khoảng diện tích: 50-80m2
$resArea50_80 = $finder->search(['area_range' => '50-80']);
$t->assert(count($resArea50_80) > 0, "1.6 Lọc diện tích 50-80m2 thành công", "Tìm thấy: " . count($resArea50_80));

// 1.7 Lọc theo khoảng diện tích: >500m2
$resArea500 = $finder->search(['area_range' => '500+']);
$t->assert(count($resArea500) > 0, "1.7 Lọc diện tích > 500m2 thành công", "Tìm thấy: " . count($resArea500));
$check500 = true;
foreach ($resArea500 as $p) { if ((float)$p['area'] < 500) $check500 = false; }
$t->assert($check500, "1.7.1 Diện tích của tất cả tin đều >= 500m²");

// 1.8 Lọc diện tích bằng min_area và max_area tùy chỉnh
$resCustomArea = $finder->search(['min_area' => 70, 'max_area' => 150]);
$t->assert(count($resCustomArea) > 0, "1.8 Lọc diện tích tùy chỉnh 70m2 - 150m2 thành công", "Tìm thấy: " . count($resCustomArea));
$checkCustomArea = true;
foreach ($resCustomArea as $p) {
    if ((float)$p['area'] < 70 || (float)$p['area'] > 150) $checkCustomArea = false;
}
$t->assert($checkCustomArea, "1.8.1 Tất cả tin đều nằm trong khoảng 70m² <= area <= 150m²");

// 1.9 Lọc theo số phòng ngủ: 1 PN
$resBed1 = $finder->search(['bedrooms' => 1]);
$t->assert(count($resBed1) > 0, "1.9 Lọc số phòng ngủ = 1 thành công", "Tìm thấy: " . count($resBed1));

// 1.10 Lọc theo số phòng ngủ: 2 PN
$resBed2 = $finder->search(['bedrooms' => 2]);
$t->assert(count($resBed2) > 0, "1.10 Lọc số phòng ngủ = 2 thành công", "Tìm thấy: " . count($resBed2));

// 1.11 Lọc theo số phòng ngủ: 5+ PN
$resBed5 = $finder->search(['bedrooms' => '5']);
$t->assert(count($resBed5) > 0, "1.11 Lọc số phòng ngủ >= 5 thành công", "Tìm thấy: " . count($resBed5));
$checkBed5 = true;
foreach ($resBed5 as $p) { if ((int)$p['bedrooms'] < 5) $checkBed5 = false; }
$t->assert($checkBed5, "1.11.1 Tất cả tin đều có số phòng ngủ >= 5");

// 1.12 Lọc theo khoảng giá phổ biến: 1b-2b (1-2 tỷ)
$resPrice1_2b = $finder->search(['price_range' => '1b-2b']);
$t->assert(count($resPrice1_2b) > 0, "1.12 Lọc khoảng giá 1 - 2 tỷ thành công", "Tìm thấy: " . count($resPrice1_2b));

// 1.13 Lọc theo min_price & max_price tùy chỉnh
$resCustomPrice = $finder->search(['min_price' => 500000000, 'max_price' => 3000000000]);
$t->assert(count($resCustomPrice) > 0, "1.13 Lọc khoảng giá tùy chỉnh 500tr - 3 tỷ thành công", "Tìm thấy: " . count($resCustomPrice));

// 1.14 Sắp xếp: Giá thấp đến cao (price_asc)
$resPriceAsc = $finder->search(['sort_by' => 'price_asc'], 10, 0);
$checkPriceAsc = true;
for ($i = 0; $i < count($resPriceAsc) - 1; $i++) {
    if ((float)$resPriceAsc[$i]['price'] > (float)$resPriceAsc[$i + 1]['price']) {
        $checkPriceAsc = false;
        break;
    }
}
$t->assert($checkPriceAsc, "1.14 Sắp xếp 'price_asc' (Giá tăng dần) chuẩn xác");

// 1.15 Sắp xếp: Giá cao đến thấp (price_desc)
$resPriceDesc = $finder->search(['sort_by' => 'price_desc'], 10, 0);
$checkPriceDesc = true;
for ($i = 0; $i < count($resPriceDesc) - 1; $i++) {
    if ((float)$resPriceDesc[$i]['price'] < (float)$resPriceDesc[$i + 1]['price']) {
        $checkPriceDesc = false;
        break;
    }
}
$t->assert($checkPriceDesc, "1.15 Sắp xếp 'price_desc' (Giá giảm dần) chuẩn xác");

// 1.16 Sắp xếp: Diện tích bé đến lớn (area_asc)
$resAreaAsc = $finder->search(['sort_by' => 'area_asc'], 10, 0);
$checkAreaAsc = true;
for ($i = 0; $i < count($resAreaAsc) - 1; $i++) {
    if ((float)$resAreaAsc[$i]['area'] > (float)$resAreaAsc[$i + 1]['area']) {
        $checkAreaAsc = false;
        break;
    }
}
$t->assert($checkAreaAsc, "1.16 Sắp xếp 'area_asc' (Diện tích tăng dần) chuẩn xác");

// 1.17 Sắp xếp: Diện tích lớn đến bé (area_desc)
$resAreaDesc = $finder->search(['sort_by' => 'area_desc'], 10, 0);
$checkAreaDesc = true;
for ($i = 0; $i < count($resAreaDesc) - 1; $i++) {
    if ((float)$resAreaDesc[$i]['area'] < (float)$resAreaDesc[$i + 1]['area']) {
        $checkAreaDesc = false;
        break;
    }
}
$t->assert($checkAreaDesc, "1.17 Sắp xếp 'area_desc' (Diện tích giảm dần) chuẩn xác");

// 1.18 Phân trang & Đếm tổng số kết quả
$totalCount = $finder->countSearch([]);
$resPage1 = $finder->search([], 5, 0);
$resPage2 = $finder->search([], 5, 5);
$t->assert($totalCount >= 14, "1.18 Tổng số tin tìm thấy đúng số lượng trong DB", "Total: {$totalCount}");
$t->assert(count($resPage1) === 5 && count($resPage2) === 5, "1.18.1 Phân trang (limit 5) trả về đúng 5 tin mỗi trang");
$t->assert($resPage1[0]['id'] !== $resPage2[0]['id'], "1.18.2 Trang 1 và Trang 2 trả về các tin khác nhau");

// =========================================================================
// SUITE 2: CHI TIẾT TIN BĐS & TỌA ĐỘ BẢN ĐỒ LEAFLET
// =========================================================================
echo "\nSUITE 2: CHI TIẾT TIN BĐS & TỌA ĐỘ BẢN ĐỒ LEAFLET\n";

$prop14 = $finder->findDetail(14);
$t->assert(!empty($prop14), "2.1 Lấy thông tin chi tiết tin ID 14 thành công");
$t->assert(!empty($prop14['latitude']) && !empty($prop14['longitude']), "2.2 Tin có đầy đủ tọa độ kinh độ, vĩ độ", "Lat: {$prop14['latitude']}, Lng: {$prop14['longitude']}");
$t->assert(isset($prop14['province_name']) && isset($prop14['district_name']), "2.3 Đầy đủ tên tỉnh, quận, phường", "{$prop14['district_name']}, {$prop14['province_name']}");

// Kiểm tra Fallback tọa độ khi tin thiếu lat/lng
$fakeNoCoords = [
    'id' => 999,
    'latitude' => null,
    'longitude' => null,
    'district_name' => 'Quận 1',
    'province_name' => 'Hồ Chí Minh'
];
$fallback = $finder->getDistrictFallbackCoords(1, 1);
$t->assert(!empty($fallback['lat']) && !empty($fallback['lng']), "2.4 Fallback tọa độ quận/huyện hoạt động chính xác khi tin thiếu tọa độ", "Fallback Q1: {$fallback['lat']}, {$fallback['lng']}");

// Lượt xem tin
$viewsBefore = (int)$prop14['views'];
$finder->incrementViews(14);
$prop14After = $finder->findDetail(14);
$t->assert((int)$prop14After['views'] === $viewsBefore + 1, "2.5 Tăng lượt xem (views) khi xem chi tiết tin thành công");

// Tiện ích và ảnh
$amenities = $finder->getAmenities(14);
$images = $finder->getImages(14);
$t->assert(is_array($amenities), "2.6 Lấy danh sách tiện ích của tin thành công", "Số tiện ích: " . count($amenities));
$t->assert(is_array($images), "2.7 Lấy danh sách hình ảnh của tin thành công", "Số ảnh: " . count($images));

// Tin tương tự
$similar = $finder->getSimilar(14, (int)$prop14['type_id'], 3);
$t->assert(is_array($similar), "2.8 Lấy danh sách tin tương tự cùng chuyên mục", "Số tin: " . count($similar));

// =========================================================================
// SUITE 3: LƯU & MỞ LẠI BỘ LỌC TÌM KIẾM (TASK 5)
// =========================================================================
echo "\nSUITE 3: LƯU & MỞ LẠI BỘ LỌC TÌM KIẾM\n";

$savedSearchModel = new SavedSearch();
$testUserId = 1; // User có sẵn trong DB
$filterCriteria = [
    'keyword' => 'Chung cư',
    'listing_type' => 'sale',
    'area_range' => '50-80',
    'bedrooms' => '2',
    'price_range' => '2b-3b'
];

$savedId = $savedSearchModel->saveSearch($testUserId, 'Căn hộ 2PN 50-80m2 dưới 3 tỷ', $filterCriteria);
$t->assert($savedId > 0, "3.1 Lưu bộ lọc tìm kiếm vào database thành công", "SavedSearch ID: {$savedId}");

$userFilters = $savedSearchModel->getByUser($testUserId);
$foundSaved = false;
foreach ($userFilters as $uf) {
    if ((int)$uf['id'] === $savedId) {
        $foundSaved = true;
        $t->assert($uf['name'] === 'Căn hộ 2PN 50-80m2 dưới 3 tỷ', "3.2 Kiểm tra tên bộ lọc đã lưu chuẩn xác");
        $t->assert(($uf['filter_data']['bedrooms'] ?? '') === '2', "3.3 Dữ liệu filter_data JSON giải mã chính xác");
        break;
    }
}
$t->assert($foundSaved, "3.4 Tìm thấy bộ lọc vừa lưu trong danh sách của người dùng");

// Xóa bộ lọc đã lưu
$deleted = $savedSearchModel->deleteOwned($savedId, $testUserId);
$t->assert($deleted, "3.5 Xóa bộ lọc đã lưu (deleteOwned) thành công");

$userFiltersAfter = $savedSearchModel->getByUser($testUserId);
$stillExists = false;
foreach ($userFiltersAfter as $uf) {
    if ((int)$uf['id'] === $savedId) $stillExists = true;
}
$t->assert(!$stillExists, "3.6 Xác nhận bộ lọc đã bị xóa khỏi cơ sở dữ liệu");

// =========================================================================
// SUITE 4: TÍNH NĂNG TIN YÊU THÍCH (FAVORITES)
// =========================================================================
echo "\nSUITE 4: TÍNH NĂNG TIN YÊU THÍCH (FAVORITES)\n";

$favModel = new Favorite();
$favPropId = 14;

// Bỏ yêu thích trước nếu đã có
if ($favModel->isFavorited($testUserId, $favPropId)) {
    $favModel->toggle($testUserId, $favPropId);
}

$status1 = $favModel->toggle($testUserId, $favPropId);
$t->assert($status1 === true, "4.1 Toggle yêu thích lần 1: Đã lưu vào yêu thích (liked = true)");
$t->assert($favModel->isFavorited($testUserId, $favPropId) === true, "4.2 Kiểm tra isFavorited trả về true");

$status2 = $favModel->toggle($testUserId, $favPropId);
$t->assert($status2 === false, "4.3 Toggle yêu thích lần 2: Đã bỏ yêu thích (liked = false)");
$t->assert($favModel->isFavorited($testUserId, $favPropId) === false, "4.4 Kiểm tra isFavorited trả về false");

// =========================================================================
// SUITE 5: ĐÁNH GIÁ & NHẬN XÉT (REVIEWS & RATINGS)
// =========================================================================
echo "\nSUITE 5: ĐÁNH GIÁ & NHẬN XÉT (REVIEWS & RATINGS)\n";

$reviewModel = new Review();
$revPropId = 14;

// Xóa review cũ nếu có để test sạch sẽ
$db = Database::connection();
$stmt = $db->prepare("DELETE FROM reviews WHERE property_id = ? AND user_id = ?");
$stmt->execute([$revPropId, $testUserId]);

// Thêm đánh giá 5 sao
$ok1 = $reviewModel->saveReview($revPropId, $testUserId, 5, 'Vị trí rất đẹp, giá cả hợp lý, đúng mô tả!');
$t->assert($ok1 === true, "5.1 Thêm đánh giá 5 sao thành công");

// Kiểm tra thống kê đánh giá
$stats = $reviewModel->getStats($revPropId);
$t->assert((int)$stats['total_reviews'] >= 1, "5.2 Thống kê tổng số đánh giá >= 1", "Total: {$stats['total_reviews']}");
$t->assert((float)$stats['avg_rating'] > 0, "5.3 Điểm đánh giá trung bình hợp lệ", "Avg rating: {$stats['avg_rating']}");

// Cập nhật lại đánh giá của user thành 4 sao
$ok2 = $reviewModel->saveReview($revPropId, $testUserId, 4, 'Đã cập nhật lại thành 4 sao.');
$t->assert($ok2 === true, "5.4 Cập nhật đánh giá hiện có (Upsert)");

$userRev = $reviewModel->getUserReview($revPropId, $testUserId);
$t->assert((int)$userRev['rating'] === 4, "5.5 Điểm rating sau cập nhật là 4 sao");

// Dọn dẹp dữ liệu review kiểm thử
$stmt = $db->prepare("DELETE FROM reviews WHERE property_id = ? AND user_id = ?");
$stmt->execute([$revPropId, $testUserId]);

// =========================================================================
// SUITE 6: KIỂM THỬ BẢO MẬT (IDOR/XSS/UPLOADS), EDIT TIN, COMPARE & PHÂN TRANG
// =========================================================================
echo "\nSUITE 6: BẢO MẬT (IDOR/XSS/UPLOADS), EDIT LISTING, COMPARE & INDEXES\n";

$propCtrlSrc = file_get_contents(APP . '/controllers/PropertyController.php');
$agentCtrlSrc = file_get_contents(APP . '/controllers/AgentController.php');
$searchViewSrc = file_get_contents(APP . '/views/property/search.php');
$detailViewSrc = file_get_contents(APP . '/views/property/detail.php');
$compareViewSrc = file_get_contents(APP . '/views/property/compare.php');
$agentEditViewSrc = file_get_contents(APP . '/views/agent/edit.php');
$homeViewSrc = file_get_contents(APP . '/views/home/index.php');
$finderSrc = file_get_contents(APP . '/models/PropertyFinder.php');
$mapJsSrc = file_get_contents(ROOT . '/public/assets/js/map.js');
$appJsSrc = file_get_contents(ROOT . '/public/assets/js/app.js');
$htaccessPath = ROOT . '/public/uploads/.htaccess';
$schemaSrc = file_get_contents(ROOT . '/database/schema.sql');

$t->assert(
    substr_count($propCtrlSrc, "(\$prop['status'] ?? '') === 'approved'") >= 2,
    "6.1 PropertyController::compare() và compareData() chỉ cho phép BĐS status === 'approved' (chặn BOLA/IDOR)"
);
$t->assert(
    strpos($agentCtrlSrc, "if (!\$this->isPost())") !== false
    && strpos($agentCtrlSrc, "\$this->json(['success' => false, 'message' => 'Phương thức không được hỗ trợ'], 405);") !== false,
    "6.2 AgentController::status() kiểm tra bắt buộc phương thức POST (trả 405 nếu GET)"
);
$t->assert(
    strpos($searchViewSrc, 'json_for_script($initialMapProperties)') !== false,
    "6.3 search.php sử dụng json_for_script(\$initialMapProperties) chống XSS breakout thẻ <script>"
);
$t->assert(
    strpos($mapJsSrc, 'escapeHtml(p.title)') !== false
    && strpos($mapJsSrc, 'escapeHtml(p.district_name)') !== false
    && strpos($mapJsSrc, 'escapeHtml(item.name)') !== false
    && strpos($detailViewSrc, 'escapeHtml(it.name)') !== false
    && strpos($appJsSrc, 'escapeHtml(p.title)') !== false,
    "6.4 map.js, detail.php và app.js bọc escapeHtml() đầy đủ chống DOM XSS"
);
$t->assert(
    is_file($htaccessPath)
    && strpos(file_get_contents($htaccessPath), 'php_flag engine off') !== false
    && strpos(file_get_contents($htaccessPath), 'Require all denied') !== false,
    "6.5 File public/uploads/.htaccess tồn tại và chặn thực thi script trong thư mục uploads"
);
$t->assert(
    strpos($compareViewSrc, "\$prop['cover_url'] ?? property_avatar_url(null)") !== false
    && strpos($compareViewSrc, "\$prop['agent_name'] ?? \$prop['author_name'] ?? 'Môi giới BĐS'") !== false
    && strpos($compareViewSrc, "\$prop['agent_phone'] ?? \$prop['author_phone'] ?? ''") !== false
    && strpos($compareViewSrc, 'Không nội thất') !== false
    && strpos($compareViewSrc, 'Nội thất cơ bản') !== false
    && strpos($compareViewSrc, 'Đầy đủ nội thất') !== false,
    "6.6 compare.php hiển thị đúng cover_url, agent_name, agent_phone và nhãn nội thất tiếng Việt"
);
$t->assert(
    strpos($finderSrc, "if (isset(\$rows[0]['total']))") !== false
    && $finder->countSearch(['keyword' => '__KHONG_TON_TAI_XYZ_99999__']) === 0,
    "6.7 PropertyFinder::countSearch() dùng isset(\$rows[0]['total']) và trả về 0 chính xác khi không có kết quả"
);
$t->assert(
    strpos($agentEditViewSrc, 'name="ward_id"') !== false
    && strpos($agentEditViewSrc, 'name="direction"') !== false
    && strpos($agentEditViewSrc, 'name="legal_status"') !== false
    && strpos($agentEditViewSrc, 'name="deposit"') !== false
    && strpos($agentEditViewSrc, 'name="furniture"') !== false
    && strpos($agentEditViewSrc, 'name="pets_allowed"') !== false
    && strpos($agentEditViewSrc, 'name="amenities[]"') !== false,
    "6.8 Form sửa tin agent/edit.php có đầy đủ các trường ward_id, direction, legal_status, deposit, furniture, pets_allowed, amenities[]"
);
$t->assert(
    strpos($homeViewSrc, 'http_build_query(array_merge($filters ?? [], [\'page\' => $i]))') !== false,
    "6.9 Phân trang trang chủ home/index.php giữ lại bộ lọc \$filters trong query string"
);
$t->assert(
    strpos($schemaSrc, 'idx_prop_status_created') !== false
    && strpos($schemaSrc, 'idx_prop_status_area') !== false
    && strpos($schemaSrc, 'idx_prop_img_cover') !== false,
    "6.10 database/schema.sql đã bổ sung đủ 3 composite indexes tối ưu truy vấn"
);

// =========================================================================
// 7. KIỂM THỬ 7 HẠNG MỤC NÂNG CẤP HOÀN THIỆN 15/15 (MỤC 4, 7, 8, 9, 10, 14, 15)
// =========================================================================
echo "\nSUITE 7: KIỂM THỬ 7 HẠNG MỤC NÂNG CẤP BỔ SUNG (HOÀN THIỆN 100% 15 KHUYẾT ĐIỂM)\n";

require_once APP . '/core/Auth.php';
require_once APP . '/models/User.php';
require_once APP . '/models/Notification.php';
require_once APP . '/models/Property.php';
require_once APP . '/helpers/ImageHelper.php';
require_once APP . '/helpers/Mailer.php';

// 7.1 Quên mật khẩu (SMTP Mailer + OWASP anti-enumeration) & Thu hồi phiên cũ khi đổi mật khẩu
$authSrc = file_get_contents(APP . '/core/Auth.php');
$authCtrlSrc = file_get_contents(APP . '/controllers/AuthController.php');
$forgotViewSrc = file_get_contents(APP . '/views/auth/forgot.php');
$userModel = new User();
$testResetToken = $userModel->createPasswordResetToken('agent1@bds.local');
$foundReset = $userModel->findValidPasswordReset($testResetToken);
$userModel->deletePasswordResetsForEmail('agent1@bds.local');

$mailer = new Mailer(['enabled' => false, 'host' => 'smtp.gmail.com', 'port' => 587, 'username' => '', 'password' => '', 'from_name' => 'Bất Động Sản']);
$mailHtml = Mailer::buildPasswordResetHtml('Nguyễn Văn Test', 'http://localhost/batdongsan/public/auth/reset?token=' . $testResetToken, 30);
$mailer->sendPasswordResetEmail('agent1@bds.local', 'Nguyễn Văn Test', 'http://localhost/batdongsan/public/auth/reset?token=' . $testResetToken, 30);
$smtpCfg = config('smtp', []);

$t->assert(
    strpos($authSrc, 'password_signature') !== false
    && strpos($authSrc, 'pwd_sig') !== false
    && strpos($authSrc, 'updatePasswordSignature') !== false
    && strpos($authCtrlSrc, 'function forgot(') !== false
    && strpos($authCtrlSrc, 'function reset(') !== false
    && strpos($authCtrlSrc, 'sendPasswordResetEmail') !== false
    && strpos($forgotViewSrc, '$resetLink') === false
    && strpos($mailHtml, 'Đặt lại mật khẩu') !== false
    && strpos($mailHtml, '/auth/reset?token=') !== false
    && is_array($smtpCfg) && array_key_exists('host', $smtpCfg) && $smtpCfg['host'] === 'smtp.gmail.com'
    && is_file(ROOT . '/storage/logs/mail.log')
    && is_file(APP . '/views/auth/forgot.php')
    && is_file(APP . '/views/auth/reset.php')
    && $foundReset !== null
    && ($foundReset['email'] ?? '') === 'agent1@bds.local',
    "7.1 [Mục 4/15] Quên mật khẩu (SMTP Mailer, password_resets, /auth/forgot, /auth/reset) & Thu hồi phiên cũ qua password_signature hoạt động chuẩn xác"
);

// 7.2 Vòng đời tin đăng (expired_at + Gia hạn tin) & Chống tăng lượt xem ảo
$propControllerSrc = file_get_contents(APP . '/controllers/PropertyController.php');
$agentControllerSrc = file_get_contents(APP . '/controllers/AgentController.php');
$agentIndexViewSrc = file_get_contents(APP . '/views/agent/index.php');
$hasExpiredCol = !empty($db->query("SHOW COLUMNS FROM properties LIKE 'expired_at'")->fetchAll());

$t->assert(
    $hasExpiredCol
    && strpos($propControllerSrc, "\$_SESSION['viewed_properties'][\$id]") !== false
    && strpos($agentControllerSrc, 'function renew(') !== false
    && strpos($agentIndexViewSrc, 'agent/renew/') !== false
    && strpos($agentIndexViewSrc, 'Gia hạn tin') !== false,
    "7.2 [Mục 7/15] Vòng đời tin đăng (cột expired_at, nút Gia hạn tin +30 ngày) & Chống tăng lượt xem ảo bằng session"
);

// 7.3 Hệ thống Thông báo In-app (Notification)
$notifModel = new Notification();
$testUserRow = $db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch();
$testUid = (int)($testUserRow['id'] ?? 1);
$notifId = $notifModel->create($testUid, 'Kiểm thử thông báo', 'Nội dung kiểm thử hệ thống thông báo', 'notification');
$unreadList = $notifModel->getUnreadForUser($testUid, 10);
$markedOk = $notifModel->markAsRead($notifId, $testUid);
$notifModel->markAllAsRead($testUid);
$db->prepare("DELETE FROM notifications WHERE id = ?")->execute([$notifId]);

$mainLayoutSrc = file_get_contents(APP . '/views/layouts/main.php');
$apptCtrlSrc = file_get_contents(APP . '/controllers/AppointmentController.php');
$modModelSrc = file_get_contents(APP . '/models/PropertyModeration.php');

$t->assert(
    $notifId > 0
    && !empty($unreadList)
    && $markedOk
    && is_file(APP . '/controllers/NotificationController.php')
    && is_file(APP . '/views/notification/index.php')
    && strpos($mainLayoutSrc, 'notificationDropdownBtn') !== false
    && strpos($apptCtrlSrc, 'new Notification()') !== false
    && strpos($modModelSrc, 'new Notification()') !== false,
    "7.3 [Mục 8/15] Hệ thống Thông báo In-app (Model Notification, NotificationController, chuông Navbar & tự động bắn thông báo 3 luồng)"
);

// 7.4 Nén và Resize ảnh tự động (WebP) + tạo thumbnail
$tmpImgPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_bds_large_' . uniqid() . '.jpg';
$tmpOutDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_bds_webp_' . uniqid();
$gdImg = imagecreatetruecolor(2000, 1200);
$bgColor = imagecolorallocate($gdImg, 35, 110, 185);
imagefill($gdImg, 0, 0, $bgColor);
imagejpeg($gdImg, $tmpImgPath, 92);
imagedestroy($gdImg);

$processedWebp = ImageHelper::processPropertyImage($tmpImgPath, $tmpOutDir, 1600, 82, true);
$webpSizeInfo = ($processedWebp && is_file($processedWebp['full_path'])) ? getimagesize($processedWebp['full_path']) : false;
$thumbSizeInfo = ($processedWebp && !empty($processedWebp['thumb_path']) && is_file($processedWebp['thumb_path'])) ? getimagesize($processedWebp['thumb_path']) : false;

if (is_file($tmpImgPath)) @unlink($tmpImgPath);
if ($processedWebp) {
    if (is_file($processedWebp['full_path'])) @unlink($processedWebp['full_path']);
    if (!empty($processedWebp['thumb_path']) && is_file($processedWebp['thumb_path'])) @unlink($processedWebp['thumb_path']);
}
if (is_dir($tmpOutDir)) @rmdir($tmpOutDir);

$t->assert(
    $processedWebp !== null
    && $webpSizeInfo !== false
    && $webpSizeInfo[0] === 1600
    && $webpSizeInfo['mime'] === 'image/webp'
    && $thumbSizeInfo !== false
    && $thumbSizeInfo[0] === 450
    && $thumbSizeInfo[1] === 300
    && strpos($agentControllerSrc, 'storePropertyData') !== false,
    "7.4 [Mục 9/15] Nén và Resize ảnh tự động sang WebP (2000px -> 1600px, quality 82) kèm sinh ảnh thumbnail 450x300px"
);

// 7.5 Bản đồ Marker Clustering (Leaflet.markercluster)
$mapJsSrc = file_get_contents(ROOT . '/public/assets/js/map.js');
$searchViewSrc = file_get_contents(APP . '/views/property/search.php');

$t->assert(
    strpos($mainLayoutSrc, 'leaflet.markercluster@1.5.3') !== false
    && strpos($searchViewSrc, 'leaflet.markercluster@1.5.3') !== false
    && strpos($mapJsSrc, 'L.markerClusterGroup') !== false
    && strpos($mapJsSrc, 'chunkedLoading: true') !== false
    && strpos($mapJsSrc, 'spiderfyOnMaxZoom: true') !== false,
    "7.5 [Mục 10/15] Bản đồ tìm kiếm tích hợp Leaflet.markercluster (chunkedLoading, spiderfyOnMaxZoom)"
);

// 7.6 Trang lỗi 500 chỉn chu & Global Exception Handler
$indexFrontSrc = file_get_contents(ROOT . '/public/index.php');
$error500Path = APP . '/views/errors/500.php';
$error500Src = is_file($error500Path) ? file_get_contents($error500Path) : '';

$t->assert(
    is_file($error500Path)
    && strpos($error500Src, '500') !== false
    && strpos($indexFrontSrc, 'catch (Throwable $e)') !== false
    && strpos($indexFrontSrc, "views/errors/500.php") !== false,
    "7.6 [Mục 14/15] Trang lỗi 500 thân thiện (app/views/errors/500.php) và Global Exception Handler trong public/index.php"
);

// 7.7 Cập nhật Avatar và Bio cho tài khoản
$profileCtrlSrc = file_get_contents(APP . '/controllers/ProfileController.php');
$profileViewSrc = file_get_contents(APP . '/views/profile/index.php');
$detailViewSrc = file_get_contents(APP . '/views/property/detail.php');

$t->assert(
    strpos($profileCtrlSrc, 'uploads/avatars') !== false
    && strpos($profileCtrlSrc, "'bio'") !== false
    && strpos($profileViewSrc, 'name="avatar"') !== false
    && strpos($profileViewSrc, 'name="bio"') !== false
    && strpos($detailViewSrc, 'agent_bio') !== false,
    "7.7 [Mục 15/15] Cập nhật Avatar (kèm xem trước) và Bio cho tài khoản trong ProfileController & hiển thị trên trang chi tiết tin"
);

// TỔNG KẾT BÁO CÁO
$t->report();

exit($t->hasFailures() ? 1 : 0);
