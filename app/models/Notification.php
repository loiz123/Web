<?php
// Thông báo trong ứng dụng (In-app Notification)
class Notification
{
    protected string $table = 'notifications';
    protected array $fillable = ['user_id', 'title', 'message', 'link', 'type', 'is_read', 'created_at'];
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }



    protected function routine(string $procedure, array $args = []): RoutineResult { return Database::routine($procedure, $args); }

    /**
     * Tạo thông báo mới. Hỗ trợ cả 2 dạng gọi:
     * - Notification::create($userId, $title, $message, $link, $type)
     * - (new Notification())->create(['user_id' => ..., 'title' => ..., ...])
     */
    public static function create(
        array|int $userIdOrData,
        string $title = '',
        string $message = '',
        ?string $link = null,
        string $type = 'info'
    ): int {
        $db = Database::connection();

        if (is_array($userIdOrData)) {
            $data = $userIdOrData;
            $userId = (int)($data['user_id'] ?? 0);
            $title = (string)($data['title'] ?? '');
            $message = (string)($data['message'] ?? '');
            $link = isset($data['link']) ? (string)$data['link'] : null;
            $type = (string)($data['type'] ?? 'info');
        } else {
            $userId = (int)$userIdOrData;
        }

        if ($userId <= 0 || trim($title) === '') {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $cleanLink = ($link !== null && trim($link) !== '') ? trim($link) : null;
        $cleanType = trim($type) !== '' ? trim($type) : 'info';

        $stmt = Database::routine('sp_rt_notification_create_1', [
            $userId,
            mb_substr(trim($title), 0, 255),
            trim($message),
            $cleanLink,
            $cleanType,
            $now,
        ]);

        return (int)Database::lastInsertId();
    }

    /**
     * Helper tạo thông báo (alias tiện lợi)
     */
    public static function send(
        int $userId,
        string $title,
        string $message = '',
        ?string $link = null,
        string $type = 'info'
    ): int {
        return self::create($userId, $title, $message, $link, $type);
    }

    /**
     * Lấy danh sách thông báo chưa đọc của người dùng.
     */
    public function getUnreadForUser(int $userId, int $limit = 15): array
    {
        $limit = max(1, min(100, $limit));
        try {
            return $this->routine('sp_rt_notification_get_unread_for_user_1', [...[$userId], $limit])->fetchAll();
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
            return (int)$this->routine('sp_rt_notification_count_unread_for_user_1', [$userId])->fetchColumn();
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
            return $this->routine('sp_rt_notification_get_all_for_user_1', [...[$userId], $limit])->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Tìm thông báo thuộc về user.
     */
    public function findForUser(int $id, int $userId): ?array
    {
        $row = $this->routine('sp_rt_notification_find_for_user_1', [$id, $userId])->fetch();
        return $row ?: null;
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc.
     */
    public function markAsRead(int $id, ?int $userId = null): bool
    {
        if ($userId !== null) {
            return $this->routine('sp_rt_notification_mark_as_read_1', [$id, $userId])->rowCount() > 0;
        }
        return $this->routine('sp_rt_notification_mark_as_read_2', [$id])->rowCount() > 0;
    }

    /**
     * Đánh dấu tất cả thông báo của người dùng là đã đọc.
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->routine('sp_rt_notification_mark_all_as_read_1', [$userId])->rowCount();
    }

    public function find(int $id): ?array
    {
        $row = $this->routine('sp_rt_notification_find_1', [$id])->fetch();
        return $row ?: null;
    }

    public function delete(int $id): bool
    {
        return $this->routine('sp_rt_notification_delete_1', [$id])->rowCount() > 0;
    }
}
