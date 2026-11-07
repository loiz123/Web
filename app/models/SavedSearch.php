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

        $this->query(
            "INSERT INTO saved_searches (user_id, name, filters, created_at) VALUES (?, ?, ?, NOW())",
            [$userId, $cleanName, $json]
        );

        return (int)$this->db->lastInsertId();
    }

    /**
     * Lấy toàn bộ danh sách bộ lọc đã lưu của user
     */
    public function getByUser(int $userId): array
    {
        $rows = $this->query(
            "SELECT * FROM saved_searches WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        )->fetchAll(PDO::FETCH_ASSOC);

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
        $row = $this->query(
            "SELECT * FROM saved_searches WHERE id = ? AND user_id = ? LIMIT 1",
            [$id, $userId]
        )->fetch(PDO::FETCH_ASSOC);

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
        return $this->query(
            "DELETE FROM saved_searches WHERE id = ? AND user_id = ?",
            [$id, $userId]
        )->rowCount() > 0;
    }
}
