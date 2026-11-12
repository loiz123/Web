<?php
class PropertyType extends CatalogModel
{
    protected string $table = 'property_types';

    public function allSorted(): array { return $this->call('sp_catalog_sorted', [$this->table]); }
}
