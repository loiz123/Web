<?php
// Lớp cha cho mọi Model. Model con khai báo $table và $fillable.
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];       // chỉ các cột này được phép ghi qua create()/update()
    protected PDO $db;

    public function __construct() { $this->db = Database::connection(); }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Gọi stored procedure: call('sp_name', [a, b]) -> mảng dòng kết quả */
    protected function call(string $procedure, array $args = []): array
    {
        try {
            $marks = implode(',', array_fill(0, count($args), '?'));
            $stmt = $this->db->prepare("CALL $procedure($marks)");
            $stmt->execute(array_values($args));
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows;
        } catch (PDOException $e) {
            if ($e->getCode() === '42000' || str_contains($e->getMessage(), '1305') || str_contains($e->getMessage(), 'does not exist')) {
                return $this->fallbackCall($procedure, $args);
            }
            throw $e;
        }
    }

    /** Fallback an toàn khi stored procedure chưa tồn tại trên DB (lỗi 1305) */
    protected function fallbackCall(string $procedure, array $args = []): array
    {
        $allowedTables = ['property_types', 'amenities', 'provinces', 'districts', 'wards'];

        switch ($procedure) {
            case 'sp_catalog_sorted':
                $table = (string)($args[0] ?? '');
                if (in_array($table, $allowedTables, true)) {
                    return $this->query("SELECT id, name FROM {$table} ORDER BY name ASC")->fetchAll();
                }
                return [];

            case 'sp_catalog_find':
                $table = (string)($args[0] ?? '');
                $id = (int)($args[1] ?? 0);
                if (in_array($table, $allowedTables, true)) {
                    return $this->query("SELECT * FROM {$table} WHERE id = ?", [$id])->fetchAll();
                }
                return [];

            case 'sp_location_provinces':
                return $this->query("SELECT id, name FROM provinces ORDER BY name")->fetchAll();

            case 'sp_location_districts':
                return $this->query(
                    "SELECT id, name FROM districts WHERE province_id = ? ORDER BY name",
                    [(int)($args[0] ?? 0)]
                )->fetchAll();

            case 'sp_location_wards':
                return $this->query(
                    "SELECT id, name FROM wards WHERE district_id = ? ORDER BY name",
                    [(int)($args[0] ?? 0)]
                )->fetchAll();

            case 'sp_location_province_by_district':
                return $this->query(
                    "SELECT province_id FROM districts WHERE id = ?",
                    [(int)($args[0] ?? 0)]
                )->fetchAll();

            case 'sp_location_valid_ward':
                $provinceId = (int)($args[0] ?? 0);
                $districtId = (int)($args[1] ?? 0);
                $wardId = (int)($args[2] ?? 0);
                return $this->query(
                    "SELECT w.id FROM wards w
                     LEFT JOIN districts d ON d.id = w.district_id
                     JOIN provinces pr ON pr.id = w.province_id
                     WHERE w.id = ? AND pr.id = ? AND (? = 0 OR d.id = ?)",
                    [$wardId, $provinceId, $districtId, $districtId]
                )->fetchAll();

            default:
                return [];
        }
    }

    public function all(): array { return $this->query("SELECT * FROM {$this->table}")->fetchAll(); }

    public function find(int $id): ?array
    {
        $row = $this->query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id])->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        $cols = implode(',', array_keys($data));
        $marks = implode(',', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO {$this->table} ($cols) VALUES ($marks)", array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        if (!$data) return false;
        $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        $this->query("UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?", [...array_values($data), $id]);
        return true;
    }

    public function delete(int $id): bool
    {
        return $this->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id])->rowCount() > 0;
    }
}
