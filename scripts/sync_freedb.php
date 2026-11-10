<?php
/**
 * Script đồng bộ cấu trúc Database và Dữ liệu từ xa lên FreeDB (freedb_3R21fnCY)
 * Hỗ trợ các tham số:
 *   --dry-run      : Chỉ kiểm tra và hiển thị các lệnh SQL sẽ chạy, không thay đổi DB
 *   --schema       : Đồng bộ cấu trúc bảng, cột, view, index còn thiếu
 *   --locations    : Đồng bộ dữ liệu địa giới hành chính (63 tỉnh, 696 quận/huyện, phường/xã)
 *   --sample-data  : Đồng bộ thêm 5 tin đăng mẫu (IDs 14..18 có tọa độ Leaflet Map)
 *   --sync-titles  : Đồng bộ cập nhật tiêu đề & địa chỉ chuẩn SEO (thay tên quận bằng tên phường)
 *   --all          : Thực hiện toàn bộ các bước trên
 */

$host = 'sql.freedb.tech';
$port = 3306;
$db   = 'freedb_3R21fnCY';
$user = 'u_xyDBBF';
$pass = 'VIQFQDHFadSj';

$options = getopt('', ['dry-run', 'schema', 'locations', 'sample-data', 'sync-titles', 'all', 'help']);

if (isset($options['help']) || empty($options)) {
    echo "========================================================================\n";
    echo "  KỊCH BẢN ĐỒNG BỘ CƠ SỞ DỮ LIỆU FREEDB (freedb_3R21fnCY)\n";
    echo "========================================================================\n";
    echo "Cách dùng: php scripts/sync_freedb.php [tùy_chọn]\n\n";
    echo "Các tùy chọn:\n";
    echo "  --dry-run      Chạy thử nghiệm (kiểm tra hiện trạng, không ghi vào DB)\n";
    echo "  --schema       Đồng bộ cấu trúc bảng (thêm cột expired_at, bảng notifications, password_resets, view property_reports, index)\n";
    echo "  --locations    Đồng bộ 63 tỉnh thành & quận huyện chuẩn toàn quốc\n";
    echo "  --sample-data  Bổ sung 5 bất động sản mẫu (IDs 14..18) có tọa độ bản đồ\n";
    echo "  --sync-titles  Đồng bộ cập nhật tiêu đề & địa chỉ bài đăng (xóa chữ quận, thay bằng phường)\n";
    echo "  --all          Đồng bộ toàn bộ (schema + locations + sample-data + sync-titles)\n";
    echo "========================================================================\n";
    exit(0);
}

$isDryRun = isset($options['dry-run']);
$doSchema = isset($options['schema']) || isset($options['all']);
$doLocations = isset($options['locations']) || isset($options['all']);
$doSampleData = isset($options['sample-data']) || isset($options['all']);
$doSyncTitles = isset($options['sync-titles']) || isset($options['all']);

echo "=== KẾT NỐI FREEDB ($host:$port / $db) ===\n";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    echo "[OK] Kết nối MySQL FreeDB thành công!\n";
} catch (PDOException $e) {
    echo "[LỖI] Không thể kết nối FreeDB: " . $e->getMessage() . "\n";
    exit(1);
}

if ($isDryRun) {
    echo "\n>>> ĐANG CHẠY Ở CHẾ ĐỘ DRY-RUN (KHÔNG THỰC HIỆN THAY ĐỔI THỰC TẾ) <<<\n\n";
}

// Helper functions
function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
    return (bool)$stmt->fetch();
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column));
    return (bool)$stmt->fetch();
}

function indexExists(PDO $pdo, string $table, string $indexName): bool {
    $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($indexName));
    return (bool)$stmt->fetch();
}

