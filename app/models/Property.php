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
        if (config('use_routines')) {
            try {
                return $this->call('sp_get_agent_properties', [$userId, $status]);
            } catch (PDOException $error) {
                $code = (int)($error->errorInfo[1] ?? 0);
                if ($code !== 1305) {
                    throw $error;
                }
            }
        }
        return $this->listingsForUserSql($userId, $status);
    }

    private function listingsForUserSql(int $userId, ?string $status): array
    {
        $sql = "SELECT p.id, p.title, p.status, p.listing_type, p.price, p.area, p.created_at,
                       p.reject_reason,
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
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : null;
        $updated = $this->query(
            "UPDATE properties
             SET type_id = ?, title = ?, description = ?, listing_type = ?, price = ?, area = ?,
                 bedrooms = ?, bathrooms = ?, address_detail = ?, latitude = ?, longitude = ?,
                 status = 'pending', reject_reason = NULL
             WHERE id = ? AND user_id = ?",
            [
                $data['type_id'], $data['title'], $data['description'], $data['listing_type'],
                $data['price'], $data['area'], $data['bedrooms'], $data['bathrooms'],
                $data['address_detail'], $lat, $lng, $id, $userId,
            ]
        )->rowCount() > 0;
        return $updated || $this->findOwned($id, $userId) !== null;
    }

    public function setOwnedVisibility(int $id, int $userId, bool $hidden): bool
    {
        return $this->setOwnedStatus($id, $userId, $hidden ? 'hidden' : 'pending');
    }

    public function setOwnedStatus(int $id, int $userId, string $status): bool
    {
        if (!in_array($status, ['pending', 'hidden', 'closed'], true)) {
            return false;
        }
        if (config('use_routines')) {
            try {
                return $this->call('sp_set_property_status', [$id, $userId, $status]) !== [];
            } catch (PDOException $error) {
                $code = (int)($error->errorInfo[1] ?? 0);
                if ($code !== 1305) {
                    throw $error;
                }
            }
        }
        $allowedFrom = $status === 'hidden' ? ['approved'] : ($status === 'pending' ? ['hidden', 'closed'] : ['approved']);
        $marks = implode(',', array_fill(0, count($allowedFrom), '?'));
        $params = [$status, $id, $userId, ...$allowedFrom];
        $sql = "UPDATE properties SET status = ?, reject_reason = NULL
                WHERE id = ? AND user_id = ? AND status IN ($marks)";
        return $this->query($sql, $params)->rowCount() > 0;
    }

    public function imagesForListing(int $id): array
    {
        return (new PropertyImage())->urlsByPropertyId($id);
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
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : null;

        $this->db->beginTransaction();
        try {
            $this->query(
                "INSERT INTO properties
                    (user_id, type_id, ward_id, title, description, listing_type, price, deposit, area,
                     bedrooms, bathrooms, direction, legal_status, furniture, pets_allowed, address_detail,
                     latitude, longitude, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [
                    $data['user_id'], $data['type_id'], $data['ward_id'], $data['title'], $data['description'],
                    $data['listing_type'], $data['price'], $data['deposit'], $data['area'], $data['bedrooms'],
                    $data['bathrooms'], $data['direction'], $data['legal_status'], $data['furniture'],
                    $data['pets_allowed'], $data['address_detail'], $lat, $lng,
                ]
            );
            $propertyId = (int)$this->db->lastInsertId();
            $propertyImage = new PropertyImage();

            foreach ($imagePaths as $index => $imagePath) {
                $propertyImage->createForProperty(
                    $propertyId,
                    $imagePath,
                    $index === $coverIndex
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
