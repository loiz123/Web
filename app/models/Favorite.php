<?php
// [C/D] Yêu thích (khóa chính kép user_id + property_id)
class Favorite extends Model
{
    protected string $table = 'favorites';
    protected array $fillable = ['user_id', 'property_id'];

    /** Kiểm tra xem user đã thích tin này chưa */
    public function isFavorited(int $userId, int $propertyId): bool
    {
        return $this->call('sp_favorite_exists', [$userId, $propertyId]) !== [];
    }

    /** Bật/tắt yêu thích. Trả về true nếu vừa lưu, false nếu vừa bỏ lưu. */
    public function toggle(int $userId, int $propertyId): bool
    {
        if ($this->isFavorited($userId, $propertyId)) {
            $this->call('sp_favorite_remove', [$userId, $propertyId]);
            return false;
        }

        return (int)($this->call('sp_favorite_add', [$userId, $propertyId])[0]['affected'] ?? 0) > 0;
    }

    /** Danh sách BĐS đã lưu của user (kèm thông tin loại, địa chỉ, ảnh bìa) */
    public function forUser(int $userId): array
    {
        return $this->call('sp_favorite_list', [$userId]);
    }

    /** Đếm số tin yêu thích của user */
    public function countForUser(int $userId): int
    {
        return (int)($this->call('sp_favorite_count', [$userId])[0]['total'] ?? 0);
    }

    /** Lấy danh sách ID các tin đã thích của user (dùng để active icon tim) */
    public function userFavoritedIds(int $userId): array
    {
        return array_column($this->call('sp_favorite_ids', [$userId]), 'property_id');
    }
}
