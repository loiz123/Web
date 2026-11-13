<?php
class Province extends CatalogModel
{
    protected string $table = 'provinces';

    /** Số mục con (số phường/xã trực thuộc tỉnh trong mô hình 2 cấp). */
    public function childCount(int $id): int
    {
        return (int)$this->routine('sp_rt_province_child_count_1', [$id])->fetchColumn();
    }

    /** Số tin đăng thuộc tỉnh này. */
    public function usageCount(int $id): int
    {
        return (int)$this->routine('sp_rt_province_usage_count_1', [$id])->fetchColumn();
    }

    /** Danh sách admin hiển thị số phường/xã (extra) và số tin dùng (used). */
    public function adminList(array $filter = []): array
    {
        return $this->routine('sp_rt_province_admin_list_1', [])->fetchAll();
    }
}
