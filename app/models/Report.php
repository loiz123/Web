<?php
// [D] Báo cáo tin vi phạm
class Report extends Model
{
    protected string $table = 'reports';
    protected array $fillable = ['property_id', 'reporter_id', 'reason', 'status'];
    // TODO: thêm phương thức truy vấn của module
}
