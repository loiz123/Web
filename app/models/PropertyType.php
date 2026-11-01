<?php
// [A] Loại bất động sản
class PropertyType extends CatalogModel
{
    protected string $table = 'property_types';

    public function allSorted(): array { return $this->query('SELECT id, name FROM property_types ORDER BY name')->fetchAll(); }

    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM properties WHERE type_id = ?', [$id])->fetchColumn();
    }

    public function adminList(array $filter = []): array
    {
        return $this->query('SELECT t.id, t.name, NULL AS parent_id, NULL AS parent_name, NULL AS extra,
            (SELECT COUNT(*) FROM properties p WHERE p.type_id = t.id) AS used
            FROM property_types t ORDER BY t.name')->fetchAll();
    }
}
