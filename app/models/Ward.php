<?php
class Ward extends CatalogModel
{
    protected string $table = 'wards';
    protected array $fillable = ['province_id', 'district_id', 'name'];
    protected ?string $parentCol = 'province_id';

    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM properties WHERE ward_id = ?', [$id])->fetchColumn();
    }

    public function findByProvinceId(int $provinceId): array
    {
        return $this->query('SELECT MIN(id) AS id, MIN(name) AS name, MIN(province_id) AS province_id FROM wards WHERE province_id = ? GROUP BY BINARY name ORDER BY MIN(name) ASC', [$provinceId])->fetchAll();
    }

    /** $filter: province_id (tùy chọn). Giới hạn 300 dòng cho gọn trang. */
    public function adminList(array $filter = []): array
    {
        $sql = 'SELECT w.id, w.name, w.province_id AS parent_id, pr.name AS parent_name,
            NULL AS grand_id, NULL AS grand_name, NULL AS extra,
            (SELECT COUNT(*) FROM properties p WHERE p.ward_id = w.id) AS used
            FROM wards w
            LEFT JOIN provinces pr ON pr.id = w.province_id';
        $where = []; $args = [];
        if (!empty($filter['province_id'])) {
            $where[] = 'w.province_id = ?';
            $args[] = (int)$filter['province_id'];
        }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        return $this->query($sql . ' ORDER BY pr.name, w.name LIMIT 300', $args)->fetchAll();
    }

    public function find(int $id): ?array
    {
        return $this->query('SELECT id, name, province_id FROM wards WHERE id = ?', [$id])->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $this->query('INSERT INTO wards (name, province_id, district_id) VALUES (?, ?, NULL)', [
            $data['name'],
            $data['province_id'] ?? null
        ]);
        return (int)$this->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $this->query('UPDATE wards SET name = ?, province_id = ? WHERE id = ?', [
            $data['name'],
            $data['province_id'] ?? null,
            $id
        ]);
        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->query('DELETE FROM wards WHERE id = ?', [$id]);
        return $stmt->rowCount() > 0;
    }

    public function nameExists(string $name, ?int $parentId = null, int $exceptId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM wards WHERE BINARY name = ? AND id <> ?';
        $args = [$name, $exceptId];
        if ($parentId !== null) {
            $sql .= ' AND province_id = ?';
            $args[] = $parentId;
        }
        return (int)$this->query($sql, $args)->fetchColumn() > 0;
    }
}
