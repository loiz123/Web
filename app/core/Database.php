<?php
require_once __DIR__ . '/RoutineResult.php';
// Kết nối PDO dùng chung (singleton).
class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            if (!empty($c['ssl_ca'])) $options[PDO::MYSQL_ATTR_SSL_CA] = $c['ssl_ca'];
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        }
        return self::$pdo;
    }

    private static int $lastProcedureInsertId = 0;

    /** Only identifiers from application code are accepted; values are always bound. */
    public static function call(string $procedure, array $args = []): array
    {
        if (!preg_match('/^sp_[a-z0-9_]+$/D', $procedure)) throw new InvalidArgumentException('Invalid procedure name.');
        $marks = implode(',', array_fill(0, count($args), '?'));
        $stmt = null;
        try {
            $stmt = self::connection()->prepare("CALL `$procedure`($marks)");
            $stmt->execute(array_values($args));
            $rows = $stmt->columnCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            while ($stmt->nextRowset()) { if ($stmt->columnCount() > 0) $stmt->fetchAll(PDO::FETCH_ASSOC); }
            if (isset($rows[0]['__insert_id'])) self::$lastProcedureInsertId = (int)$rows[0]['__insert_id'];
            return $rows;
        } catch (PDOException $error) {
            if ((int)($error->errorInfo[1] ?? 0) === 1305) {
                throw new RuntimeException('Missing required procedure ' . $procedure . '. Import deploy/procedures-online.sql first.', 0, $error);
            }
            throw $error;
        } finally { if ($stmt !== null) $stmt->closeCursor(); }
    }
    public static function routine(string $procedure, array $args = []): RoutineResult
    {
        return new RoutineResult(self::call($procedure, $args));
    }
    public static function lastInsertId(): int { return self::$lastProcedureInsertId; }
}
