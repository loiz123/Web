<?php
// [A] Lớp cha cho các bảng danh mục (loại BĐS, tiện ích, tỉnh, quận, phường) dùng trong trang admin.
abstract class CatalogModel extends Model
{
    protected array $fillable = ['name'];
    protected ?string $parentCol = null;   // cột khóa ngoại cha (vd province_id); null nếu không có cha

    /** Số tin đăng đang dùng mục này (chặn xóa khi > 0). */
    abstract public function usageCount(int $id): int;

    /** Danh sách cho admin: id, name, parent_id, parent_name, grand_id, grand_name, extra, used. */
    abstract public function adminList(array $filter = []): array;

    /** Số mục con trực tiếp (vd tỉnh có bao nhiêu quận). */
    public function childCount(int $id): int { return 0; }

    /** Trùng tên trong cùng một cha (collation unicode_ci nên không phân biệt hoa/thường, dấu). */
    public function nameExists(string $name, ?int $parentId = null, int $exceptId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE name = ? AND id <> ?";
        $args = [$name, $exceptId];
        if ($this->parentCol !== null) { $sql .= " AND {$this->parentCol} = ?"; $args[] = (int)$parentId; }
        return (int)$this->query($sql, $args)->fetchColumn() > 0;
    }
}
