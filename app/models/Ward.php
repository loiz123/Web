<?php
class Ward extends CatalogModel
{
    protected string $table = 'wards';
    protected array $fillable = ['province_id', 'district_id', 'name'];
    protected ?string $parentCol = 'province_id';

    public function usageCount(int $id): int
    {
        return (int)$this->routine('sp_rt_ward_usage_count_1', [$id])->fetchColumn();
    }

    public function findByProvinceId(int $provinceId): array
    {
        return $this->routine('sp_rt_ward_find_by_province_id_1', [$provinceId])->fetchAll();
    }

    /** $filter: province_id (tùy chọn). Giới hạn 300 dòng cho gọn trang. */
    public function adminList(array $filter = []): array
    {
        return $this->call('sp_ward_admin_list', [(int)($filter['province_id'] ?? 0)]);
    }

    public function find(int $id): ?array
    {
        return $this->routine('sp_rt_ward_find_1', [$id])->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $this->routine('sp_rt_ward_create_1', [
            $data['name'],
            $data['province_id'] ?? null
        ]);
        return (int)Database::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->routine('sp_rt_ward_update_1', [
            $data['name'],
            $data['province_id'] ?? null,
            $id
        ]);
        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->routine('sp_rt_ward_delete_1', [$id]);
        return $stmt->rowCount() > 0;
    }

    public function nameExists(string $name, ?int $parentId = null, int $exceptId = 0): bool
    {
        return (int)($this->call('sp_ward_name_exists', [$name, $parentId, $exceptId])[0]['total'] ?? 0) > 0;
    }
}
