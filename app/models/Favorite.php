<?php
// [C/D] Yêu thích (khóa chính kép user_id + property_id)
class Favorite extends Model
{
    protected string $table = 'favorites';
    protected array $fillable = ['user_id', 'property_id'];

    /** Kiểm tra xem user đã thích tin này chưa */
    public function isFavorited(int $userId, int $propertyId): bool
    {
        if (config('use_routines')) {
            try {
                return $this->call('sp_favorite_exists', [$userId, $propertyId]) !== [];
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1305) throw $e;
            }
        }
        $row = $this->query(
            'SELECT 1 FROM favorites WHERE user_id = ? AND property_id = ? LIMIT 1',
            [$userId, $propertyId]
        )->fetchColumn();
        return (bool)$row;
    }

    /** Bật/tắt yêu thích. Trả về true nếu vừa lưu, false nếu vừa bỏ lưu. */
    public function toggle(int $userId, int $propertyId): bool
    {
        if ($this->isFavorited($userId, $propertyId)) {
            if (config('use_routines')) {
                try {
                    $this->call('sp_favorite_remove', [$userId, $propertyId]);
                    return false;
                } catch (PDOException $e) {
                    if ((int)($e->errorInfo[1] ?? 0) !== 1305) throw $e;
                }
            }
            $this->query(
                'DELETE FROM favorites WHERE user_id = ? AND property_id = ?',
                [$userId, $propertyId]
            );
            return false;
        }

        if (config('use_routines')) {
            try {
                return (int)($this->call('sp_favorite_add', [$userId, $propertyId])[0]['affected'] ?? 0) > 0;
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1305) throw $e;
            }
        }

        $stmt = $this->query(
            'INSERT IGNORE INTO favorites (user_id, property_id) VALUES (?, ?)',
            [$userId, $propertyId]
        );
        return $stmt->rowCount() > 0;
    }

    /** Danh sách BĐS đã lưu của user (kèm thông tin loại, địa chỉ, ảnh bìa) */
    public function forUser(int $userId): array
    {
        if (config('use_routines')) {
            try {
                return $this->call('sp_favorite_list', [$userId]);
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1305) throw $e;
            }
        }

        $sql = "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
                       p.is_verified, p.status, f.created_at AS favorited_at,
                       t.name AS type_name, w.name AS ward_name,
                       COALESCE(d.name, '') AS district_name, pr.name AS province_name,
                       (SELECT image_url FROM property_images i WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
                FROM favorites f
                JOIN properties p ON p.id = f.property_id
                JOIN property_types t ON t.id = p.type_id
                JOIN wards w ON w.id = p.ward_id
                LEFT JOIN districts d ON d.id = w.district_id
                JOIN provinces pr ON pr.id = w.province_id
                WHERE f.user_id = ? AND p.status = 'approved'
                ORDER BY f.created_at DESC";
        return $this->query($sql, [$userId])->fetchAll();
    }

    /** Đếm số tin yêu thích của user */
    public function countForUser(int $userId): int
    {
        if (config('use_routines')) {
            try {
                return (int)($this->call('sp_favorite_count', [$userId])[0]['total'] ?? 0);
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1305) throw $e;
            }
        }

        return (int)$this->query(
            "SELECT COUNT(*) FROM favorites f
             JOIN properties p ON p.id = f.property_id
             WHERE f.user_id = ? AND p.status = 'approved'",
            [$userId]
        )->fetchColumn();
    }

    /** Lấy danh sách ID các tin đã thích của user (dùng để active icon tim) */
    public function userFavoritedIds(int $userId): array
    {
        if (config('use_routines')) {
            try {
                return array_column($this->call('sp_favorite_ids', [$userId]), 'property_id');
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1305) throw $e;
            }
        }

        return $this->query(
            'SELECT property_id FROM favorites WHERE user_id = ?',
            [$userId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }
}
