<?php
// [B] Ảnh tin đăng
class PropertyImage extends Model
{
    protected string $table = 'property_images';
    protected array $fillable = ['property_id', 'image_url', 'is_cover'];

    public function findByProperty(int $propertyId): array
    {
        return $this->query(
            'SELECT * FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, id ASC',
            [$propertyId]
        )->fetchAll();
    }
}
