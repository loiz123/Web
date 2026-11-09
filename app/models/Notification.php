<?php
// [C/D] Thông báo trong ứng dụng (In-app Notification)
class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = ['user_id', 'title', 'message', 'link', 'is_read', 'created_at'];

    /**
     * Tạo thông báo mới.
     * Hỗ trợ cả 2 cú pháp:
     *   - create(['user_id' => 1, 'title' => '...', 'message' => '...', 'link' => '...'])
     *   - create($userId, $title, $message, $link)
     */
    public function create(array|int $userIdOrData, string $title = '', string $message = '', ?string $link = null): int
    {
        if (is_array($userIdOrData)) {
            $data = $userIdOrData;
            if (!isset($data['is_read'])) {
                $data['is_read'] = 0;
            }
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            return parent::create($data);
        }

        $userId = (int)$userIdOrData;
        if ($userId <= 0 || trim($title) === '') {
            return 0;
        }

        return parent::create([
            'user_id'    => $userId,
            'title'      => mb_substr(trim($title), 0, 255),
            'message'    => trim($message),
            'link'       => $link !== null && trim($link) !== '' ? trim($link) : null,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Lấy danh sách thông báo chưa đọc của người dùng.
     */
    public function getUnreadForUser(int $userId, int $limit = 15): array
    {
        $limit = max(1, min(100, $limit));
        try {
            return $this->query(
                "SELECT * FROM notifications
                 WHERE user_id = ? AND is_read = 0
                 ORDER BY created_at DESC, id DESC
                 LIMIT {$limit}",
                [$userId]
            )->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Đếm tổng số thông báo chưa đọc của người dùng.
     */
    public function countUnreadForUser(int $userId): int
    {
        try {
            return (int)$this->query(
                'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
                [$userId]
            )->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Lấy toàn bộ danh sách thông báo của người dùng (cả chưa đọc và đã đọc).
     */
    public function getAllForUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        try {
            return $this->query(
                "SELECT * FROM notifications
                 WHERE user_id = ?
                 ORDER BY is_read ASC, created_at DESC, id DESC
                 LIMIT {$limit}",
                [$userId]
            )->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Tìm thông báo thuộc về user.
     */
    public function findForUser(int $id, int $userId): ?array
    {
        $row = $this->query(
            'SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc.
     */
    public function markAsRead(int $id, ?int $userId = null): bool
    {
        if ($userId !== null) {
            return $this->query(
                'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
                [$id, $userId]
            )->rowCount() > 0;
        }
        return $this->query(
            'UPDATE notifications SET is_read = 1 WHERE id = ?',
            [$id]
        )->rowCount() > 0;
    }

    /**
     * Đánh dấu tất cả thông báo của người dùng là đã đọc.
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->query(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0',
            [$userId]
        )->rowCount();
    }
}
