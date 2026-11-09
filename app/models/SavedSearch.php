<?php
// [C] Bộ lọc đã lưu - Hà Ngọc Thiện (@hntrhunter) phụ trách
class SavedSearch extends Model
{
    protected string $table = 'saved_searches';
    protected array $fillable = ['user_id', 'name', 'filters'];

    /**
     * Lưu một bộ lọc tìm kiếm mới cho user
     */
    public function saveSearch(int $userId, string $name, array $filters): int
    {
        $cleanName = mb_substr(trim($name), 0, 100, 'UTF-8');
        if ($cleanName === '') {
            $cleanName = 'Bộ lọc ' . date('d/m/Y H:i');
        }

        // Lọc bỏ các key rỗng trước khi lưu JSON
        $cleanFilters = array_filter($filters, function ($val) {
            return $val !== null && $val !== '';
        });

        $json = json_encode($cleanFilters, JSON_UNESCAPED_UNICODE);

        $rows = $this->call('sp_saved_search_create', [$userId, $cleanName, $json]);
        return (int)($rows[0]['id'] ?? 0);
    }

    /**
     * Lấy toàn bộ danh sách bộ lọc đã lưu của user
     */
    public function getByUser(int $userId): array
    {
        $rows = $this->call('sp_saved_search_list', [$userId]);

        foreach ($rows as &$row) {
            $row['filter_data'] = json_decode($row['filters'], true) ?: [];
        }
        unset($row);

        return $rows;
    }

    /**
     * Tìm 1 bộ lọc thuộc sở hữu của user
     */
    public function findOwned(int $id, int $userId): ?array
    {
        $row = $this->call('sp_saved_search_find', [$id, $userId])[0] ?? null;

        if ($row) {
            $row['filter_data'] = json_decode($row['filters'], true) ?: [];
            return $row;
        }

        return null;
    }

    /**
     * Xóa 1 bộ lọc của user
     */
    public function deleteOwned(int $id, int $userId): bool
    {
        $rows = $this->call('sp_saved_search_delete', [$id, $userId]);
        return (int)($rows[0]['affected'] ?? 0) > 0;
    }
}
