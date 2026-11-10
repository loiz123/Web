<?php
class Province extends CatalogModel
{
    protected string $table = 'provinces';

    /** Số mục con (số phường/xã trực thuộc tỉnh trong mô hình 2 cấp). */
    public function childCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM wards WHERE province_id = ?', [$id])->fetchColumn();
    }

    /** Số tin đăng thuộc tỉnh này. */
    public function usageCount(int $id): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.province_id = ?', [$id])->fetchColumn();
    }

    /** Danh sách admin hiển thị số phường/xã (extra) và số tin dùng (used). */
    public function adminList(array $filter = []): array
    {
        return $this->query(
            'SELECT pr.id, pr.name, NULL AS parent_id, NULL AS parent_name,
             (SELECT COUNT(*) FROM wards w WHERE w.province_id = pr.id) AS extra,
             (SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.province_id = pr.id) AS used
             FROM provinces pr
             ORDER BY pr.name'
        )->fetchAll();
    }
}