// -------------------------------------------------------------
// PHA 1: ĐỒNG BỘ CẤU TRÚC (SCHEMA)
// -------------------------------------------------------------
if ($doSchema) {
    echo "--- 1. ĐỒNG BỘ CẤU TRÚC BẢNG & CỘT & VIEW & INDEX ---\n";

    // 1.1. Cột expired_at và slug trong bảng properties
    echo "1.1. Kiểm tra cột `expired_at` và `slug` trong bảng `properties`:\n";
    if (!columnExists($pdo, 'properties', 'slug')) {
        $sqlSlug = "ALTER TABLE `properties` ADD COLUMN `slug` VARCHAR(255) NULL DEFAULT NULL AFTER `title`";
        echo "  -> Thiếu cột `slug`! SQL: $sqlSlug\n";
        if (!$isDryRun) {
            $pdo->exec($sqlSlug);
            echo "  [OK] Đã thêm cột `slug` vào bảng `properties`.\n";
        }
    } else {
        echo "  [PASS] Cột `slug` đã tồn tại.\n";
    }
    if (!columnExists($pdo, 'properties', 'expired_at')) {
        $sql = "ALTER TABLE `properties` ADD COLUMN `expired_at` DATETIME NULL AFTER `views`";
        echo "  -> Thiếu cột `expired_at`! SQL: $sql\n";
        if (!$isDryRun) {
            $pdo->exec($sql);
            // Cập nhật ngày hết hạn mặc định 30 ngày cho các tin đang approved
            $updateSql = "UPDATE `properties` SET `expired_at` = DATE_ADD(`created_at`, INTERVAL 30 DAY) WHERE `expired_at` IS NULL AND `status` = 'approved'";
            $affected = $pdo->exec($updateSql);
            echo "  [OK] Đã thêm cột `expired_at` và cập nhật $affected tin approved (+30 ngày).\n";
        }
    } else {
        echo "  [PASS] Cột `expired_at` đã tồn tại.\n";
    }

    // 1.2. Các cột còn thiếu trong bảng reports: description, admin_notes, updated_at
    echo "1.2. Kiểm tra các cột trong bảng `reports`:\n";
    $reportCols = [
        'description' => "ALTER TABLE `reports` ADD COLUMN `description` TEXT NULL AFTER `reason`",
        'admin_notes' => "ALTER TABLE `reports` ADD COLUMN `admin_notes` TEXT NULL AFTER `status`",
        'updated_at'  => "ALTER TABLE `reports` ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    ];
    foreach ($reportCols as $col => $sql) {
        if (!columnExists($pdo, 'reports', $col)) {
            echo "  -> Thiếu cột `$col`! SQL: $sql\n";
            if (!$isDryRun) {
                $pdo->exec($sql);
                echo "  [OK] Đã thêm cột `$col` vào bảng `reports`.\n";
            }
        } else {
            echo "  [PASS] Cột `$col` đã tồn tại trong `reports`.\n";
        }
    }

    // 1.3. Bảng password_resets
    echo "1.3. Kiểm tra bảng `password_resets`:\n";
    if (!tableExists($pdo, 'password_resets')) {
        $sql = "CREATE TABLE `password_resets` (
            `email` VARCHAR(191) NOT NULL,
            `token` VARCHAR(191) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_password_resets_email` (`email`),
            INDEX `idx_password_resets_token` (`token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        echo "  -> Thiếu bảng `password_resets`! SQL: CREATE TABLE ...\n";
        if (!$isDryRun) {
            $pdo->exec($sql);
            echo "  [OK] Đã tạo bảng `password_resets`.\n";
        }
    } else {
        echo "  [PASS] Bảng `password_resets` đã tồn tại.\n";
    }

    // 1.4. Bảng notifications
    echo "1.4. Kiểm tra bảng `notifications`:\n";
    if (!tableExists($pdo, 'notifications')) {
        $sql = "CREATE TABLE `notifications` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT,
            `link` VARCHAR(255) NULL,
            `type` VARCHAR(30) NOT NULL DEFAULT 'info',
            `is_read` TINYINT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_notifications_user_read` (`user_id`, `is_read`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        echo "  -> Thiếu bảng `notifications`! SQL: CREATE TABLE ...\n";
        if (!$isDryRun) {
            $pdo->exec($sql);
            echo "  [OK] Đã tạo bảng `notifications`.\n";
        }
    } else {
        echo "  [PASS] Bảng `notifications` đã tồn tại.\n";
    }

    // 1.5. View property_reports
    echo "1.5. Kiểm tra View `property_reports`:\n";
    $viewSql = "CREATE OR REPLACE VIEW `property_reports` AS
        SELECT id, property_id, reporter_id AS user_id, reason, description, status, admin_notes, created_at, updated_at
        FROM reports";
    echo "  -> Tạo/cập nhật View `property_reports`:\n";
    if (!$isDryRun) {
        $pdo->exec($viewSql);
        echo "  [OK] View `property_reports` đã sẵn sàng.\n";
    } else {
        echo "  [DRY-RUN] Sẽ chạy: $viewSql\n";
    }

    // 1.5b. Cấu trúc bảng wards theo mô hình 2 cấp (province_id NOT NULL, district_id NULL DEFAULT NULL)
    echo "1.5b. Kiểm tra cấu trúc bảng `wards` (Mô hình 2 cấp Tỉnh/Thành -> Phường):\n";
    if (!columnExists($pdo, 'wards', 'province_id')) {
        $sql = "ALTER TABLE `wards` ADD COLUMN `province_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`";
        echo "  -> Thiếu cột `province_id` trong `wards`! SQL: $sql\n";
        if (!$isDryRun) {
            $pdo->exec($sql);
            echo "  [OK] Đã thêm cột `province_id` vào bảng `wards`.\n";
        }
    } else {
        echo "  [PASS] Cột `province_id` đã tồn tại trong `wards`.\n";
    }
    if (!$isDryRun) {
        $pdo->exec("ALTER TABLE `wards` MODIFY COLUMN `district_id` INT UNSIGNED NULL DEFAULT NULL");
        echo "  [OK] Đã cập nhật `district_id` cho phép NULL trong bảng `wards`.\n";
    }

    // 1.6. Tạo các index còn thiếu
    echo "1.6. Kiểm tra các index hiệu năng:\n";
    $indexes = [
        ['table' => 'properties',      'name' => 'idx_prop_search',         'cols' => '`status`, `listing_type`, `price`'],
        ['table' => 'properties',      'name' => 'idx_prop_status_created', 'cols' => '`status`, `created_at` DESC'],
        ['table' => 'properties',      'name' => 'idx_prop_status_area',    'cols' => '`status`, `listing_type`, `area`'],
        ['table' => 'property_images', 'name' => 'idx_prop_img_cover',      'cols' => '`property_id`, `is_cover` DESC, `id`'],
        ['table' => 'reports',         'name' => 'idx_report_status',       'cols' => '`status`'],
        ['table' => 'reports',         'name' => 'idx_report_property',     'cols' => '`property_id`'],
        ['table' => 'provinces',       'name' => 'idx_provinces_name',      'cols' => '`name`'],
        ['table' => 'districts',       'name' => 'idx_districts_province_id','cols' => '`province_id`'],
        ['table' => 'districts',       'name' => 'idx_districts_province_name', 'cols' => '`province_id`, `name`'],
        ['table' => 'wards',           'name' => 'idx_wards_province_name',  'cols' => '`province_id`, `name`'],
        ['table' => 'wards',           'name' => 'idx_wards_district_id',    'cols' => '`district_id`'],
        ['table' => 'wards',           'name' => 'idx_wards_district_name',  'cols' => '`district_id`, `name`'],
    ];

    foreach ($indexes as $idx) {
        $tbl = $idx['table'];
        $name = $idx['name'];
        $cols = $idx['cols'];
        if (!indexExists($pdo, $tbl, $name)) {
            $sql = "ALTER TABLE `$tbl` ADD INDEX `$name` ($cols)";
            echo "  -> Thiếu index `$name` trên `$tbl`! SQL: $sql\n";
            if (!$isDryRun) {
                try {
                    $pdo->exec($sql);
                    echo "  [OK] Đã tạo index `$name` trên `$tbl`.\n";
                } catch (Exception $e) {
                    echo "  [CẢNH BÁO] Không thể tạo index `$name`: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "  [PASS] Index `$name` trên `$tbl` đã tồn tại.\n";
        }
    }
}

// -------------------------------------------------------------
// PHA 2: ĐỒNG BỘ ĐỊA GIỚI HÀNH CHÍNH (3.308 PHƯỜNG/XÃ CHUẨN WIKIPEDIA - 63 TỈNH/THÀNH)
// -------------------------------------------------------------
if ($doLocations) {
    echo "\n--- 2. ĐỒNG BỘ 3.308 PHƯỜNG/XÃ CHUẨN WIKIPEDIA (709 PHƯỜNG + 2.599 XÃ TRÊN 63 TỈNH/THÀNH) ---\n";
    $wikiWardsSqlPath = __DIR__ . '/../database/seeds/wikipedia_wards_2tier.sql';
    if (!file_exists($wikiWardsSqlPath)) {
        echo "  [LỖI] Không tìm thấy file: $wikiWardsSqlPath\n";
    } else {
        echo "  File nguồn: $wikiWardsSqlPath (" . round(filesize($wikiWardsSqlPath) / 1024, 1) . " KB)\n";
        if ($isDryRun) {
            echo "  [DRY-RUN] Sẽ nạp dữ liệu 3.308 phường/xã từ $wikiWardsSqlPath vào FreeDB.\n";
        } else {
            echo "  Đang đọc và thực thi script SQL 3.308 phường/xã Wikipedia...\n";
            if (!columnExists($pdo, 'wards', 'province_id')) {
                $pdo->exec("ALTER TABLE `wards` ADD COLUMN `province_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`");
            }
            if (!columnExists($pdo, 'properties', 'slug')) {
                $pdo->exec("ALTER TABLE `properties` ADD COLUMN `slug` VARCHAR(255) NULL DEFAULT NULL AFTER `title`");
            }
            $pdo->exec("ALTER TABLE `wards` MODIFY COLUMN `district_id` INT UNSIGNED NULL DEFAULT NULL");

            $sqlContent = file_get_contents($wikiWardsSqlPath);
            $pdo->exec("SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS = 0;");

            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            $count = 0;
            $start = microtime(true);
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $elapsed = round(microtime(true) - $start, 2);
            echo "  [OK] Đã nạp thành công $count câu lệnh SQL trong {$elapsed}s!\n";

            // Kiểm tra số lượng sau khi nạp
            $pCount      = (int)$pdo->query("SELECT COUNT(*) FROM provinces")->fetchColumn();
            $wCount      = (int)$pdo->query("SELECT COUNT(*) FROM wards")->fetchColumn();
            $phuongCount = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Phường %'")->fetchColumn();
            $xaCount     = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Xã %'")->fetchColumn();
            $ttCount     = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Thị trấn %'")->fetchColumn();
            $covCount    = (int)$pdo->query("SELECT COUNT(DISTINCT province_id) FROM wards")->fetchColumn();
            $hcmCount    = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE province_id = 1")->fetchColumn();
            $hnCount     = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE province_id = 2")->fetchColumn();
            $dnCount     = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE province_id = 3")->fetchColumn();
            $bdCount     = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE province_id = 12")->fetchColumn();
            $glCount     = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE province_id = 20")->fetchColumn();
            echo "  Kết quả sau nạp: $pCount tỉnh/thành ($covCount/63 có dữ liệu), $wCount wards (Phường: $phuongCount, Xã: $xaCount, Thị trấn: $ttCount).\n";
            echo "  Chi tiết mẫu: TP.HCM=$hcmCount, Hà Nội=$hnCount, Đà Nẵng=$dnCount, Bình Định=$bdCount, Gia Lai=$glCount.\n";
        }
    }
}

// -------------------------------------------------------------
// PHA 3: ĐỒNG BỘ TIN ĐĂNG MẪU BỔ SUNG (IDs 14..18)
// -------------------------------------------------------------
if ($doSampleData) {
    echo "\n--- 3. BỔ SUNG BẤT ĐỘNG SẢN MẪU (IDs 14..18 CÓ TỌA ĐỘ BẢN ĐỒ) ---\n";
    $sampleSqlPath = __DIR__ . '/../database/add_sample_properties.sql';
    if (!file_exists($sampleSqlPath)) {
        echo "  [LỖI] Không tìm thấy file: $sampleSqlPath\n";
    } else {
        echo "  File nguồn: $sampleSqlPath\n";
        if ($isDryRun) {
            echo "  [DRY-RUN] Sẽ chạy script $sampleSqlPath trên FreeDB.\n";
        } else {
            $sqlContent = file_get_contents($sampleSqlPath);
            $pdo->exec("SET NAMES utf8mb4;");
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            $count = 0;
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            echo "  [OK] Đã thực thi $count câu lệnh từ $sampleSqlPath.\n";
            $pCount = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'approved'")->fetchColumn();
            echo "  Tổng số tin approved hiện tại: $pCount tin.\n";
        }
    }
}

// -------------------------------------------------------------
// PHA 4: ĐỒNG BỘ CẬP NHẬT TIÊU ĐỀ & ĐỊA CHỈ (XÓA CHỮ QUẬN, THAY BẰNG PHƯỜNG)
// -------------------------------------------------------------
if ($doSyncTitles) {
    echo "\n--- 4. ĐỒNG BỘ CẬP NHẬT TIÊU ĐỀ & ĐỊA CHỈ BÀI ĐĂNG (CHUẨN HÓA PHƯỜNG) ---\n";
    $updateSqlPath = __DIR__ . '/../database/seeds/update_property_titles_2tier.sql';
    if (!file_exists($updateSqlPath)) {
        echo "  [LỖI] Không tìm thấy file: $updateSqlPath\n";
    } else {
        echo "  File nguồn: $updateSqlPath\n";
        if ($isDryRun) {
            echo "  [DRY-RUN] Sẽ thực thi các câu lệnh UPDATE từ $updateSqlPath trên FreeDB.\n";
            $sqlContent = file_get_contents($updateSqlPath);
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    echo "    SQL: " . substr($stmt, 0, 100) . "...\n";
                }
            }
        } else {
            if (!columnExists($pdo, 'properties', 'slug')) {
                $pdo->exec("ALTER TABLE `properties` ADD COLUMN `slug` VARCHAR(255) NULL DEFAULT NULL AFTER `title`");
            }
            $sqlContent = file_get_contents($updateSqlPath);
            $pdo->exec("SET NAMES utf8mb4;");
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            $count = 0;
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            echo "  [OK] Đã thực thi $count câu lệnh UPDATE tiêu đề/địa chỉ lên FreeDB thành công!\n";
        }
    }
}

echo "\n================= HOÀN TẤT ĐỒNG BỘ =================\n";
