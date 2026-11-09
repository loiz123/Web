<?php
declare(strict_types=1);

/**
 * Script thu thập, chuẩn hóa và nạp đầy đủ dữ liệu Địa giới hành chính Việt Nam
 * (63 Tỉnh/Thành phố, toàn bộ Quận/Huyện/Thị xã và Phường/Xã/Thị trấn) vào MariaDB `batdongsan`
 * đồng thời xuất file SQL seed độc lập `database/seeds/vietnam_administrative_divisions.sql`.
 *
 * Bảo toàn tuyệt đối:
 * - provinces ID 1..3 (TP. Hồ Chí Minh, Hà Nội, Đà Nẵng)
 * - districts ID 1..6 (Quận 1, Quận 3, TP. Thủ Đức, Quận Ba Đình, Quận Cầu Giấy, Quận Hải Châu)
 * - wards ID 1..8 (gắn với 18 bản ghi properties hiện có)
 */

define('ROOT', dirname(__DIR__, 2));
$config = require ROOT . '/config/config.php';
require ROOT . '/app/helpers/functions.php';
require ROOT . '/app/core/Database.php';

$jsonCachePath = 'C:/Users/Admin/AppData/Local/Temp/vn_admin_depth3.json';
if (!is_file($jsonCachePath)) {
    $jsonCachePath = sys_get_temp_dir() . '/vn_admin_depth3.json';
}
$sqlSeedPath   = __DIR__ . '/vietnam_administrative_divisions.sql';
$apiUrl        = 'https://provinces.open-api.vn/api/?depth=3';

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Nếu chạy ở chế độ chỉ nạp từ file SQL có sẵn (offline mode)
if (in_array('--from-sql', $argv ?? [], true) && is_file($sqlSeedPath)) {
    echo "[INFO] Đang nạp dữ liệu từ file SQL seed offline: {$sqlSeedPath}\n";
    $sqlContent = (string)file_get_contents($sqlSeedPath);
    $pdo->exec($sqlContent);
    echo "[OK] Hoàn tất nạp dữ liệu từ SQL seed.\n";
    exit(0);
}

// 1. Tải hoặc đọc dữ liệu JSON 63 Tỉnh/Thành phố (depth=3)
$rawJson = '';
if (is_file($jsonCachePath) && filesize($jsonCachePath) > 500000) {
    echo "[INFO] Sử dụng dữ liệu JSON đã tải tại: {$jsonCachePath}\n";
    $rawJson = (string)file_get_contents($jsonCachePath);
} else {
    echo "[INFO] Đang tải dữ liệu hành chính 63 tỉnh/thành từ {$apiUrl} ...\n";
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header'  => "User-Agent: BDSViet-AdminSeeder/1.0\r\nAccept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);
    $rawJson = (string)@file_get_contents($apiUrl, false, $ctx);
    if ($rawJson !== '') {
        file_put_contents($jsonCachePath, $rawJson);
    }
}

if ($rawJson === '') {
    if (is_file($sqlSeedPath)) {
        echo "[WARN] Không có kết nối mạng, chuyển sang nạp từ file SQL seed offline...\n";
        $pdo->exec((string)file_get_contents($sqlSeedPath));
        exit(0);
    }
    fwrite(STDERR, "[ERROR] Không thể tải dữ liệu hành chính và chưa có file SQL seed.\n");
    exit(1);
}

$apiProvinces = json_decode($rawJson, true);
if (!is_array($apiProvinces) || count($apiProvinces) < 63) {
    fwrite(STDERR, "[ERROR] Dữ liệu JSON không hợp lệ hoặc không đủ 63 tỉnh/thành.\n");
    exit(1);
}

// 2. Định nghĩa ánh xạ cố định để bảo toàn khóa ngoại cho 18 bất động sản hiện có
$fixedProvinces = [
    1 => ['name' => 'TP. Hồ Chí Minh', 'api_code' => 79],
    2 => ['name' => 'Hà Nội',          'api_code' => 1],
    3 => ['name' => 'Đà Nẵng',         'api_code' => 48],
];

