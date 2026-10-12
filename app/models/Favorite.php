<?php
// [D] Yêu thích (khóa chính kép user_id + property_id nên find()/delete() theo id không dùng được: tự viết query)
class Favorite extends Model
{
    protected string $table = 'favorites';
    protected array $fillable = ['user_id', 'property_id'];
    // TODO: thêm phương thức truy vấn của module
}
