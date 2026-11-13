<?php
// Data access is exclusively through named stored procedures.
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected PDO $db;

    public function __construct() { $this->db = Database::connection(); }
    protected function call(string $procedure, array $args = []): array { return Database::call($procedure, $args); }
    protected function routine(string $procedure, array $args = []): RoutineResult { return Database::routine($procedure, $args); }

    private function modelProcedure(string $operation): string
    {
        $tables = ['roles','users','provinces','districts','wards','property_types','amenities','properties',
            'property_images','property_amenities','saved_searches','favorites','reviews','viewing_appointments',
            'reports','password_resets','notifications'];
        if (!in_array($this->table, $tables, true)) throw new LogicException('Unsupported model table.');
        return 'sp_model_' . $this->table . '_' . $operation;
    }
    public function all(): array { return $this->call($this->modelProcedure('all')); }
    public function find(int $id): ?array { return $this->call($this->modelProcedure('find'), [$id])[0] ?? null; }
    public function create(array $data): int
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        return (int)($this->call($this->modelProcedure('create'), [json_encode((object)$data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)])[0]['id'] ?? 0);
    }
    public function update(int $id, array $data): bool
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        if (!$data) return false;
        $this->call($this->modelProcedure('update'), [$id, json_encode((object)$data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
        return true;
    }
    public function delete(int $id): bool { return (int)($this->call($this->modelProcedure('delete'), [$id])[0]['affected'] ?? 0) > 0; }
}