$fixedDistricts = [
    1 => ['province_id' => 1, 'name' => 'Quận 1',        'api_code' => 760],
    2 => ['province_id' => 1, 'name' => 'Quận 3',        'api_code' => 770],
    3 => ['province_id' => 1, 'name' => 'TP. Thủ Đức',   'api_code' => 769],
    4 => ['province_id' => 2, 'name' => 'Quận Ba Đình',  'api_code' => 1],
    5 => ['province_id' => 2, 'name' => 'Quận Cầu Giấy', 'api_code' => 5],
    6 => ['province_id' => 3, 'name' => 'Quận Hải Châu', 'api_code' => 492],
];

$fixedWards = [
    1 => ['district_id' => 1, 'name' => 'Phường Bến Nghé',   'api_code' => 26740],
    2 => ['district_id' => 1, 'name' => 'Phường Bến Thành',  'api_code' => 26743],
    3 => ['district_id' => 1, 'name' => 'Phường Đa Kao',     'api_code' => 26737],
    4 => ['district_id' => 2, 'name' => 'Phường Võ Thị Sáu', 'api_code' => 27139],
    5 => ['district_id' => 3, 'name' => 'Phường Thảo Điền',  'api_code' => 27088],
    6 => ['district_id' => 4, 'name' => 'Phường Điện Biên',  'api_code' => 19],
    7 => ['district_id' => 5, 'name' => 'Phường Dịch Vọng',  'api_code' => 166],
    8 => ['district_id' => 6, 'name' => 'Phường Hải Châu 1', 'api_code' => -8],
];

$apiCodeToProvinceId = [];
$provincesRows = [];
foreach ($fixedProvinces as $pid => $info) {
    $provincesRows[$pid] = ['id' => $pid, 'name' => $info['name']];
    $apiCodeToProvinceId[$info['api_code']] = $pid;
}

// Sắp xếp các tỉnh/thành còn lại theo tên chuẩn tiếng Việt
$remainingProvinces = array_filter($apiProvinces, static function (array $p) use ($apiCodeToProvinceId): bool {
    return !isset($apiCodeToProvinceId[(int)$p['code']]);
});
usort($remainingProvinces, static function (array $a, array $b): int {
    return strcmp((string)$a['name'], (string)$b['name']);
});

$nextProvinceId = 4;
foreach ($remainingProvinces as $p) {
    $code = (int)$p['code'];
    $name = trim((string)$p['name']);
    $provincesRows[$nextProvinceId] = ['id' => $nextProvinceId, 'name' => $name];
    $apiCodeToProvinceId[$code] = $nextProvinceId;
    $nextProvinceId++;
}

// Xây dựng danh sách Quận/Huyện
$apiCodeToDistrictId = [];
$districtsRows = [];
foreach ($fixedDistricts as $did => $info) {
    $districtsRows[$did] = [
        'id'          => $did,
        'province_id' => $info['province_id'],
        'name'        => $info['name'],
    ];
    $apiCodeToDistrictId[$info['api_code']] = $did;
}

// Sắp xếp provinces theo province_id trong DB để thứ tự district_id gọn gàng
$apiProvincesByDbId = [];
foreach ($apiProvinces as $p) {
    $dbPid = $apiCodeToProvinceId[(int)$p['code']];
    $apiProvincesByDbId[$dbPid] = $p;
}
ksort($apiProvincesByDbId);

$nextDistrictId = 7;
foreach ($apiProvincesByDbId as $dbPid => $p) {
    $districts = $p['districts'] ?? [];
    foreach ($districts as $d) {
        $dCode = (int)$d['code'];
        if (isset($apiCodeToDistrictId[$dCode])) {
            continue;
        }
        $dName = trim((string)$d['name']);
        $districtsRows[$nextDistrictId] = [
            'id'          => $nextDistrictId,
            'province_id' => $dbPid,
            'name'        => $dName,
        ];
        $apiCodeToDistrictId[$dCode] = $nextDistrictId;
        $nextDistrictId++;
    }
}

// Xây dựng danh sách Phường/Xã
$usedWardApiCodes = [];
$wardsRows = [];
foreach ($fixedWards as $wid => $info) {
    $wardsRows[$wid] = [
        'id'          => $wid,
        'district_id' => $info['district_id'],
        'name'        => $info['name'],
    ];
    if ($info['api_code'] > 0) {
        $usedWardApiCodes[$info['api_code']] = $wid;
    }
}

