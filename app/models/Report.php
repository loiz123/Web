<?php
// [D] Báo cáo tin vi phạm (reports)
class Report extends Model
{
    protected string $table = 'reports';
    protected array $fillable = ['property_id', 'reporter_id', 'reason', 'status'];

    public function hasOpenReport(int $propertyId, int $reporterId): bool
    {
        return (bool)$this->query(
            "SELECT 1 FROM reports WHERE property_id = ? AND reporter_id = ? AND status = 'open'",
            [$propertyId, $reporterId]
        )->fetchColumn();
    }

    /** Khách báo cáo tin. Trả id mới, 0 nếu tin không tồn tại hoặc đã có báo cáo đang mở của người này. */
    public function submit(int $propertyId, int $reporterId, string $reason): int
    {
        $exists = $this->query('SELECT 1 FROM properties WHERE id = ?', [$propertyId])->fetchColumn();
        if (!$exists || $this->hasOpenReport($propertyId, $reporterId)) {
            return 0;
        }
        $this->query(
            'INSERT INTO reports (property_id, reporter_id, reason) VALUES (?, ?, ?)',
            [$propertyId, $reporterId, mb_substr(trim($reason), 0, 255)]
        );
        return (int)$this->db->lastInsertId();
    }

    /** Danh sách cho admin, có phân trang. $status = null để lấy tất cả. */
    public function paginate(?string $status = 'open', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($status !== null && in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            $where = 'WHERE r.status = ?';
            $params[] = $status;
        }

        $total = (int)$this->query("SELECT COUNT(*) FROM reports r $where", $params)->fetchColumn();
        $items = $this->query(
            "SELECT r.*, p.title AS property_title, p.status AS property_status, u.full_name AS reporter_name
             FROM reports r
             JOIN properties p ON p.id = r.property_id
             JOIN users u ON u.id = r.reporter_id
             $where
             ORDER BY r.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();

        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    public function countOpen(): int
    {
        return (int)$this->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn();
    }

    /** Admin xử lý: resolved hoặc dismissed. Nếu $hideProperty thì ẩn luôn tin vi phạm. */
    public function resolve(int $id, string $newStatus, bool $hideProperty = false): bool
    {
        if (!in_array($newStatus, ['resolved', 'dismissed'], true)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->query("UPDATE reports SET status = ? WHERE id = ? AND status = 'open'", [$newStatus, $id]);
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }
            if ($hideProperty && $newStatus === 'resolved') {
                $propId = (int)$this->query('SELECT property_id FROM reports WHERE id = ?', [$id])->fetchColumn();
                $this->query("UPDATE properties SET status = 'hidden' WHERE id = ? AND status = 'approved'", [$propId]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
