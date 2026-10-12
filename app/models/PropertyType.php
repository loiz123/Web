<?php
// [A] Loại bất động sản
class PropertyType extends Model
{
    protected string $table = 'property_types';
    protected array $fillable = ['name'];
    public function allSorted(): array { return $this->query('SELECT id, name FROM property_types ORDER BY name')->fetchAll(); }
}
