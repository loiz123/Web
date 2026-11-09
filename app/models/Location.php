<?php
// [A] Tỉnh / Quận / Phường (dùng cho dropdown phụ thuộc bằng AJAX)
class Location extends Model
{
    protected string $table = 'provinces';

    public function provinces(): array { return $this->call('sp_location_provinces'); }
    public function districtsByProvince(int $id): array { return $this->call('sp_location_districts', [$id]); }
    public function wardsByDistrict(int $id): array { return $this->call('sp_location_wards', [$id]); }
    public function provinceByDistrict(int $districtId): ?int
    {
        $row = $this->call('sp_location_province_by_district', [$districtId])[0] ?? null;
        return $row ? (int)$row['province_id'] : null;
    }
    public function isValidWard(int $provinceId, int $districtId, int $wardId): bool
    {
        return $this->call('sp_location_valid_ward', [$provinceId, $districtId, $wardId]) !== [];
    }
}
