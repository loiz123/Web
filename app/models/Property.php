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
