<?php
// [B] Tin đăng - Hồ Duy Hải (@haiho2365-source) phụ trách (CRUD, upload ảnh, đổi trạng thái)
// Lưu ý: Các hàm latest() và search() đã được chuyển sang PropertyFinder.php (Hà Ngọc Thiện phụ trách)
class Property extends Model
{
    protected string $table = 'properties';
    protected array $fillable = [
        'user_id', 'type_id', 'ward_id', 'title', 'description', 'listing_type', 'price', 'deposit', 'area',
        'bedrooms', 'bathrooms', 'direction', 'legal_status', 'furniture', 'pets_allowed', 'address_detail',
        'latitude', 'longitude', 'status', 'reject_reason', 'is_verified', 'expired_at',
    ];

    public function typeExists(int $typeId): bool
    {
        return (new PropertyType())->find($typeId) !== null;
    }

    public function find(int $id): ?array
    {
        return $this->call('sp_agent_property_by_id', [$id, null])[0] ?? null;
    }

    public function listingsForUser(int $userId, ?string $status = null): array
    {
        return $this->call('sp_agent_property_list', [$userId, $status]);
    }



    /**
     * Gia hạn thời gian hiển thị của tin đăng thêm $days ngày (mặc định 30 ngày).
     */
    public function renewOwnedListing(int $id, int $userId, int $days = 30): bool
    {
        $days = max(1, min(365, $days));
        $stmt = $this->routine('sp_rt_property_renew_owned_listing_1', [...[$id, $userId], $days, $days]);
        return $stmt->rowCount() > 0;
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $row = $this->routine('sp_rt_property_find_owned_1', [$id, $userId])->fetch();
        return $row ?: null;
    }

    public function amenityIdsForListing(int $propertyId): array
    {
        return array_map(
            'intval',
            $this->routine('sp_rt_property_amenity_ids_for_listing_1', [$propertyId])->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function syncAmenities(int $propertyId, array $amenityIds): void
    {
        $this->routine('sp_rt_property_sync_amenities_1', [$propertyId]);
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $amenityIds), fn($v) => $v > 0)));
        foreach ($uniqueIds as $amenityId) {
            $this->routine('sp_rt_property_sync_amenities_2', [$propertyId, $amenityId]);
        }
    }

    public function updateOwnedListing(
        int $id,
        int $userId,
        array $data,
        ?array $amenityIds = null,
        array $newImagePaths = [],
        array $deleteImageIds = [],
        ?int $coverImageId = null,
        ?int $newCoverIndex = null
    ): bool {
        $current = $this->findOwned($id, $userId);
        if (!$current) {
            return false;
        }
        if ($newCoverIndex !== null && ($newCoverIndex < 0 || !array_key_exists($newCoverIndex, $newImagePaths))) {
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
            $this->routine('sp_rt_property_update_owned_listing_1', [
                    (int)$data['type_id'], $wardId, $data['title'], $data['description'], $data['listing_type'],
                    (float)$data['price'], $deposit, (float)$data['area'], (int)$data['bedrooms'], (int)$data['bathrooms'],
                    $direction, $legalStatus, $furniture, $petsAllowed,
                    $data['address_detail'], $lat, $lng, $id, $userId,
                ]);

            if ($amenityIds === null && isset($data['amenities']) && is_array($data['amenities'])) {
                $amenityIds = $data['amenities'];
            }
            if ($amenityIds !== null) {
                $this->syncAmenities($id, $amenityIds);
            }

            if (!empty($deleteImageIds)) {
                $cleanDelIds = array_values(array_filter(array_map('intval', $deleteImageIds), fn($v) => $v > 0));
                if (!empty($cleanDelIds)) {
                    $this->call('sp_property_images_remove_ids', [$id, implode(',', $cleanDelIds)]);
                }
            }

            if (!empty($newImagePaths)) {
                $propertyImage = new PropertyImage();
                foreach ($newImagePaths as $index => $imgPath) {
                    $newId = $propertyImage->createForProperty($id, $imgPath, false);
                    if ($newCoverIndex !== null && $index === $newCoverIndex) {
                        $coverImageId = $newId;
                    }
                }
            }

            if ($coverImageId !== null && $coverImageId > 0) {
                $hasCoverTarget = (bool)$this->routine('sp_rt_property_update_owned_listing_2', [$coverImageId, $id])->fetchColumn();
                if ($hasCoverTarget) {
                    $this->routine('sp_rt_property_update_owned_listing_3', [$id]);
                    $this->routine('sp_rt_property_update_owned_listing_4', [$coverImageId, $id]);
                }
            }

            $hasCover = (bool)$this->routine('sp_rt_property_update_owned_listing_5', [$id])->fetchColumn();
            if (!$hasCover) {
                $firstImgId = (int)$this->routine('sp_rt_property_update_owned_listing_6', [$id])->fetchColumn();
                if ($firstImgId > 0) {
                    $this->routine('sp_rt_property_update_owned_listing_7', [$firstImgId]);
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
        if (!in_array($status, ['pending', 'hidden', 'closed'], true)) return false;
        return (int)($this->call('sp_agent_property_status', [$id, $userId, $status])[0]['affected'] ?? 0) > 0;
    }

    public function imagesForListing(int $id): array
    {
        return (new PropertyImage())->urlsByPropertyId($id);
    }

    public function deleteOwned(int $id, int $userId): bool
    {
        return (int)($this->call('sp_agent_property_delete', [$id, $userId])[0]['affected'] ?? 0) > 0;
    }

    public function createListing(array $data, array $imagePaths, array $amenityIds, int $coverIndex): int
    {
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : null;
        $this->db->beginTransaction();
        try {
            $r = $this->call('sp_agent_property_create', [$data['user_id'], $data['type_id'], $data['ward_id'], $data['title'], $data['description'], $data['listing_type'], $data['price'], $data['deposit'], $data['area'], $data['bedrooms'], $data['bathrooms'], $data['direction'], $data['legal_status'], $data['furniture'], $data['pets_allowed'], $data['address_detail'], $lat, $lng]);
            $propertyId = (int)($r[0]['id'] ?? 0);
            if ($propertyId <= 0) throw new RuntimeException('Khong the tao tin dang.');
            $propertyImage = new PropertyImage();
            foreach ($imagePaths as $index => $imagePath) $propertyImage->createForProperty($propertyId, $imagePath, $index === $coverIndex);
            foreach ($amenityIds as $amenityId) $this->call('sp_agent_property_add_amenity', [$propertyId, $amenityId]);
            $this->db->commit();
            return $propertyId;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /**
     * Lấy danh sách bất động sản tương tự (ưu tiên cùng phường/tỉnh, cùng loại BĐS và chênh lệch giá nhỏ nhất)
     */
    public function getSimilarProperties(int $currentId, int $districtId, int $typeId, float $price, int $limit = 4): array
    {
        $rows = $this->call('sp_property_similar_full', [$currentId, $districtId, $typeId, $price, max(1, $limit)]);

        foreach ($rows as &$row) {
            $row['avatar'] = $row['cover_url'] ?? null;
            $row['thumbnail'] = $row['cover_url'] ?? null;
            $row['avatar_url'] = property_avatar_url($row['cover_url'] ?? null);
        }
        unset($row);

        return $rows;
    }

    public static function toggleVerified(int $propertyId): array
    {
        return PropertyModeration::toggleVerified($propertyId);
    }

    public static function updateStatus(int $propertyId, string $status): array
    {
        return PropertyModeration::updateStatus($propertyId, $status);
    }
}
