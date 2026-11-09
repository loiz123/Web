<?php
// [B] Ảnh tin đăng
class PropertyImage extends Model
{
    protected string $table = 'property_images';
    protected array $fillable = ['property_id', 'image_url', 'is_cover'];

    public function findByPropertyId(int $propertyId): array
    {
        return $this->call('sp_property_images', [$propertyId]);
    }

    public function urlsByPropertyId(int $propertyId): array
    {
        return array_column($this->findByPropertyId($propertyId), 'image_url');
    }

    public function coverByPropertyId(int $propertyId): ?string
    {
        return $this->findByPropertyId($propertyId)[0]['image_url'] ?? null;
    }

    public function createForProperty(int $propertyId, string $imageUrl, bool $isCover = false): int
    {
        return (int)($this->call('sp_property_image_create', [$propertyId, $imageUrl, $isCover ? 1 : 0])[0]['id'] ?? 0);
    }

    public function deleteByPropertyId(int $propertyId): bool
    {
        return (int)($this->call('sp_property_images_delete', [$propertyId])[0]['affected'] ?? 0) > 0;
    }
}
