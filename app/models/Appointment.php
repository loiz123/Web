<?php
// [D] Lịch hẹn xem nhà
class Appointment extends Model
{
    protected string $table = 'viewing_appointments';
    protected array $fillable = ['property_id', 'customer_id', 'scheduled_at', 'note', 'status'];
    // TODO: thêm phương thức truy vấn của module
}
