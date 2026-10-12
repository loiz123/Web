<?php
// [B] Ảnh tin đăng
class PropertyImage extends Model
{
    protected string $table = 'property_images';
    protected array $fillable = ['property_id', 'image_url', 'is_cover'];
    // TODO: thêm phương thức truy vấn của module
}
