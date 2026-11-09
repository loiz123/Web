<?php
// [D] Duyệt tin của admin qua stored procedure.
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

        $total = (int)($this->call('sp_moderation_count', [$status])[0]['total'] ?? 0);
        $items = $this->call('sp_moderation_list', [$status, $perPage, $offset]);

        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    /** Số tin theo trạng thái (cho dashboard). */
    public function countByStatus(): array
    {
        $result = [];
        foreach ($this->call('sp_dashboard_by_status') as $row) $result[$row['status']] = $row['total'];
        return $result;
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

        $result = $this->call('sp_moderation_apply', [$id, $action, $reason])[0] ?? [];
        if ((int)($result['affected'] ?? 0) > 0) {
            return ['ok' => true, 'message' => $this->successMessage($action)];
        }
        return ['ok' => false, 'message' => !empty($result['exists_after'])
            ? 'Chỉ duyệt hoặc từ chối được tin đang chờ duyệt.'
            : 'Tin không tồn tại hoặc đã bị xóa.'];
    }

    private function successMessage(string $action): string
    {
        return ['approve' => 'Đã duyệt tin.', 'reject' => 'Đã từ chối tin.', 'delete' => 'Đã xóa tin.'][$action];
    }
}
