<?php
/**
 * Script cào và cập nhật chính xác 100% danh sách 709 phường từ Wikipedia:
 * https://vi.wikipedia.org/wiki/Danh_s%C3%A1ch_ph%C6%B0%E1%BB%9Dng_t%E1%BA%A1i_Vi%E1%BB%87t_Nam
 *
 * Thực hiện:
 * 1. Đọc bảng wikitable 709 phường từ Wikipedia (hoặc cache scripts/wiki_wards.html).
 * 2. Chuẩn hóa tên thành "Phường [Tên]" (hoặc giữ nguyên nếu đã bắt đầu bằng Phường), khớp 100% với 34 tỉnh/thành trong bảng provinces.
 * 3. Xóa sạch toàn bộ phường rác/cũ trong bảng wards, nạp đúng 709 phường từ Wikipedia (id 1..709, district_id = NULL, province_id chính xác).
 * 4. Cập nhật 18 bất động sản mẫu trong bảng properties trỏ vào đúng ward_id của tỉnh/thành phố tương ứng (TP.HCM, Hà Nội, Đà Nẵng).
 * 5. Xuất file SQL seed chuẩn: database/seeds/wikipedia_wards_2tier.sql và cập nhật database/seeds/update_property_titles_2tier.sql.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$cfg = require $root . '/config/config.php';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['db']['host'], $cfg['db']['port'], $cfg['db']['name']),
    $cfg['db']['user'],
    $cfg['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// 1. Tải HTML từ Wikipedia API nếu chưa có file cache
$htmlFile = __DIR__ . '/wiki_wards.html';
if (!is_file($htmlFile) || filesize($htmlFile) < 100000) {
    $apiUrl = 'https://vi.wikipedia.org/w/api.php?action=parse&page=Danh_s%C3%A1ch_ph%C6%B0%E1%BB%9Dng_t%E1%BA%A1i_Vi%E1%BB%87t_Nam&prop=text&format=json';
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) BDSViet-AdminSync/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $json = json_decode((string)$res, true);
    $html = $json['parse']['text']['*'] ?? '';
    if ($html === '') {
        throw new RuntimeException('Không thể tải dữ liệu từ Wikipedia API.');
    }
    file_put_contents($htmlFile, $html);
} else {
    $html = (string)file_get_contents($htmlFile);
}

// 2. Parse bảng wikitable
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
$xpath = new DOMXPath($dom);

$tables = $xpath->query('//table[contains(@class, "wikitable")]');
if (!$tables || $tables->length === 0) {
    throw new RuntimeException('Không tìm thấy bảng wikitable trong trang Wikipedia.');
}

function cleanWikiCellText(DOMNode $cell, DOMXPath $xpath): string {
    $clone = $cell->cloneNode(true);
    foreach ($xpath->query('.//sup|.//style|.//script', $clone) as $sup) {
        $sup->parentNode->removeChild($sup);
    }
    $text = html_entity_decode($clone->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Bỏ chú thích trong ngoặc nếu có
    $text = preg_replace('/\s*\([^)]*\)/u', '', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function normalizeVi(string $str): string {
    $str = trim(mb_strtolower($str, 'UTF-8'));
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d',
    ];
    $str = strtr($str, $map);
    $str = preg_replace('/^(thanh pho|tp\.?|tinh)\s+/u', '', $str) ?? $str;
    $str = preg_replace('/[^a-z0-9\s]/', ' ', $str) ?? $str;
    $str = preg_replace('/\s+/', ' ', $str) ?? $str;
    return trim($str);
}

// Lấy danh sách provinces từ DB
$dbProvinces = $pdo->query('SELECT id, name FROM provinces ORDER BY id ASC')->fetchAll();
$provMap = [];
foreach ($dbProvinces as $p) {
    $norm = normalizeVi($p['name']);
    $provMap[$norm] = (int)$p['id'];
    if ($norm === 'thua thien hue') {
        $provMap['hue'] = (int)$p['id'];
    }
}

$wikiWards = [];
$trNodes = $xpath->query('.//tr', $tables->item(0));
foreach ($trNodes as $idx => $tr) {
    if ($idx === 0) continue; // Header row
    $cells = $xpath->query('./td|./th', $tr);
    if ($cells->length < 3) continue;

    $stt = cleanWikiCellText($cells->item(0), $xpath);
    $rawName = cleanWikiCellText($cells->item(1), $xpath);
    $rawProv = cleanWikiCellText($cells->item(2), $xpath);

    if ($rawName === '' || $rawProv === '') continue;

    $provNorm = normalizeVi($rawProv);
    if (!isset($provMap[$provNorm])) {
        throw new RuntimeException("Không khớp được tỉnh/thành phố: '{$rawProv}' (normalized: '{$provNorm}')");
    }
    $provinceId = $provMap[$provNorm];

    // Chuẩn hóa tên phường: thêm tiền tố "Phường " nếu chưa có
    if (preg_match('/^phường\s+/iu', $rawName)) {
        $wardName = preg_replace('/^phường\s+/iu', 'Phường ', $rawName) ?? $rawName;
    } else {
        $wardName = 'Phường ' . $rawName;
    }

    $wikiWards[] = [
        'id'          => count($wikiWards) + 1,
        'stt'         => (int)$stt,
        'name'        => $wardName,
        'raw_name'    => $rawName,
        'province_id' => $provinceId,
        'province'    => $rawProv,
    ];
}

echo "Tổng số phường parse từ bảng Wikipedia: " . count($wikiWards) . " phường.\n";
if (count($wikiWards) !== 709) {
    throw new RuntimeException("Số lượng phường không đúng 709 (thực tế: " . count($wikiWards) . ")");
}

// 3. Đảm bảo cấu trúc bảng wards hỗ trợ mô hình 2 cấp (province_id NOT NULL, district_id NULL DEFAULT NULL)
$cols = $pdo->query("SHOW COLUMNS FROM wards")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('province_id', $cols, true)) {
    $pdo->exec("ALTER TABLE wards ADD COLUMN province_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
}
$pdo->exec("ALTER TABLE wards MODIFY COLUMN district_id INT UNSIGNED NULL DEFAULT NULL");

// 4. Nạp đúng 709 phường từ Wikipedia vào bảng wards
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE wards");

$insStmt = $pdo->prepare("INSERT INTO wards (id, province_id, district_id, name) VALUES (?, ?, NULL, ?)");
foreach ($wikiWards as $w) {
    $insStmt->execute([$w['id'], $w['province_id'], $w['name']]);
}

// Tạo bảng tra cứu nhanh (province_id, ward_name) -> ward_id
$lookupWardId = [];
foreach ($wikiWards as $w) {
    $lookupWardId[$w['province_id'] . '|' . mb_strtolower($w['name'], 'UTF-8')] = $w['id'];
}

$getWardId = function (int $provId, string $wardName) use ($lookupWardId): int {
    $key = $provId . '|' . mb_strtolower(trim($wardName), 'UTF-8');
    if (!isset($lookupWardId[$key])) {
        throw new RuntimeException("Không tìm thấy '{$wardName}' thuộc province_id={$provId} trong 709 phường Wikipedia!");
    }
    return $lookupWardId[$key];
};

// 5. Ánh xạ 18 bất động sản mẫu vào đúng phường chuẩn Wikipedia thuộc đúng tỉnh/thành phố
$propertyUpdates = [
    1  => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Căn hộ 2PN view sông Phường An Khánh (Thảo Điền)',           'slug' => 'can-ho-2pn-view-song-phuong-an-khanh-thao-dien',           'addr' => '12 Xuân Thủy'],
    2  => ['prov' => 1, 'ward' => 'Phường Sài Gòn',    'title' => 'Nhà phố 4 tầng trung tâm Phường Sài Gòn',                    'slug' => 'nha-pho-4-tang-trung-tam-phuong-sai-gon',                  'addr' => '45 Hai Bà Trưng'],
    3  => ['prov' => 3, 'ward' => 'Phường Hải Châu',   'title' => 'Đất nền mặt tiền Phường Hải Châu',                           'slug' => 'dat-nen-mat-tien-phuong-hai-chau',                         'addr' => '36 Bạch Đằng'],
    4  => ['prov' => 1, 'ward' => 'Phường Sài Gòn',    'title' => 'Căn hộ 3PN ban công rộng tại Phường Sài Gòn',                'slug' => 'can-ho-3pn-ban-cong-rong-tai-phuong-sai-gon',              'addr' => '12 Nguyễn Huệ'],
    5  => ['prov' => 1, 'ward' => 'Phường Xuân Hòa',   'title' => 'Nhà phố hẻm xe hơi tại Phường Xuân Hòa',                     'slug' => 'nha-pho-hem-xe-hoi-tai-phuong-xuan-hoa',                   'addr' => '28 Trần Quốc Thảo'],
    6  => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Căn hộ 1PN cho thuê tại Phường An Khánh (Thảo Điền)',        'slug' => 'can-ho-1pn-cho-thue-tai-phuong-an-khanh-thao-dien',        'addr' => '18 Quốc Hương'],
    7  => ['prov' => 1, 'ward' => 'Phường Bến Thành',  'title' => 'Căn hộ 2PN gần chợ Phường Bến Thành',                        'slug' => 'can-ho-2pn-gan-cho-phuong-ben-thanh',                      'addr' => '45 Lê Thị Hồng Gấm'],
    8  => ['prov' => 3, 'ward' => 'Phường Hải Châu',   'title' => 'Đất nền vuông vức tại Phường Hải Châu',                      'slug' => 'dat-nen-vuong-vuc-tai-phuong-hai-chau',                    'addr' => '70 Nguyễn Văn Linh'],
    9  => ['prov' => 2, 'ward' => 'Phường Cầu Giấy',   'title' => 'Nhà phố 4 tầng gần công viên Phường Cầu Giấy',               'slug' => 'nha-pho-4-tang-gan-cong-vien-phuong-cau-giay',             'addr' => '16 Trần Thái Tông'],
    10 => ['prov' => 2, 'ward' => 'Phường Ba Đình',    'title' => 'Văn phòng sáng thoáng tại Phường Ba Đình',                   'slug' => 'van-phong-sang-thoang-tai-phuong-ba-dinh',                 'addr' => '90 Điện Biên Phủ'],
    11 => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Biệt thự sân vườn tại Phường An Khánh (Thảo Điền)',          'slug' => 'biet-thu-san-vuon-tai-phuong-an-khanh-thao-dien',          'addr' => '35 Nguyễn Văn Hưởng'],
    12 => ['prov' => 1, 'ward' => 'Phường Tân Định',   'title' => 'Căn hộ 2PN tại Phường Tân Định',                             'slug' => 'can-ho-2pn-tai-phuong-tan-dinh',                           'addr' => '8 Nguyễn Đình Chiểu'],
    13 => ['prov' => 3, 'ward' => 'Phường Sơn Trà',    'title' => 'Condotel hướng biển tại Phường Sơn Trà',                     'slug' => 'condotel-huong-bien-tai-phuong-son-tra',                   'addr' => '22 Võ Nguyên Giáp'],
    14 => ['prov' => 1, 'ward' => 'Phường Bình Thạnh', 'title' => 'Studio mini 1PN tiện nghi giá rẻ Phường Bình Thạnh',         'slug' => 'studio-mini-1pn-tien-nghi-gia-re-phuong-binh-thanh',       'addr' => '15/4 Điện Biên Phủ'],
    15 => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Dinh thự ven sông Phường An Khánh (Thảo Điền) 6PN đẳng cấp', 'slug' => 'dinh-thu-ven-song-phuong-an-khanh-thao-dien-6pn-dang-cap', 'addr' => '215 Nguyễn Văn Hưởng'],
    16 => ['prov' => 1, 'ward' => 'Phường Xuân Hòa',   'title' => 'Nhà phố kinh doanh 3PN mặt tiền Phường Xuân Hòa',            'slug' => 'nha-pho-kinh-doanh-3pn-mat-tien-phuong-xuan-hoa',          'addr' => '88 Võ Thị Sáu'],
    17 => ['prov' => 2, 'ward' => 'Phường Cầu Giấy',   'title' => 'Căn hộ 2PN cao cấp Phường Cầu Giấy',                         'slug' => 'can-ho-2pn-cao-cap-phuong-cau-giay',                       'addr' => '72 Trần Đăng Ninh'],
    18 => ['prov' => 3, 'ward' => 'Phường Hải Châu',   'title' => 'Đất nền biệt thự sinh thái Phường Hải Châu Đà Nẵng',         'slug' => 'dat-nen-biet-thu-sinh-thai-phuong-hai-chau-da-nang',       'addr' => 'Lô B2-15 Đường 2 Tháng 9'],
];

$updPropStmt = $pdo->prepare("UPDATE properties SET ward_id = ?, title = ?, slug = ?, address_detail = ? WHERE id = ?");
foreach ($propertyUpdates as $propId => $info) {
    $wId = $getWardId($info['prov'], $info['ward']);
    $propertyUpdates[$propId]['ward_id'] = $wId;
    $updPropStmt->execute([$wId, $info['title'], $info['slug'], $info['addr'], $propId]);
}

// Đảm bảo bất kỳ tin phát sinh nào khác (nếu có > 18) cũng có ward_id hợp lệ
$defaultHcmWardId = $getWardId(1, 'Phường Bến Thành');
$pdo->exec("UPDATE properties SET ward_id = {$defaultHcmWardId} WHERE ward_id NOT IN (SELECT id FROM wards)");

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// 6. Xuất file SQL seed chuẩn: database/seeds/wikipedia_wards_2tier.sql
$sqlLines = [];
$sqlLines[] = "-- ===========================================================";
$sqlLines[] = "-- DANH SÁCH CHUẨN 709 PHƯỜNG VIỆT NAM TỪ WIKIPEDIA (MÔ HÌNH 2 CẤP)";
$sqlLines[] = "-- Nguồn: https://vi.wikipedia.org/wiki/Danh_s%C3%A1ch_ph%C6%B0%E1%BB%9Dng_t%E1%BA%A1i_Vi%E1%BB%87t_Nam";
$sqlLines[] = "-- Tổng số: 709 phường thuộc 34 tỉnh/thành phố (TP.HCM: 113, Hà Nội: 51, Đà Nẵng: 23...)";
$sqlLines[] = "-- ===========================================================";
$sqlLines[] = "";
$sqlLines[] = "SET NAMES utf8mb4;";
$sqlLines[] = "SET FOREIGN_KEY_CHECKS = 0;";
$sqlLines[] = "";
$sqlLines[] = "ALTER TABLE `wards` MODIFY COLUMN `district_id` INT UNSIGNED NULL DEFAULT NULL;";
$sqlLines[] = "";
$sqlLines[] = "TRUNCATE TABLE `wards`;";
$sqlLines[] = "";
$sqlLines[] = "INSERT INTO `wards` (`id`, `province_id`, `district_id`, `name`) VALUES";

$valueRows = [];
foreach ($wikiWards as $w) {
    $escapedName = str_replace("'", "''", $w['name']);
    $valueRows[] = sprintf("  (%d, %d, NULL, '%s')", $w['id'], $w['province_id'], $escapedName);
}
$sqlLines[] = implode(",\n", $valueRows) . ";";
$sqlLines[] = "";
$sqlLines[] = "-- Cập nhật ward_id chuẩn cho 18 bất động sản mẫu";
foreach ($propertyUpdates as $propId => $info) {
    $tEsc = str_replace("'", "''", $info['title']);
    $sEsc = str_replace("'", "''", $info['slug']);
    $aEsc = str_replace("'", "''", $info['addr']);
    $sqlLines[] = sprintf(
        "UPDATE `properties` SET `ward_id` = %d, `title` = '%s', `slug` = '%s', `address_detail` = '%s' WHERE `id` = %d;",
        $info['ward_id'],
        $tEsc,
        $sEsc,
        $aEsc,
        $propId
    );
}
$sqlLines[] = "";
$sqlLines[] = "SET FOREIGN_KEY_CHECKS = 1;";
$sqlLines[] = "";

$seedFile = $root . '/database/seeds/wikipedia_wards_2tier.sql';
file_put_contents($seedFile, implode("\n", $sqlLines));

// Cập nhật luôn database/seeds/update_property_titles_2tier.sql để đồng bộ
$propSqlLines = [
    "-- ===========================================================",
    "-- CẬP NHẬT TIÊU ĐỀ, SLUG VÀ ĐỊA CHỈ 18 BẤT ĐỘNG SẢN CHUẨN 2 CẤP (709 PHƯỜNG WIKIPEDIA)",
    "-- ===========================================================",
    "",
    "ALTER TABLE `properties` ADD COLUMN IF NOT EXISTS `slug` VARCHAR(255) NULL DEFAULT NULL AFTER `title`;",
    ""
];
foreach ($propertyUpdates as $propId => $info) {
    $tEsc = str_replace("'", "''", $info['title']);
    $sEsc = str_replace("'", "''", $info['slug']);
    $aEsc = str_replace("'", "''", $info['addr']);
    $propSqlLines[] = sprintf(
        "UPDATE `properties` SET `title` = '%s', `slug` = '%s', `address_detail` = '%s', `ward_id` = %d WHERE `id` = %d;",
        $tEsc,
        $sEsc,
        $aEsc,
        $info['ward_id'],
        $propId
    );
}
file_put_contents($root . '/database/seeds/update_property_titles_2tier.sql', implode("\n", $propSqlLines) . "\n");

echo "Đã cập nhật thành công bảng wards (709 phường) và 18 bất động sản mẫu!\n";
echo "Đã tạo file SQL: {$seedFile}\n";
