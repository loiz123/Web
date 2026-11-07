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

// TỔNG KẾT BÁO CÁO
$t->report();

exit($t->hasFailures() ? 1 : 0);
