<?php
// [A] Tiện ích
class Amenity extends Model
{
    protected string $table = 'amenities';
    protected array $fillable = ['name'];

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
