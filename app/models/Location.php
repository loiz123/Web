<?php
// [A] Tỉnh / Quận / Phường (dùng cho dropdown phụ thuộc bằng AJAX)
class Location extends Model
{
    protected string $table = 'provinces';

    public function provinces(): array { return $this->query('SELECT id, name FROM provinces ORDER BY name')->fetchAll(); }
    public function districtsByProvince(int $id): array { return $this->query('SELECT id, name FROM districts WHERE province_id = ? ORDER BY name', [$id])->fetchAll(); }
    public function wardsByDistrict(int $id): array { return $this->query('SELECT id, name FROM wards WHERE district_id = ? ORDER BY name', [$id])->fetchAll(); }
}
