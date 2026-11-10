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

    /** $filter: province_id hoặc district_id (tùy chọn). Giới hạn 300 dòng cho gọn trang. */
    public function adminList(array $filter = []): array
    {
        $sql = 'SELECT w.id, w.name, w.province_id AS parent_id, pr.name AS parent_name,
            w.district_id AS grand_id, d.name AS grand_name, NULL AS extra,
            (SELECT COUNT(*) FROM properties p WHERE p.ward_id = w.id) AS used
            FROM wards w
            LEFT JOIN provinces pr ON pr.id = w.province_id
            LEFT JOIN districts d ON d.id = w.district_id';
        $where = []; $args = [];
        if (!empty($filter['province_id'])) { $where[] = 'w.province_id = ?'; $args[] = (int)$filter['province_id']; }
        elseif (!empty($filter['district_id'])) { $where[] = 'w.district_id = ?'; $args[] = (int)$filter['district_id']; }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        return $this->query($sql . ' ORDER BY pr.name, w.name LIMIT 300', $args)->fetchAll();
    }
}