$nextWardId = 9;
foreach ($apiProvincesByDbId as $dbPid => $p) {
    foreach ($p['districts'] ?? [] as $d) {
        $dCode = (int)$d['code'];
        $dbDid = $apiCodeToDistrictId[$dCode];
        foreach ($d['wards'] ?? [] as $w) {
            $wCode = (int)$w['code'];
            if (isset($usedWardApiCodes[$wCode])) {
                continue;
            }
            $wName = trim((string)$w['name']);
            // Tránh trùng tên với 8 phường gốc trong cùng quận
            $isDuplicateFixed = false;
            foreach ($fixedWards as $fw) {
                if ($fw['district_id'] === $dbDid && mb_strtolower($fw['name'], 'UTF-8') === mb_strtolower($wName, 'UTF-8')) {
                    $isDuplicateFixed = true;
                    break;
                }
            }
            if ($isDuplicateFixed) {
                continue;
            }
            $wardsRows[$nextWardId] = [
                'id'          => $nextWardId,
                'district_id' => $dbDid,
                'name'        => $wName,
            ];
            $usedWardApiCodes[$wCode] = $nextWardId;
            $nextWardId++;
        }
    }
}

echo sprintf(
    "[INFO] Đã chuẩn hóa: %d Tỉnh/Thành phố, %d Quận/Huyện/Thị xã, %d Phường/Xã/Thị trấn.\n",
    count($provincesRows),
    count($districtsRows),
    count($wardsRows)
);

// 3. Nạp vào MariaDB batdongsan an toàn (không xóa ID gốc 1..3, 1..6, 1..8)
$pdo->beginTransaction();
try {
    $pdo->exec("DELETE FROM wards WHERE id > 8");
    $pdo->exec("DELETE FROM districts WHERE id > 6");
    $pdo->exec("DELETE FROM provinces WHERE id > 3");

    $stmtProvince = $pdo->prepare(
        "INSERT INTO provinces (id, name) VALUES (:id, :name)
         ON DUPLICATE KEY UPDATE name = VALUES(name)"
    );
    foreach ($provincesRows as $row) {
        $stmtProvince->execute([
            ':id'   => $row['id'],
            ':name' => $row['name'],
        ]);
    }

    $stmtDistrict = $pdo->prepare(
        "INSERT INTO districts (id, province_id, name) VALUES (:id, :province_id, :name)
         ON DUPLICATE KEY UPDATE province_id = VALUES(province_id), name = VALUES(name)"
    );
    foreach ($districtsRows as $row) {
        $stmtDistrict->execute([
            ':id'          => $row['id'],
            ':province_id' => $row['province_id'],
            ':name'        => $row['name'],
        ]);
    }

    // Batch insert cho wards để tối ưu tốc độ
    $wardChunks = array_chunk(array_values($wardsRows), 500);
    foreach ($wardChunks as $chunk) {
        $placeholders = [];
        $params       = [];
        foreach ($chunk as $row) {
            $placeholders[] = '(?, ?, ?)';
            $params[]       = $row['id'];
            $params[]       = $row['district_id'];
            $params[]       = $row['name'];
        }
        $sql = "INSERT INTO wards (id, district_id, name) VALUES " . implode(', ', $placeholders)
             . " ON DUPLICATE KEY UPDATE district_id = VALUES(district_id), name = VALUES(name)";
        $stmtWardBatch = $pdo->prepare($sql);
        $stmtWardBatch->execute($params);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "[ERROR] Lỗi khi nạp vào MariaDB: " . $e->getMessage() . "\n");
    exit(1);
}

// 4. Đảm bảo các Index tối ưu tốc độ truy vấn phân cấp địa lý
$indexDefinitions = [
    ['table' => 'provinces', 'name' => 'idx_provinces_name',          'ddl' => 'ALTER TABLE provinces ADD INDEX idx_provinces_name (name)'],
    ['table' => 'districts', 'name' => 'idx_districts_province_id',   'ddl' => 'ALTER TABLE districts ADD INDEX idx_districts_province_id (province_id)'],
    ['table' => 'districts', 'name' => 'idx_districts_province_name', 'ddl' => 'ALTER TABLE districts ADD INDEX idx_districts_province_name (province_id, name)'],
    ['table' => 'wards',     'name' => 'idx_wards_district_id',       'ddl' => 'ALTER TABLE wards ADD INDEX idx_wards_district_id (district_id)'],
    ['table' => 'wards',     'name' => 'idx_wards_district_name',     'ddl' => 'ALTER TABLE wards ADD INDEX idx_wards_district_name (district_id, name)'],
];

