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
        $marks = implode(',', array_fill(0, count($args), '?'));
        $stmt = $this->db->prepare("CALL $procedure($marks)");
        $stmt->execute(array_values($args));
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
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
