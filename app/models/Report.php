<?php
// Báo cáo tin vi phạm (reports)
class Report extends Model
{
    protected string $table = 'reports';
    protected array $fillable = ['property_id', 'reporter_id', 'reason', 'description', 'status', 'admin_notes'];

    public function hasOpenReport(int $propertyId, int $reporterId): bool
    {
        return (bool)$this->query(
            "SELECT 1 FROM reports WHERE property_id = ? AND reporter_id = ? AND status IN ('open', 'pending')",
            [$propertyId, $reporterId]
        )->fetchColumn();
    }

    /**
     * Khách báo cáo tin. Trả id mới, 0 nếu tin chưa được duyệt (không phải approved) hoặc đã có báo cáo đang mở của người này.
     */
    public function submit(int $propertyId, int $reporterId, string $reason, ?string $description = null): int
    {
        $exists = $this->query("SELECT 1 FROM properties WHERE id = ? AND status = 'approved'", [$propertyId])->fetchColumn();
        if (!$exists || $this->hasOpenReport($propertyId, $reporterId)) {
            return 0;
        }
        $this->query(
            "INSERT INTO reports (property_id, reporter_id, reason, description, status) VALUES (?, ?, ?, ?, 'pending')",
            [$propertyId, $reporterId, mb_substr(trim($reason), 0, 255), $description ? trim($description) : null]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Lấy 1 báo cáo kèm đầy đủ thông tin tin đăng và người báo cáo
     */
    public function getDetail(int $id): ?array
    {
        $row = $this->query(
            "SELECT r.*, 
                    p.title AS property_title, p.price AS property_price, p.status AS property_status, p.user_id AS agent_id,
                    u.full_name AS reporter_name, u.email AS reporter_email,
                    agent.full_name AS agent_name, agent.email AS agent_email
             FROM reports r
             JOIN properties p ON p.id = r.property_id
             LEFT JOIN users u ON u.id = r.reporter_id
             LEFT JOIN users agent ON agent.id = p.user_id
             WHERE r.id = ? LIMIT 1",
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Danh sách cho admin, có phân trang. $status = null hoặc 'all' để lấy tất cả.
     */
    public function paginate(?string $status = null, int $page = 1, int $perPage = 10, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            if ($status === 'pending' || $status === 'open') {
                $conditions[] = "r.status IN ('open', 'pending')";
            } elseif (in_array($status, ['resolved', 'dismissed'], true)) {
                $conditions[] = "r.status = ?";
                $params[] = $status;
            }
        }

        if (trim($search) !== '') {
            $conditions[] = "(p.title LIKE ? OR r.reason LIKE ? OR r.description LIKE ? OR u.full_name LIKE ?)";
            $kw = '%' . trim($search) . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $total = (int)$this->query(
            "SELECT COUNT(*) FROM reports r
             JOIN properties p ON p.id = r.property_id
             LEFT JOIN users u ON u.id = r.reporter_id
             $where",
            $params
        )->fetchColumn();

        $items = $this->query(
            "SELECT r.*, 
                    p.title AS property_title, p.price AS property_price, p.status AS property_status, p.user_id AS agent_id,
                    u.full_name AS reporter_name, u.email AS reporter_email,
                    agent.full_name AS agent_name, agent.email AS agent_email
             FROM reports r
             JOIN properties p ON p.id = r.property_id
             LEFT JOIN users u ON u.id = r.reporter_id
             LEFT JOIN users agent ON agent.id = p.user_id
             $where
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public function countOpen(): int
    {
        return (int)$this->query("SELECT COUNT(*) FROM reports WHERE status IN ('open', 'pending')")->fetchColumn();
    }

    /**
     * Admin xử lý: resolved hoặc dismissed. Nếu $hideProperty thì ẩn luôn tin vi phạm.
     */
    public function resolve(int $id, string $newStatus, bool $hideProperty = false, ?string $adminNotes = null): bool
    {
        if (!in_array($newStatus, ['resolved', 'dismissed'], true)) {
            return false;
        }

        $report = $this->getDetail($id);
        if (!$report) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->query(
                "UPDATE reports SET status = ?, admin_notes = COALESCE(?, admin_notes), updated_at = NOW() WHERE id = ?",
                [$newStatus, $adminNotes, $id]
            );
            if ($stmt->rowCount() === 0 && $report['status'] === $newStatus) {
                // Đã ở trạng thái này rồi nhưng vẫn đảm bảo nếu cần ẩn tin
            }

            if ($hideProperty && $newStatus === 'resolved') {
                $this->query(
                    "UPDATE properties SET status = 'hidden', updated_at = NOW() WHERE id = ?",
                    [(int)$report['property_id']]
                );
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
