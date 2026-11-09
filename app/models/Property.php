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

    public function findOwned(int $id, int $userId): ?array
    {
        return $this->call('sp_agent_property_by_id', [$id, $userId])[0] ?? null;
    }

    public function updateOwnedListing(int $id, int $userId, array $data): bool
    {
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : null;
        $r = $this->call('sp_agent_property_update', [$id, $userId, $data['type_id'], $data['title'], $data['description'], $data['listing_type'], $data['price'], $data['area'], $data['bedrooms'], $data['bathrooms'], $data['address_detail'], $lat, $lng])[0] ?? [];
        return !empty($r['affected']) || !empty($r['owned']);
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
     * Lấy danh sách bất động sản tương tự (ưu tiên cùng quận/huyện, cùng loại BĐS và chênh lệch giá nhỏ nhất)
     */
    public function getSimilarProperties(int $currentId, int $districtId, int $typeId, float $price, int $limit = 4): array
    {
        $rows = $this->call('sp_property_similar_ranked', [$currentId, $districtId, $typeId, $price, max(1, $limit)]);

        foreach ($rows as &$row) {
            $row['avatar'] = $row['cover_url'] ?? null;
            $row['thumbnail'] = $row['cover_url'] ?? null;
            $row['avatar_url'] = property_avatar_url($row['cover_url'] ?? null);
        }
        unset($row);

        return $rows;
    }
}
