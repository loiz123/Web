<?php
// [A] Tỉnh / Thành phố
class Province extends CatalogModel
{
    protected string $table = 'provinces';

    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM properties p
            JOIN wards w ON w.id = p.ward_id JOIN districts d ON d.id = w.district_id
            WHERE d.province_id = ?', [$id])->fetchColumn();
    }

    public function childCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM districts WHERE province_id = ?', [$id])->fetchColumn();
    }

    public function adminList(array $filter = []): array
    {
        return $this->query('SELECT pr.id, pr.name, NULL AS parent_id, NULL AS parent_name,
            (SELECT COUNT(*) FROM districts d WHERE d.province_id = pr.id) AS extra,
            (SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id
               JOIN districts d ON d.id = w.district_id WHERE d.province_id = pr.id) AS used
            FROM provinces pr ORDER BY pr.name')->fetchAll();
    }
}
