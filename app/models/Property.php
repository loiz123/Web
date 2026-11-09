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
            'SELECT p.*, w.district_id, d.province_id
             FROM properties p
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN districts d ON d.id = w.district_id
             WHERE p.id = ? AND p.user_id = ?',
            [$id, $userId]
        )->fetch();
        return $row ?: null;
    }

    public function amenityIdsForListing(int $propertyId): array
    {
        return array_map(
            'intval',
            $this->query(
                'SELECT amenity_id FROM property_amenities WHERE property_id = ? ORDER BY amenity_id ASC',
                [$propertyId]
            )->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function syncAmenities(int $propertyId, array $amenityIds): void
    {
        $this->query('DELETE FROM property_amenities WHERE property_id = ?', [$propertyId]);
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $amenityIds), fn($v) => $v > 0)));
        foreach ($uniqueIds as $amenityId) {
            $this->query(
                'INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)',
                [$propertyId, $amenityId]
            );
        }
    }

    public function updateOwnedListing(
        int $id,
        int $userId,
        array $data,
        ?array $amenityIds = null,
        array $newImagePaths = [],
        array $deleteImageIds = [],
        ?int $coverImageId = null
    ): bool {
        $current = $this->findOwned($id, $userId);
        if (!$current) {
            return false;
        }

        $wardId = !empty($data['ward_id']) ? (int)$data['ward_id'] : (int)$current['ward_id'];
        $direction = array_key_exists('direction', $data)
            ? ($data['direction'] !== null && trim((string)$data['direction']) !== '' ? trim((string)$data['direction']) : null)
            : $current['direction'];
        $legalStatus = array_key_exists('legal_status', $data)
            ? ($data['legal_status'] !== null && trim((string)$data['legal_status']) !== '' ? trim((string)$data['legal_status']) : null)
            : $current['legal_status'];
        $deposit = array_key_exists('deposit', $data)
            ? ($data['deposit'] !== null && $data['deposit'] !== '' ? (float)$data['deposit'] : null)
            : ($current['deposit'] !== null ? (float)$current['deposit'] : null);
        $furniture = array_key_exists('furniture', $data)
            ? ($data['furniture'] !== null && $data['furniture'] !== '' ? (string)$data['furniture'] : null)
            : $current['furniture'];
        $petsAllowed = array_key_exists('pets_allowed', $data)
            ? ((int)$data['pets_allowed'] ? 1 : 0)
            : (int)$current['pets_allowed'];
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : null;

        $this->db->beginTransaction();
        try {
            $this->query(
                "UPDATE properties
                 SET type_id = ?, ward_id = ?, title = ?, description = ?, listing_type = ?, price = ?, deposit = ?,
                     area = ?, bedrooms = ?, bathrooms = ?, direction = ?, legal_status = ?, furniture = ?,
                     pets_allowed = ?, address_detail = ?, latitude = ?, longitude = ?,
                     status = 'pending', reject_reason = NULL
                 WHERE id = ? AND user_id = ?",
                [
                    (int)$data['type_id'], $wardId, $data['title'], $data['description'], $data['listing_type'],
                    (float)$data['price'], $deposit, (float)$data['area'], (int)$data['bedrooms'], (int)$data['bathrooms'],
                    $direction, $legalStatus, $furniture, $petsAllowed,
                    $data['address_detail'], $lat, $lng, $id, $userId,
                ]
            );

            if ($amenityIds === null && isset($data['amenities']) && is_array($data['amenities'])) {
                $amenityIds = $data['amenities'];
            }
            if ($amenityIds !== null) {
                $this->syncAmenities($id, $amenityIds);
            }

            if (!empty($deleteImageIds)) {
                $cleanDelIds = array_values(array_filter(array_map('intval', $deleteImageIds), fn($v) => $v > 0));
                if (!empty($cleanDelIds)) {
                    $marks = implode(',', array_fill(0, count($cleanDelIds), '?'));
                    $this->query(
                        "DELETE FROM property_images WHERE property_id = ? AND id IN ($marks)",
                        [$id, ...$cleanDelIds]
                    );
                }
            }

            if (!empty($newImagePaths)) {
                $propertyImage = new PropertyImage();
                foreach ($newImagePaths as $imgPath) {
                    $propertyImage->createForProperty($id, $imgPath, false);
                }
            }

            if ($coverImageId !== null && $coverImageId > 0) {
                $hasCoverTarget = (bool)$this->query(
                    'SELECT id FROM property_images WHERE id = ? AND property_id = ?',
                    [$coverImageId, $id]
                )->fetchColumn();
                if ($hasCoverTarget) {
                    $this->query('UPDATE property_images SET is_cover = 0 WHERE property_id = ?', [$id]);
                    $this->query('UPDATE property_images SET is_cover = 1 WHERE id = ? AND property_id = ?', [$coverImageId, $id]);
                }
            }

            $hasCover = (bool)$this->query(
                'SELECT id FROM property_images WHERE property_id = ? AND is_cover = 1 LIMIT 1',
                [$id]
            )->fetchColumn();
            if (!$hasCover) {
                $firstImgId = (int)$this->query(
                    'SELECT id FROM property_images WHERE property_id = ? ORDER BY id ASC LIMIT 1',
                    [$id]
                )->fetchColumn();
                if ($firstImgId > 0) {
                    $this->query('UPDATE property_images SET is_cover = 1 WHERE id = ?', [$firstImgId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
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

    /**
     * Lấy danh sách bất động sản tương tự (ưu tiên cùng quận/huyện, cùng loại BĐS và chênh lệch giá nhỏ nhất)
     */
    public function getSimilarProperties(int $currentId, int $districtId, int $typeId, float $price, int $limit = 4): array
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
                       p.direction, p.is_verified, p.latitude, p.longitude, p.created_at, p.address_detail,
                       t.id AS type_id, t.name AS type_name,
                       w.id AS ward_id, w.name AS ward_name,
                       d.id AS district_id, d.name AS district_name,
                       pr.id AS province_id, pr.name AS province_name,
                       (SELECT image_url FROM property_images i
                        WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url
                FROM properties p
                JOIN property_types t ON t.id = p.type_id
                JOIN wards w ON w.id = p.ward_id
                JOIN districts d ON d.id = w.district_id
                JOIN provinces pr ON pr.id = d.province_id
                WHERE p.status = 'approved'
                  AND p.id != :currentId
                  AND (d.id = :districtIdFilter OR p.type_id = :typeIdFilter)
                ORDER BY
                    (CASE
                        WHEN d.id = :districtIdOrder AND p.type_id = :typeIdOrder THEN 0
                        WHEN d.id = :districtIdSecond THEN 1
                        WHEN p.type_id = :typeIdSecond THEN 2
                        ELSE 3
                    END) ASC,
                    ABS(p.price - :price) ASC,
                    p.created_at DESC
                LIMIT {$limit}";

        $rows = $this->query($sql, [
            ':currentId'        => $currentId,
            ':districtIdFilter' => $districtId,
            ':typeIdFilter'     => $typeId,
            ':districtIdOrder'  => $districtId,
            ':typeIdOrder'      => $typeId,
            ':districtIdSecond' => $districtId,
            ':typeIdSecond'     => $typeId,
            ':price'            => $price,
        ])->fetchAll();

        // Nếu ít kết quả hơn $limit thì lấy bổ sung các tin đã duyệt khác có mức giá gần nhất
        if (count($rows) < $limit) {
            $excludeIds = array_merge([$currentId], array_map(fn($r) => (int)$r['id'], $rows));
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $needed = $limit - count($rows);
            $fallbackSql = "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
                                   p.direction, p.is_verified, p.latitude, p.longitude, p.created_at, p.address_detail,
                                   t.id AS type_id, t.name AS type_name,
                                   w.id AS ward_id, w.name AS ward_name,
                                   d.id AS district_id, d.name AS district_name,
                                   pr.id AS province_id, pr.name AS province_name,
                                   (SELECT image_url FROM property_images i
                                    WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url
                            FROM properties p
                            JOIN property_types t ON t.id = p.type_id
                            JOIN wards w ON w.id = p.ward_id
                            JOIN districts d ON d.id = w.district_id
                            JOIN provinces pr ON pr.id = d.province_id
                            WHERE p.status = 'approved'
                              AND p.id NOT IN ({$placeholders})
                            ORDER BY ABS(p.price - ?) ASC, p.created_at DESC
                            LIMIT {$needed}";
            $fallbackRows = $this->query($fallbackSql, [...$excludeIds, $price])->fetchAll();
            $rows = array_merge($rows, $fallbackRows);
        }

        foreach ($rows as &$row) {
            $row['avatar'] = $row['cover_url'] ?? null;
            $row['thumbnail'] = $row['cover_url'] ?? null;
            $row['avatar_url'] = property_avatar_url($row['cover_url'] ?? null);
        }
        unset($row);

        return $rows;
    }
}
