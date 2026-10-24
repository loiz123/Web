<?php
// [D] Lịch hẹn xem nhà (viewing_appointments)
class Appointment extends Model
{
    protected string $table = 'viewing_appointments';
    protected array $fillable = ['property_id', 'customer_id', 'scheduled_at', 'note', 'status'];

    /** Khách đặt lịch. Trả id mới, 0 nếu tin không tồn tại hoặc chưa được duyệt. */
    public function book(int $propertyId, int $customerId, string $scheduledAt, string $note = ''): int
    {
        $ok = $this->query("SELECT 1 FROM properties WHERE id = ? AND status = 'approved'", [$propertyId])->fetchColumn();
        if (!$ok) {
            return 0;
        }
        $this->query(
            'INSERT INTO viewing_appointments (property_id, customer_id, scheduled_at, note) VALUES (?, ?, ?, ?)',
            [$propertyId, $customerId, $scheduledAt, $note]
        );
        return (int)$this->db->lastInsertId();
    }

    /** Lịch hẹn của một khách. */
    public function forCustomer(int $customerId): array
    {
        return $this->query(
            'SELECT va.*, p.title AS property_title
             FROM viewing_appointments va
             JOIN properties p ON p.id = va.property_id
             WHERE va.customer_id = ?
             ORDER BY va.scheduled_at DESC',
            [$customerId]
        )->fetchAll();
    }

    /** Lịch hẹn của các tin do môi giới này đăng (môi giới suy ra từ properties.user_id). */
    public function forAgent(int $agentId): array
    {
        return $this->query(
            'SELECT va.*, p.title AS property_title, u.full_name AS customer_name, u.phone AS customer_phone
             FROM viewing_appointments va
             JOIN properties p ON p.id = va.property_id
             JOIN users u ON u.id = va.customer_id
             WHERE p.user_id = ?
             ORDER BY va.scheduled_at DESC',
            [$agentId]
        )->fetchAll();
    }

    /** Môi giới xác nhận / hủy / đánh dấu đã xem. Chỉ đổi được lịch thuộc tin của mình. */
    public function changeStatusByAgent(int $id, int $agentId, string $status): bool
    {
        if (!in_array($status, ['confirmed', 'cancelled', 'done'], true)) {
            return false;
        }
        $stmt = $this->query(
            "UPDATE viewing_appointments va
             JOIN properties p ON p.id = va.property_id
             SET va.status = ?
             WHERE va.id = ? AND p.user_id = ? AND va.status IN ('pending', 'confirmed')",
            [$status, $id, $agentId]
        );
        return $stmt->rowCount() > 0;
    }

    /** Khách tự hủy lịch của mình. */
    public function cancelByCustomer(int $id, int $customerId): bool
    {
        $stmt = $this->query(
            "UPDATE viewing_appointments SET status = 'cancelled'
             WHERE id = ? AND customer_id = ? AND status IN ('pending', 'confirmed')",
            [$id, $customerId]
        );
        return $stmt->rowCount() > 0;
    }
}
