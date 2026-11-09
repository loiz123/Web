<?php
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
}
