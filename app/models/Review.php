<?php
// [C - Thiện] Đánh giá & bình luận tin đăng (reviews)
class Review extends Model
{
    protected string $table = 'reviews';
    protected array $fillable = ['property_id', 'user_id', 'rating', 'comment'];

    /** Lấy danh sách đánh giá của một tin đăng. */
    public function forProperty(int $propertyId): array
    {
        return $this->query(
            'SELECT r.*, u.full_name AS user_name, u.avatar AS user_avatar, u.role_id
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.property_id = ?
             ORDER BY r.created_at DESC',
            [$propertyId]
        )->fetchAll();
    }

    /** Thống kê điểm trung bình và tổng số đánh giá của một tin. */
    public function getStats(int $propertyId): array
    {
        $row = $this->query(
            'SELECT COUNT(*) AS total, AVG(rating) AS avg_rating
             FROM reviews
             WHERE property_id = ?',
            [$propertyId]
        )->fetch();

        $total = (int)($row['total'] ?? 0);
        $avg = $total > 0 ? round((float)$row['avg_rating'], 1) : 0.0;

        return [
            'total'      => $total,
            'avg_rating' => $avg,
        ];
    }

    /** Lấy đánh giá của một người dùng cụ thể trên tin đăng này. */
    public function getUserReview(int $propertyId, int $userId): ?array
    {
        $res = $this->query(
            'SELECT * FROM reviews WHERE property_id = ? AND user_id = ? LIMIT 1',
            [$propertyId, $userId]
        )->fetch();

        return $res ?: null;
    }

    /** Thêm mới hoặc cập nhật đánh giá (Upsert). */
    public function saveReview(int $propertyId, int $userId, int $rating, ?string $comment = null): bool
    {
        $rating = max(1, min(5, $rating));
        $comment = $comment !== null ? mb_substr(trim($comment), 0, 500, 'UTF-8') : null;

        // Kiểm tra xem tin có tồn tại không
        $propExists = (bool)$this->query('SELECT 1 FROM properties WHERE id = ?', [$propertyId])->fetchColumn();
        if (!$propExists) {
            return false;
        }

        $stmt = $this->query(
            'INSERT INTO reviews (property_id, user_id, rating, comment)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = CURRENT_TIMESTAMP',
            [$propertyId, $userId, $rating, $comment]
        );

        return $stmt->rowCount() > 0;
    }

    /** Xóa đánh giá của chính người dùng. */
    public function deleteByUser(int $id, int $userId): bool
    {
        $stmt = $this->query(
            'DELETE FROM reviews WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
        return $stmt->rowCount() > 0;
    }
}
