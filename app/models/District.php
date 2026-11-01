<?php
// [A] Quận / Huyện
class District extends CatalogModel
{
    protected string $table = 'districts';
    protected array $fillable = ['province_id', 'name'];
    protected ?string $parentCol = 'province_id';

    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id
            WHERE w.district_id = ?', [$id])->fetchColumn();
    }

    public function childCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM wards WHERE district_id = ?', [$id])->fetchColumn();
    }

    /** $filter: province_id (tùy chọn) */
    public function adminList(array $filter = []): array
    {
        $sql = 'SELECT d.id, d.name, d.province_id AS parent_id, pr.name AS parent_name,
            (SELECT COUNT(*) FROM wards w WHERE w.district_id = d.id) AS extra,
            (SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.district_id = d.id) AS used
            FROM districts d JOIN provinces pr ON pr.id = d.province_id';
        $args = [];
        if (!empty($filter['province_id'])) { $sql .= ' WHERE d.province_id = ?'; $args[] = (int)$filter['province_id']; }
        return $this->query($sql . ' ORDER BY pr.name, d.name', $args)->fetchAll();
    }
}
