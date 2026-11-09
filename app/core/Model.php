<?php
// Lớp cha cho mọi Model. Model con khai báo $table và $fillable.
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];       // chỉ các cột này được phép ghi qua create()/update()
    protected PDO $db;

    public function __construct() { $this->db = Database::connection(); }

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

}