foreach ($indexDefinitions as $idx) {
    $checkStmt = $pdo->prepare("SHOW INDEX FROM {$idx['table']} WHERE Key_name = ?");
    $checkStmt->execute([$idx['name']]);
    if (!$checkStmt->fetch()) {
        $pdo->exec($idx['ddl']);
        echo "[INDEX] Đã tạo index {$idx['name']} trên bảng {$idx['table']}.\n";
    }
}

// 5. Xuất file SQL seed độc lập `database/seeds/vietnam_administrative_divisions.sql`
$sqlLines = [];
$sqlLines[] = "-- =============================================================================";
$sqlLines[] = "-- DỮ LIỆU ĐỊA GIỚI HÀNH CHÍNH VIỆT NAM TOÀN QUỐC (63 TỈNH/THÀNH - QUẬN/HUYỆN - PHƯỜNG/XÃ)";
$sqlLines[] = "-- Chuẩn mã hóa: UTF-8 (utf8mb4_unicode_ci)";
$sqlLines[] = "-- Bảo toàn nguyên vẹn khóa ngoại ID 1..3 (provinces), 1..6 (districts), 1..8 (wards)";
$sqlLines[] = "-- =============================================================================";
$sqlLines[] = "SET NAMES utf8mb4;";
$sqlLines[] = "SET FOREIGN_KEY_CHECKS = 0;";
$sqlLines[] = "";
$sqlLines[] = "-- Xóa dữ liệu mở rộng cũ (giữ nguyên các bản ghi gốc đang gắn khóa ngoại với bảng properties)";
$sqlLines[] = "DELETE FROM `wards` WHERE `id` > 8;";
$sqlLines[] = "DELETE FROM `districts` WHERE `id` > 6;";
$sqlLines[] = "DELETE FROM `provinces` WHERE `id` > 3;";
$sqlLines[] = "";
$sqlLines[] = "-- 1. Bảng provinces (" . count($provincesRows) . " Tỉnh/Thành phố)";
$pValues = [];
foreach ($provincesRows as $row) {
    $pValues[] = sprintf("(%d, %s)", $row['id'], $pdo->quote($row['name']));
}
$sqlLines[] = "INSERT INTO `provinces` (`id`, `name`) VALUES\n  " . implode(",\n  ", $pValues)
            . "\nON DUPLICATE KEY UPDATE `name` = VALUES(`name`);";
$sqlLines[] = "";

$sqlLines[] = "-- 2. Bảng districts (" . count($districtsRows) . " Quận/Huyện/Thị xã/Thành phố thuộc tỉnh)";
$dChunks = array_chunk(array_values($districtsRows), 250);
foreach ($dChunks as $chunk) {
    $dValues = [];
    foreach ($chunk as $row) {
        $dValues[] = sprintf("(%d, %d, %s)", $row['id'], $row['province_id'], $pdo->quote($row['name']));
    }
    $sqlLines[] = "INSERT INTO `districts` (`id`, `province_id`, `name`) VALUES\n  " . implode(",\n  ", $dValues)
                . "\nON DUPLICATE KEY UPDATE `province_id` = VALUES(`province_id`), `name` = VALUES(`name`);";
}
$sqlLines[] = "";

$sqlLines[] = "-- 3. Bảng wards (" . count($wardsRows) . " Phường/Xã/Thị trấn)";
$wChunks = array_chunk(array_values($wardsRows), 500);
foreach ($wChunks as $chunk) {
    $wValues = [];
    foreach ($chunk as $row) {
        $wValues[] = sprintf("(%d, %d, %s)", $row['id'], $row['district_id'], $pdo->quote($row['name']));
    }
    $sqlLines[] = "INSERT INTO `wards` (`id`, `district_id`, `name`) VALUES\n  " . implode(",\n  ", $wValues)
                . "\nON DUPLICATE KEY UPDATE `district_id` = VALUES(`district_id`), `name` = VALUES(`name`);";
}
$sqlLines[] = "";
$sqlLines[] = "SET FOREIGN_KEY_CHECKS = 1;";
$sqlLines[] = "";

file_put_contents($sqlSeedPath, implode("\n", $sqlLines));
echo "[OK] Đã xuất file SQL seed tại: {$sqlSeedPath} (" . number_format((float)filesize($sqlSeedPath) / 1024, 2) . " KB)\n";
