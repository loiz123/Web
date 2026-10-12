<?php
// [C] Bộ lọc đã lưu
class SavedSearch extends Model
{
    protected string $table = 'saved_searches';
    protected array $fillable = ['user_id', 'name', 'filters'];
    // TODO: thêm phương thức truy vấn của module
}
