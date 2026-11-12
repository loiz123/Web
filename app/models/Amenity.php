<?php
class Amenity extends CatalogModel
{
    protected string $table = 'amenities';

    public function allSorted(): array { return $this->call('sp_catalog_sorted', [$this->table]); }

    public function hasIds(array $ids): bool
    {
        foreach ($ids as $id) if (!$this->find((int)$id)) return false;
        return count(array_unique($ids)) === count($ids);
    }
}
