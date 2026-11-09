<?php
// [C - Thiện] Đánh giá & bình luận tin đăng (reviews)
class Review extends Model
{
    protected string $table = 'reviews';
    protected array $fillable = ['property_id', 'user_id', 'rating', 'comment'];

    /** Lấy danh sách đánh giá của một tin đăng. */
    public function forProperty(int $propertyId): array
    {
        return $this->call('sp_review_for_property', [$propertyId]);
    }

    /** Thống kê điểm trung bình và tổng số đánh giá của một tin. */
    public function getStats(int $propertyId): array
    {
        $row = $this->call('sp_review_stats', [$propertyId])[0] ?? [];

        $total = (int)($row['total'] ?? 0);
        $avg = $total > 0 ? round((float)$row['avg_rating'], 1) : 0.0;

        return [
            'total'         => $total,
            'total_reviews' => $total,
            'avg_rating'    => $avg,
        ];
    }

    /** Lấy đánh giá của một người dùng cụ thể trên tin đăng này. */
    public function getUserReview(int $propertyId, int $userId): ?array
    {
        $res = $this->call('sp_review_by_user', [$propertyId, $userId])[0] ?? null;

        return $res ?: null;
    }

    /** Thêm mới hoặc cập nhật đánh giá (Upsert). */
    public function saveReview(int $propertyId, int $userId, int $rating, ?string $comment = null): bool
    {
        $rating = max(1, min(5, $rating));
        if ($comment !== null) {
            preg_match('/^.{0,500}/us', trim($comment), $matches);
            $comment = $matches[0] ?? '';
        }

        return (int)($this->call('sp_review_save', [$propertyId, $userId, $rating, $comment])[0]['affected'] ?? 0) > 0;
    }

    /** Xóa đánh giá của chính người dùng. */
    public function deleteByUser(int $id, int $userId): bool
    {
        return (int)($this->call('sp_review_delete', [$id, $userId])[0]['affected'] ?? 0) > 0;
    }
}
