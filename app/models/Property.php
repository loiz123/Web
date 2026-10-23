<?php
// [B] Tin đăng - Hồ Duy Hải (@haiho2365-source) phụ trách (CRUD, upload ảnh, đổi trạng thái)
// Lưu ý: Các hàm latest() và search() đã được chuyển sang PropertyFinder.php (Hà Ngọc Thiện phụ trách)
class Property extends Model
{
    protected string $table = 'properties';
    protected array $fillable = [
        'user_id', 'type_id', 'ward_id', 'title', 'description', 'listing_type', 'price', 'deposit', 'area',
        'bedrooms', 'bathrooms', 'direction', 'legal_status', 'furniture', 'pets_allowed', 'address_detail',
        'latitude', 'longitude', 'status', 'reject_reason', 'is_verified',
    ];

    public function typeExists(int $typeId): bool
    {
        return (bool)$this->query('SELECT id FROM property_types WHERE id = ?', [$typeId])->fetch();
    }

    public function listingsForUser(int $userId, ?string $status = null): array
    {
        $sql = "SELECT p.id, p.title, p.status, p.listing_type, p.price, p.area, p.created_at,
                       t.name AS type_name,
                       (SELECT image_url FROM property_images i
                        WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
                FROM properties p
                JOIN property_types t ON t.id = p.type_id
                WHERE p.user_id = ?";
        $params = [$userId];
        if ($status !== null) {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
        return $this->query($sql, $params)->fetchAll();
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $row = $this->query(
            'SELECT * FROM properties WHERE id = ? AND user_id = ?',
            [$id, $userId]
        )->fetch();
        return $row ?: null;
    }

    public function updateOwnedListing(int $id, int $userId, array $data): bool
    {
        $updated = $this->query(
            "UPDATE properties
             SET type_id = ?, title = ?, description = ?, listing_type = ?, price = ?, area = ?,
                 bedrooms = ?, bathrooms = ?, address_detail = ?, status = 'pending', reject_reason = NULL
             WHERE id = ? AND user_id = ?",
            [
                $data['type_id'], $data['title'], $data['description'], $data['listing_type'],
                $data['price'], $data['area'], $data['bedrooms'], $data['bathrooms'],
                $data['address_detail'], $id, $userId,
            ]
        )->rowCount() > 0;
        return $updated || $this->findOwned($id, $userId) !== null;
    }

    public function setOwnedVisibility(int $id, int $userId, bool $hidden): bool
    {
        if ($hidden) {
            return $this->query(
                "UPDATE properties SET status = 'hidden' WHERE id = ? AND user_id = ? AND status = 'approved'",
                [$id, $userId]
            )->rowCount() > 0;
        }
        return $this->query(
            "UPDATE properties SET status = 'pending', reject_reason = NULL
             WHERE id = ? AND user_id = ? AND status = 'hidden'",
            [$id, $userId]
        )->rowCount() > 0;
    }

    public function imagesForListing(int $id): array
    {
        return $this->query(
            'SELECT image_url FROM property_images WHERE property_id = ?',
            [$id]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function deleteOwned(int $id, int $userId): bool
    {
        return $this->query(
            'DELETE FROM properties WHERE id = ? AND user_id = ?',
            [$id, $userId]
        )->rowCount() > 0;
    }

    public function createListing(array $data, array $imagePaths, array $amenityIds, int $coverIndex): int
    {
        $this->db->beginTransaction();
        try {
            $this->query(
                "INSERT INTO properties
                    (user_id, type_id, ward_id, title, description, listing_type, price, deposit, area,
                     bedrooms, bathrooms, direction, legal_status, furniture, pets_allowed, address_detail, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [
                    $data['user_id'], $data['type_id'], $data['ward_id'], $data['title'], $data['description'],
                    $data['listing_type'], $data['price'], $data['deposit'], $data['area'], $data['bedrooms'],
                    $data['bathrooms'], $data['direction'], $data['legal_status'], $data['furniture'],
                    $data['pets_allowed'], $data['address_detail'],
                ]
            );
            $propertyId = (int)$this->db->lastInsertId();

            foreach ($imagePaths as $index => $imagePath) {
                $this->query(
                    'INSERT INTO property_images (property_id, image_url, is_cover) VALUES (?, ?, ?)',
                    [$propertyId, $imagePath, $index === $coverIndex ? 1 : 0]
                );
            }
            foreach ($amenityIds as $amenityId) {
                $this->query(
                    'INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)',
                    [$propertyId, $amenityId]
                );
            }

            $this->db->commit();
            return $propertyId;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
