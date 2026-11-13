<?php
// [D] Lịch hẹn xem nhà (viewing_appointments)
class Appointment extends Model
{
    protected string $table = 'viewing_appointments';
    protected array $fillable = ['property_id', 'customer_id', 'scheduled_at', 'note', 'status'];

    /** Khách đặt lịch. Trả id mới, 0 nếu tin không tồn tại hoặc chưa được duyệt. */
    public function book(int $propertyId, int $customerId, string $scheduledAt, string $note = ''): int
    {
        $rows = $this->call('sp_appointment_book', [$propertyId, $customerId, $scheduledAt, $note]);
        return (int)($rows[0]['id'] ?? 0);
    }

    /** Khách đã có lịch đang chờ hoặc đã xác nhận cho tin này chưa. */
    public function hasActiveBooking(int $propertyId, int $customerId): bool
    {
        return $this->call('sp_appointment_has_active', [$propertyId, $customerId]) !== [];
    }

    /** Lịch hẹn của một khách. */
    public function forCustomer(int $customerId): array
    {
        return $this->call('sp_appointment_for_customer', [$customerId]);
    }

    /** Lịch hẹn của các tin do môi giới này đăng (môi giới suy ra từ properties.user_id). */
    public function forAgent(int $agentId): array
    {
        return $this->call('sp_appointment_for_agent', [$agentId]);
    }

    /** Môi giới xác nhận / hủy / đánh dấu đã xem. Chỉ đổi được lịch thuộc tin của mình. */
    public function changeStatusByAgent(int $id, int $agentId, string $status): bool
    {
        if (!in_array($status, ['confirmed', 'cancelled', 'done'], true)) {
            return false;
        }
        $rows = $this->call('sp_appointment_agent_status', [$id, $agentId, $status]);
        return (int)($rows[0]['affected'] ?? 0) > 0;
    }

    /** Khách tự hủy lịch của mình. */
    public function cancelByCustomer(int $id, int $customerId): bool
    {
        $rows = $this->call('sp_appointment_customer_cancel', [$id, $customerId]);
        return (int)($rows[0]['affected'] ?? 0) > 0;
    }

    /** Lấy chi tiết lịch hẹn kèm thông tin bất động sản và môi giới. */
    public function findWithProperty(int $id): ?array
    {
        $row = $this->routine('sp_rt_appointment_find_with_property_1', [$id])->fetch();
        return $row ?: null;
    }
}
