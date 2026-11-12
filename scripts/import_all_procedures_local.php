<?php
declare(strict_types=1);

/**
 * Nạp toàn bộ Stored Procedures từ database/procedures/*.sql vào MariaDB local (batdongsan).
 * Chạy qua CLI: php scripts/import_all_procedures_local.php
 */

$dbName = 'batdongsan';
$dsn = "mysql:host=127.0.0.1;dbname={$dbName};charset=utf8mb4";
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[ERROR] Kết nối MariaDB thất bại: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$procedureDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'procedures';
$files = glob($procedureDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
sort($files);

if (empty($files)) {
    fwrite(STDERR, "[ERROR] Không tìm thấy file SQL nào trong: {$procedureDir}" . PHP_EOL);
    exit(1);
}

/**
 * Tách các câu lệnh SQL có sử dụng DELIMITER $$ / DELIMITER ;
 *
 * @return string[]
 */
function parseProcedureSqlStatements(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $lines = explode("\n", $sql);

    $delimiter = ';';
    $buffer = '';
    $statements = [];

    $flush = static function () use (&$buffer, &$statements): void {
        $stmt = trim($buffer);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
        $buffer = '';
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (preg_match('/^DELIMITER\s+(\S+)$/i', $trimmed, $matches)) {
            $flush();
            $delimiter = $matches[1];
            continue;
        }

        if ($buffer === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
            continue;
        }

        $buffer .= $line . "\n";
        $rtrimmed = rtrim($buffer);
        if ($delimiter !== '' && str_ends_with($rtrimmed, $delimiter)) {
            $buffer = substr($rtrimmed, 0, -strlen($delimiter));
            $flush();
        }
    }

    $flush();
    return $statements;
}

$totalStatements = 0;
$createdCount = 0;

foreach ($files as $file) {
    $basename = basename($file);
    $content = file_get_contents($file);
    if ($content === false) {
        fwrite(STDERR, "[ERROR] Không đọc được file: {$file}" . PHP_EOL);
        exit(1);
    }

    $statements = parseProcedureSqlStatements($content);
    $fileCreated = 0;

    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            $totalStatements++;
            if (preg_match('/^\s*CREATE\s+PROCEDURE\b/i', $statement)) {
                $createdCount++;
                $fileCreated++;
            }
        } catch (PDOException $e) {
            fwrite(
                STDERR,
                "[ERROR] {$basename} (lệnh #" . ($index + 1) . "): " . $e->getMessage() . PHP_EOL
                . "--- SQL ---" . PHP_EOL . $statement . PHP_EOL
            );
            exit(1);
        }
    }

    echo sprintf("[OK] %-32s (%d statements, %d procedures created)\n", $basename, count($statements), $fileCreated);
}

$routinesStmt = $pdo->prepare(
    "SELECT ROUTINE_NAME
     FROM information_schema.ROUTINES
     WHERE ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'PROCEDURE'
     ORDER BY ROUTINE_NAME ASC"
);
$routinesStmt->execute([$dbName]);
$routines = $routinesStmt->fetchAll(PDO::FETCH_COLUMN);

echo PHP_EOL . "Tổng kết: đã xử lý " . count($files) . " file, {$totalStatements} câu lệnh, tạo {$createdCount} procedure." . PHP_EOL;
echo "Số procedure hiện có trong schema '{$dbName}': " . count($routines) . PHP_EOL;

if (!in_array('sp_catalog_sorted', $routines, true)) {
    fwrite(STDERR, "[ERROR] Thiếu procedure bắt buộc: sp_catalog_sorted" . PHP_EOL);
    exit(1);
}

echo "[VERIFIED] sp_catalog_sorted đã tồn tại trong {$dbName}." . PHP_EOL;
echo "Danh sách ROUTINE_NAME: " . implode(', ', $routines) . PHP_EOL;
