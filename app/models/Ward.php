<?php
// [A] Phường / Xã
class Ward extends CatalogModel
{
    protected string $table = 'wards';
    protected array $fillable = ['district_id', 'name'];
    protected ?string $parentCol = 'district_id';

    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM properties WHERE ward_id = ?', [$id])->fetchColumn();
    }

    /** $filter: district_id hoặc province_id (tùy chọn). Giới hạn 300 dòng cho gọn trang. */
    public function adminList(array $filter = []): array
    {
        $sql = 'SELECT w.id, w.name, w.district_id AS parent_id, d.name AS parent_name,
            d.province_id AS grand_id, pr.name AS grand_name, NULL AS extra,
            (SELECT COUNT(*) FROM properties p WHERE p.ward_id = w.id) AS used
            FROM wards w JOIN districts d ON d.id = w.district_id JOIN provinces pr ON pr.id = d.province_id';
        $where = []; $args = [];
        if (!empty($filter['district_id'])) { $where[] = 'w.district_id = ?'; $args[] = (int)$filter['district_id']; }
        elseif (!empty($filter['province_id'])) { $where[] = 'd.province_id = ?'; $args[] = (int)$filter['province_id']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        return $this->query($sql . ' ORDER BY pr.name, d.name, w.name LIMIT 300', $args)->fetchAll();
    }
}
