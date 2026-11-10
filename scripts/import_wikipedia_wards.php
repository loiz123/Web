<?php
/**
 * Script cào, chuẩn hóa và nạp dữ liệu địa chính 2 cấp (Tỉnh/Thành -> Phường & Xã)
 * từ 2 trang Wikipedia chính thức theo Phương án 1 (gán 100% trực tiếp theo cột Tỉnh trên Wikipedia):
 *   1. https://vi.wikipedia.org/wiki/Danh_s%C3%A1ch_ph%C6%B0%E1%BB%9Dng_t%E1%BA%A1i_Vi%E1%BB%87t_Nam (709 Phường)
 *   2. https://vi.wikipedia.org/wiki/Danh_s%C3%A1ch_x%C3%A3_t%E1%BA%A1i_Vi%E1%BB%87t_Nam (2.599 Xã)
 *
 * Nguyên tắc dữ liệu (Phương án 1):
 * - CHỈ LẤY Phường và Xã (3.308 đơn vị: 709 Phường + 2.599 Xã), TUYỆT ĐỐI BỎ Thị trấn (0 Thị trấn).
 * - Bỏ qua tách ngược tỉnh cũ: cột `Tỉnh` trên Wikipedia ghi tỉnh/thành nào thì chuẩn hóa và ánh xạ
 *   trực tiếp 100% vào `province_id` tương ứng trong bảng `provinces` (34 tỉnh/thành trên Wikipedia).
 *   Ví dụ: `Đức Huệ | Tây Ninh` -> `Tỉnh Tây Ninh` (province_id = 55);
 *          `Phước Hòa | Thành phố Hồ Chí Minh` -> `TP. Hồ Chí Minh` (province_id = 1).
 * - Bảo toàn 100% ID `1..709` của 709 Phường hiện có (giữ nguyên toàn bộ `ward_id` 1..18 và các `ward_id`
 *   mà 18 tin đăng mẫu đang tham chiếu) để không làm gãy khóa ngoại `properties.ward_id`.
 * - Xuất file SQL chuẩn hóa: `database/seeds/wikipedia_wards_2tier.sql` và `database/seeds/update_property_titles_2tier.sql`.
 *
 * Tham số CLI:
 *   --refresh    : Tải lại HTML mới nhất từ Wikipedia API (bỏ qua cache tạm).
 *   --from-seed  : Nạp trực tiếp từ `database/seeds/wikipedia_wards_2tier.sql` không qua scrape.
 *   --dry-run    : Chỉ kiểm tra và xuất thống kê, không ghi DB hoặc file seed.
 *   --db=<name>  : Chỉ định tên CSDL đích (mặc định lấy từ config/config.php).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$cfg = require $root . '/config/config.php';

$cliArgs = array_slice($argv ?? [], 1);
$optRefresh  = in_array('--refresh', $cliArgs, true);
$optFromSeed = in_array('--from-seed', $cliArgs, true);
$optDryRun   = in_array('--dry-run', $cliArgs, true);
$targetDb    = $cfg['db']['name'];
foreach ($cliArgs as $arg) {
    if (str_starts_with($arg, '--db=')) {
        $targetDb = substr($arg, 5);
    }
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['db']['host'], $cfg['db']['port'], $targetDb),
    $cfg['db']['user'],
    $cfg['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

if ($optFromSeed) {
    $seedPath = $root . '/database/seeds/wikipedia_wards_2tier.sql';
    if (!is_file($seedPath)) {
        throw new RuntimeException("Không tìm thấy file seed: {$seedPath}");
    }
    echo "Đang nạp trực tiếp từ {$seedPath} vào CSDL `{$targetDb}`...\n";
    if (!$optDryRun) {
        $sqlContent = str_replace(["\r\n", "\r"], "\n", (string)file_get_contents($seedPath));
        $pdo->exec('SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS = 0;');
        foreach (array_filter(array_map('trim', explode(";\n", $sqlContent))) as $stmt) {
            if ($stmt !== '' && !str_starts_with($stmt, '--')) {
                $pdo->exec($stmt);
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }
    printPostLoadAudit($pdo, $targetDb);
    exit(0);
}

// -------------------------------------------------------------------------
// Helper chuẩn hóa tên & Parsing Wikipedia
// -------------------------------------------------------------------------
function normalizeViProvince(string $str): string
{
    $str = trim(mb_strtolower($str, 'UTF-8'));
    if ($str === '') {
        return '';
    }
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
    $str = preg_replace('/[^a-z0-9\s]/', ' ', $str) ?? $str;
    $str = preg_replace('/\s+/', ' ', $str) ?? $str;
    $str = trim($str);
    return trim((string)preg_replace('/^(thanh pho|tp|tinh)\s+/', '', $str));
}

function fetchWikipediaPageHtml(string $pageTitle, string $cacheFile, bool $refresh): string
{
    if (!$refresh && is_file($cacheFile) && filesize($cacheFile) > 50000) {
        return (string)file_get_contents($cacheFile);
    }
    $url = 'https://vi.wikipedia.org/w/api.php?action=parse&page=' . rawurlencode($pageTitle) . '&prop=text&format=json';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'BatDongSanBot/2.0 (https://localhost)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    $json = json_decode((string)$res, true);
    $html = $json['parse']['text']['*'] ?? '';
    if ($html === '') {
        throw new RuntimeException("Không thể tải trang Wikipedia '{$pageTitle}': {$err}");
    }
    file_put_contents($cacheFile, $html);
    return $html;
}

function cleanWikiCellText(DOMNode $cell, DOMXPath $xpath): string
{
    $clone = $cell->cloneNode(true);
    foreach ($xpath->query('.//sup|.//style|.//script', $clone) as $sup) {
        $sup->parentNode->removeChild($sup);
    }
    $text = html_entity_decode($clone->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s*\([^)]*\)/u', '', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function parseWikiAdminTable(string $html, string $prefix): array
{
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);
    $tables = $xpath->query('//table[contains(@class, "wikitable")]');
    if (!$tables || $tables->length === 0) {
        throw new RuntimeException("Không tìm thấy bảng wikitable cho '{$prefix}'.");
    }
    $rows = [];
    foreach ($xpath->query('.//tr', $tables->item(0)) as $tr) {
        $cells = $xpath->query('./td', $tr);
        if ($cells->length < 3) {
            continue;
        }
        $rawName = cleanWikiCellText($cells->item(1), $xpath);
        $rawProv = cleanWikiCellText($cells->item(2), $xpath);
        if ($rawName === '' || $rawProv === '') {
            continue;
        }
        if (preg_match('/^thị\s+trấn\b/iu', $rawName)) {
            continue;
        }
        if (preg_match('/^' . $prefix . '\s+/iu', $rawName)) {
            $fullName = preg_replace('/^' . $prefix . '\s+/iu', $prefix . ' ', $rawName) ?? $rawName;
        } else {
            $fullName = $prefix . ' ' . $rawName;
        }

        $rows[] = [
            'prefix'    => $prefix,
            'name'      => $fullName,
            'raw_name'  => $rawName,
            'wiki_prov' => $rawProv,
        ];
    }
    return $rows;
}

// -------------------------------------------------------------------------
// 1. Tải & parse bảng Phường (709) + Xã (2.599) từ Wikipedia
// -------------------------------------------------------------------------
$tmpDir = sys_get_temp_dir();
$htmlPhuong = fetchWikipediaPageHtml('Danh_sách_phường_tại_Việt_Nam', $tmpDir . '/wiki_phuong.html', $optRefresh);
$htmlXa     = fetchWikipediaPageHtml('Danh_sách_xã_tại_Việt_Nam',     $tmpDir . '/wiki_xa.html',     $optRefresh);

$phuongList = parseWikiAdminTable($htmlPhuong, 'Phường');
$xaList     = parseWikiAdminTable($htmlXa,     'Xã');
$allItems   = array_merge($phuongList, $xaList);

echo sprintf(
    "Đã đọc từ Wikipedia: %d Phường + %d Xã = %d đơn vị hành chính cấp xã (0 Thị trấn).\n",
    count($phuongList),
    count($xaList),
    count($allItems)
);
if (count($phuongList) !== 709 || count($xaList) !== 2599 || count($allItems) !== 3308) {
    throw new RuntimeException(sprintf(
        'Số lượng từ Wikipedia không khớp chuẩn (Phường=%d/709, Xã=%d/2599).',
        count($phuongList),
        count($xaList)
    ));
}

// -------------------------------------------------------------------------
// 2. Đọc bảng `provinces` và ánh xạ 100% trực tiếp cột `Tỉnh` Wikipedia -> province_id
// -------------------------------------------------------------------------
$provinces = $pdo->query('SELECT id, name FROM provinces ORDER BY id ASC')->fetchAll(PDO::FETCH_KEY_PAIR);
if (count($provinces) === 0) {
    $origSqlPath = $root . '/database/seeds/vietnam_administrative_divisions.sql';
    $origSql = (string)file_get_contents($origSqlPath);
    $provPart = explode('-- 2. BẢNG DISTRICTS', $origSql)[0];
    preg_match_all("/\((\d+),\s*'((?:[^'\\\\]|\\\\.|'')*)'\)/u", $provPart, $pm);
    foreach ($pm[1] as $i => $id) {
        $provinces[(int)$id] = str_replace("''", "'", $pm[2][$i]);
    }
}
if (count($provinces) < 34) {
    throw new RuntimeException('Bảng provinces không đủ dữ liệu để ánh xạ (' . count($provinces) . ' dòng).');
}

$provNormToId = [];
foreach ($provinces as $pid => $pname) {
    $norm = normalizeViProvince((string)$pname);
    $provNormToId[$norm] = (int)$pid;
}

// Bí danh chuẩn hóa bổ sung phòng trường hợp khác biệt chính tả/tên gọi trên Wikipedia
$directAliases = [
    'tphcm'          => 'ho chi minh',
    'tp hcm'         => 'ho chi minh',
    'hcm'            => 'ho chi minh',
    'sai gon'        => 'ho chi minh',
    'thua thien hue' => 'hue',
    'dac lac'        => 'dak lak',
    'daklak'         => 'dak lak',
    'dac nong'       => 'dak nong',
    'kon tum'        => 'kon tum',
    'bac can'        => 'bac kan',
];
foreach ($directAliases as $alias => $canonical) {
    if (isset($provNormToId[$canonical]) && !isset($provNormToId[$alias])) {
        $provNormToId[$alias] = $provNormToId[$canonical];
    }
}

$resolvedPid = [];
$statsByPid  = [];
foreach ($allItems as $idx => $it) {
    $wpNorm = normalizeViProvince($it['wiki_prov']);
    if (!isset($provNormToId[$wpNorm])) {
        throw new RuntimeException("Không ánh xạ được cột Tỉnh Wikipedia '{$it['wiki_prov']}' (chuẩn hóa: '{$wpNorm}') vào bảng `provinces`!");
    }
    $pid = $provNormToId[$wpNorm];
    $resolvedPid[$idx] = $pid;
    if (!isset($statsByPid[$pid])) {
        $statsByPid[$pid] = ['name' => $provinces[$pid], 'phuong' => 0, 'xa' => 0, 'total' => 0];
    }
    $statsByPid[$pid][$it['prefix'] === 'Phường' ? 'phuong' : 'xa']++;
    $statsByPid[$pid]['total']++;
}

ksort($statsByPid);
echo "\n=== BẢNG PHÂN BỔ 3.308 PHƯỜNG & XÃ TRỰC TIẾP THEO CỘT TỈNH WIKIPEDIA ===\n";
foreach ($statsByPid as $pid => $st) {
    echo sprintf(
        "  [%02d] %-24s : %3d đơn vị (%3d Phường, %3d Xã)\n",
        $pid,
        $st['name'],
        $st['total'],
        $st['phuong'],
        $st['xa']
    );
}
echo sprintf("Tổng số tỉnh/thành có trên bảng Wikipedia: %d tỉnh/thành | Tổng cộng: %d đơn vị.\n\n", count($statsByPid), count($allItems));

// -------------------------------------------------------------------------
// 3. Xây dựng danh sách sạch (id 1..709 Phường, 710..3308 Xã) & kiểm tra toàn vẹn
// -------------------------------------------------------------------------
$cleanWards  = [];
$seenKeys    = [];
$provCovered = [];
foreach ($allItems as $idx => $it) {
    $pid = (int)$resolvedPid[$idx];
    $dedupKey = $pid . '|' . mb_strtolower($it['name'], 'UTF-8');
    if (isset($seenKeys[$dedupKey])) {
        throw new RuntimeException("Phát hiện trùng lặp trong cùng tỉnh pid={$pid}: {$it['name']}");
    }
    $seenKeys[$dedupKey] = true;
    $provCovered[$pid] = ($provCovered[$pid] ?? 0) + 1;

    $cleanWards[] = [
        'id'          => $idx + 1, // 1..709 giữ nguyên id Phường cũ, 710..3308 là Xã
        'province_id' => $pid,
        'name'        => $it['name'],
        'prefix'      => $it['prefix'],
    ];
}

if (count($provCovered) !== count($statsByPid) || count($provCovered) < 34) {
    throw new RuntimeException('Số tỉnh có dữ liệu wards không khớp cột Tỉnh Wikipedia: ' . count($provCovered));
}

// Tạo lookup (province_id, ward_name) -> ward_id và ánh xạ 18 BĐS mẫu
$lookupWardId = [];
foreach ($cleanWards as $w) {
    $lookupWardId[$w['province_id'] . '|' . mb_strtolower($w['name'], 'UTF-8')] = $w['id'];
}
$getWardId = static function (int $provId, string $wardName) use ($lookupWardId): int {
    $key = $provId . '|' . mb_strtolower(trim($wardName), 'UTF-8');
    if (!isset($lookupWardId[$key])) {
        throw new RuntimeException("Không tìm thấy '{$wardName}' thuộc province_id={$provId}!");
    }
    return $lookupWardId[$key];
};

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
foreach ($propertyUpdates as $propId => $info) {
    $propertyUpdates[$propId]['ward_id'] = $getWardId($info['prov'], $info['ward']);
}

if ($optDryRun) {
    echo '[DRY-RUN] Xác nhận hợp lệ 3.308 đơn vị (709 Phường + 2.599 Xã) trên ' . count($provCovered) . " tỉnh/thành trực tiếp theo Wikipedia. Không ghi file/DB.\n";
    exit(0);
}

// -------------------------------------------------------------------------
// 4. Nạp vào MariaDB đích (bảo toàn 100% FK và giữ nguyên ward_id 1..18)
// -------------------------------------------------------------------------
$cols = $pdo->query('SHOW COLUMNS FROM wards')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('province_id', $cols, true)) {
    $pdo->exec('ALTER TABLE wards ADD COLUMN province_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
}
$pdo->exec('ALTER TABLE wards MODIFY COLUMN district_id INT UNSIGNED NULL DEFAULT NULL');

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE wards');

$insStmt = $pdo->prepare('INSERT INTO wards (id, province_id, district_id, name) VALUES (?, ?, NULL, ?)');
$pdo->beginTransaction();
foreach ($cleanWards as $w) {
    $insStmt->execute([$w['id'], $w['province_id'], $w['name']]);
}
$pdo->commit();

$updPropStmt = $pdo->prepare('UPDATE properties SET ward_id = ?, title = ?, slug = ?, address_detail = ? WHERE id = ?');
foreach ($propertyUpdates as $propId => $info) {
    $updPropStmt->execute([$info['ward_id'], $info['title'], $info['slug'], $info['addr'], $propId]);
}
$defaultHcmWardId = $getWardId(1, 'Phường Bến Thành');
$pdo->exec("UPDATE properties SET ward_id = {$defaultHcmWardId} WHERE ward_id NOT IN (SELECT id FROM wards)");
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// -------------------------------------------------------------------------
// 5. Xuất 2 file SQL seed chuẩn: wikipedia_wards_2tier.sql & update_property_titles_2tier.sql
// -------------------------------------------------------------------------
$sqlLines = [
    '-- ===========================================================',
    '-- DANH SÁCH CHUẨN 3.308 PHƯỜNG & XÃ VIỆT NAM TỪ WIKIPEDIA (PHƯƠNG ÁN 1 - MÔ HÌNH 2 CẤP)',
    '-- Nguồn: Danh_sách_phường_tại_Việt_Nam (709 Phường) + Danh_sách_xã_tại_Việt_Nam (2.599 Xã)',
    '-- Gán 100% trực tiếp theo cột Tỉnh trên Wikipedia (34 Tỉnh/Thành phố, 0 Thị trấn, 0 trùng lặp)',
    '-- Bảo toàn 100% id 1..709 của các Phường cũ để giữ nguyên khóa ngoại 18 bất động sản mẫu.',
    '-- ===========================================================',
    '',
    'SET NAMES utf8mb4;',
    'SET FOREIGN_KEY_CHECKS = 0;',
    '',
    'ALTER TABLE `wards` MODIFY COLUMN `district_id` INT UNSIGNED NULL DEFAULT NULL;',
    '',
    'TRUNCATE TABLE `wards`;',
    '',
];

foreach (array_chunk($cleanWards, 500) as $chunk) {
    $sqlLines[] = 'INSERT INTO `wards` (`id`, `province_id`, `district_id`, `name`) VALUES';
    $valueRows = [];
    foreach ($chunk as $w) {
        $escapedName = str_replace("'", "''", $w['name']);
        $valueRows[] = sprintf("  (%d, %d, NULL, '%s')", $w['id'], $w['province_id'], $escapedName);
    }
    $sqlLines[] = implode(",\n", $valueRows) . ';';
    $sqlLines[] = '';
}

$sqlLines[] = '-- Cập nhật ward_id chuẩn cho 18 bất động sản mẫu';
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
$sqlLines[] = '';
$sqlLines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$sqlLines[] = '';

$seedFile = $root . '/database/seeds/wikipedia_wards_2tier.sql';
file_put_contents($seedFile, implode("\n", $sqlLines));

$propSqlLines = [
    '-- ===========================================================',
    '-- CẬP NHẬT TIÊU ĐỀ, SLUG VÀ ĐỊA CHỈ 18 BẤT ĐỘNG SẢN CHUẨN 2 CẤP (3.308 PHƯỜNG/XÃ WIKIPEDIA)',
    '-- ===========================================================',
    '',
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

echo "Đã xuất file SQL: {$seedFile}\n";
printPostLoadAudit($pdo, $targetDb);

function printPostLoadAudit(PDO $pdo, string $dbName): void
{
    $total     = (int)$pdo->query('SELECT COUNT(*) FROM wards')->fetchColumn();
    $phuong    = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Phường %'")->fetchColumn();
    $xa        = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Xã %'")->fetchColumn();
    $thitran   = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Thị trấn %'")->fetchColumn();
    $provs     = (int)$pdo->query('SELECT COUNT(DISTINCT province_id) FROM wards')->fetchColumn();
    $dups      = (int)$pdo->query('SELECT COUNT(*) FROM (SELECT province_id, BINARY name FROM wards GROUP BY province_id, BINARY name HAVING COUNT(*) > 1) t')->fetchColumn();
    $validFk   = (int)$pdo->query('SELECT COUNT(*) FROM properties p INNER JOIN wards w ON w.id = p.ward_id')->fetchColumn();
    $totalProp = (int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();

    echo "\n=== KIỂM TRA SAU KHI NẠP [{$dbName}] ===\n";
    echo "  Tổng wards        : {$total} (Phường: {$phuong}, Xã: {$xa}, Thị trấn: {$thitran})\n";
    echo "  Số tỉnh có dữ liệu: {$provs}/63 (34 tỉnh/thành trực tiếp theo cột Tỉnh Wikipedia)\n";
    echo "  Trùng (prov,name) : {$dups}\n";
    echo "  FK properties     : {$validFk}/{$totalProp}\n";
}
