<?php
// [D] Duyệt tin của admin. Dùng routine sp_moderate_property, tự chuyển sang SQL thường khi use_routines = false.
class PropertyModeration extends Model
{
    protected string $table = 'properties';

    public function paginate(string $status = 'pending', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;
        if (!in_array($status, ['pending', 'approved', 'rejected', 'hidden', 'closed'], true)) {
            $status = 'pending';
        }

        $total = (int)$this->query('SELECT COUNT(*) FROM properties WHERE status = ?', [$status])->fetchColumn();
        $items = $this->query(
            "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.address_detail, p.status,
                    p.reject_reason, p.created_at, u.full_name AS agent_name
             FROM properties p
             JOIN users u ON u.id = p.user_id
             WHERE p.status = ?
             ORDER BY p.created_at ASC
             LIMIT $perPage OFFSET $offset",
            [$status]
        )->fetchAll();

        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    /** Số tin theo trạng thái (cho dashboard). */
    public function countByStatus(): array
    {
        return $this->query('SELECT status, COUNT(*) AS total FROM properties GROUP BY status')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** @return array{ok: bool, message: string} */
    public function moderate(int $id, string $action, string $reason = ''): array
    {
        if (!in_array($action, ['approve', 'reject', 'delete'], true)) {
            return ['ok' => false, 'message' => 'Hành động không hợp lệ.'];
        }
        $reason = mb_substr(trim($reason), 0, 255);
        if ($action === 'reject' && $reason === '') {
            return ['ok' => false, 'message' => 'Vui lòng nhập lý do từ chối.'];
        }

        if (config('use_routines')) {
            try {
                $this->query('CALL sp_moderate_property(?, ?, ?)', [$id, $action, $reason]);
                return ['ok' => true, 'message' => $this->successMessage($action)];
            } catch (PDOException $e) {
                $code = (int)($e->errorInfo[1] ?? 0);
                if ($code === 1644) {            // lỗi nghiệp vụ do SIGNAL trong routine
                    return ['ok' => false, 'message' => (string)($e->errorInfo[2] ?? 'Không thực hiện được.')];
                }
                if ($code !== 1305) {            // 1305 = routine chưa được cài; lỗi khác thì ném tiếp
                    throw $e;
                }
                // routine chưa import: rơi xuống SQL thường
            }
        }
        return $this->moderateSql($id, $action, $reason);
    }

    private function moderateSql(int $id, string $action, string $reason): array
    {
        if ($action === 'approve') {
            $stmt = $this->query(
                "UPDATE properties SET status = 'approved', reject_reason = NULL WHERE id = ? AND status = 'pending'",
                [$id]
            );
        } elseif ($action === 'reject') {
            $stmt = $this->query(
                "UPDATE properties SET status = 'rejected', reject_reason = ? WHERE id = ? AND status = 'pending'",
                [$reason, $id]
            );
        } else {
            $stmt = $this->query('DELETE FROM properties WHERE id = ?', [$id]);
        }

        if ($stmt->rowCount() > 0) {
            return ['ok' => true, 'message' => $this->successMessage($action)];
        }
        $exists = $this->query('SELECT 1 FROM properties WHERE id = ?', [$id])->fetchColumn();
        return ['ok' => false, 'message' => $exists
            ? 'Chỉ duyệt hoặc từ chối được tin đang chờ duyệt.'
            : 'Tin không tồn tại hoặc đã bị xóa.'];
    }

    private function successMessage(string $action): string
    {
        return ['approve' => 'Đã duyệt tin.', 'reject' => 'Đã từ chối tin.', 'delete' => 'Đã xóa tin.'][$action];
    }
}
