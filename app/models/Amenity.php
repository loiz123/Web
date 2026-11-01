<?php
// [A] Tiện ích
class Amenity extends CatalogModel
{
    protected string $table = 'amenities';
    protected array $fillable = ['name'];

    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM property_amenities WHERE amenity_id = ?', [$id])->fetchColumn();
    }

    public function adminList(array $filter = []): array
    {
        return $this->query('SELECT a.id, a.name, NULL AS parent_id, NULL AS parent_name, NULL AS extra,
            (SELECT COUNT(*) FROM property_amenities pa WHERE pa.amenity_id = a.id) AS used
            FROM amenities a ORDER BY a.name')->fetchAll();
    }

    public function allSorted(): array
    {
        return $this->query('SELECT id, name FROM amenities ORDER BY name')->fetchAll();
    }

    public function hasIds(array $ids): bool
    {
        if (!$ids) return true;
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->query("SELECT id FROM amenities WHERE id IN ($marks)", $ids)->fetchAll();
        return count($rows) === count($ids);
    }
}
