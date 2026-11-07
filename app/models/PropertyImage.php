<?php
// [B] Ảnh tin đăng
class PropertyImage extends Model
{
    protected string $table = 'property_images';
    protected array $fillable = ['property_id', 'image_url', 'is_cover'];

    public function findByPropertyId(int $propertyId): array
    {
        return $this->query(
            'SELECT id, property_id, image_url, is_cover
             FROM property_images
             WHERE property_id = ?
             ORDER BY is_cover DESC, id ASC',
            [$propertyId]
        )->fetchAll();
    }

    public function urlsByPropertyId(int $propertyId): array
    {
        return $this->query(
            'SELECT image_url
             FROM property_images
             WHERE property_id = ?
             ORDER BY is_cover DESC, id ASC',
            [$propertyId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function coverByPropertyId(int $propertyId): ?string
    {
        $imageUrl = $this->query(
            'SELECT image_url
             FROM property_images
             WHERE property_id = ?
             ORDER BY is_cover DESC, id ASC
             LIMIT 1',
            [$propertyId]
        )->fetchColumn();

        return $imageUrl === false ? null : (string)$imageUrl;
    }

    public function createForProperty(int $propertyId, string $imageUrl, bool $isCover = false): int
    {
        return $this->create([
            'property_id' => $propertyId,
            'image_url' => $imageUrl,
            'is_cover' => $isCover ? 1 : 0,
        ]);
    }

    public function deleteByPropertyId(int $propertyId): bool
    {
        return $this->query(
            'DELETE FROM property_images WHERE property_id = ?',
            [$propertyId]
        )->rowCount() > 0;
    }
}
