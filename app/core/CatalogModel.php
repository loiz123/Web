<?php
// [A] Lớp cha cho các bảng danh mục (loại BĐS, tiện ích, tỉnh, quận, phường) dùng trong trang admin.
abstract class CatalogModel extends Model
{
    protected array $fillable = ['name'];
    protected ?string $parentCol = null;   // cột khóa ngoại cha (vd province_id); null nếu không có cha

    /** Số tin đăng đang dùng mục này (chặn xóa khi > 0). */
    public function usageCount(int $id): int { return (int)($this->call('sp_catalog_usage', [$this->table, $id])[0]['total'] ?? 0); }

    /** Danh sách cho admin: id, name, parent_id, parent_name, grand_id, grand_name, extra, used. */
    public function adminList(array $filter = []): array { return $this->call('sp_catalog_admin_list', [$this->table, (int)($filter['province_id'] ?? 0), (int)($filter['district_id'] ?? 0)]); }

    /** Số mục con trực tiếp (vd tỉnh có bao nhiêu quận). */
    public function childCount(int $id): int { return (int)($this->call('sp_catalog_children', [$this->table, $id])[0]['total'] ?? 0); }

    public function find(int $id): ?array { return $this->call('sp_catalog_find', [$this->table, $id])[0] ?? null; }
    public function create(array $data): int { return (int)($this->call('sp_catalog_create', [$this->table, $data['name'], $this->parentCol ? ($data[$this->parentCol] ?? null) : null])[0]['id'] ?? 0); }
    public function update(int $id, array $data): bool { $this->call('sp_catalog_update', [$this->table, $id, $data['name'], $this->parentCol ? ($data[$this->parentCol] ?? null) : null]); return true; }
    public function delete(int $id): bool { return (int)($this->call('sp_catalog_delete', [$this->table, $id])[0]['affected'] ?? 0) > 0; }

    /** Trùng tên trong cùng một cha (collation unicode_ci nên không phân biệt hoa/thường, dấu). */
    public function nameExists(string $name, ?int $parentId = null, int $exceptId = 0): bool
    {
        return (int)($this->call('sp_catalog_name_exists', [$this->table, $name, $parentId, $exceptId])[0]['total'] ?? 0) > 0;
    }
}
