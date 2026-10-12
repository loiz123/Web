<?php
// [D] Đánh giá
class Review extends Model
{
    protected string $table = 'reviews';
    protected array $fillable = ['property_id', 'user_id', 'rating', 'comment'];
    // TODO: thêm phương thức truy vấn của module
}
