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

        $prop = $this->query('SELECT id, user_id, title FROM properties WHERE id = ? LIMIT 1', [$id])->fetch();
        $result = null;

        if (config('use_routines')) {
            try {
                $res = $this->call('sp_moderation_apply', [$id, $action, $reason])[0] ?? [];
                if ((int)($res['affected'] ?? 0) > 0) {
                    if ($action === 'approve') {
                        $this->query(
                            'UPDATE properties SET expired_at = COALESCE(expired_at, DATE_ADD(NOW(), INTERVAL 30 DAY)) WHERE id = ?',
                            [$id]
                        );
                    }
                    $result = ['ok' => true, 'message' => $this->successMessage($action)];
                } else {
                    $result = [
                        'ok' => false,
                        'message' => !empty($res['exists_after'])
                            ? 'Chỉ duyệt hoặc từ chối được tin đang chờ duyệt.'
                            : 'Tin không tồn tại hoặc đã bị xóa.'
                    ];
                }
            } catch (PDOException $e) {
                $code = (int)($e->errorInfo[1] ?? 0);
                if ($code === 1644) {
                    return ['ok' => false, 'message' => (string)($e->errorInfo[2] ?? 'Không thực hiện được.')];
                }
                if ($code !== 1305) {
                    throw $e;
                }
            }
        }

        if ($result === null) {
            $result = $this->moderateSql($id, $action, $reason);
        }

        if (!empty($result['ok']) && $prop && in_array($action, ['approve', 'reject'], true)) {
            $this->notifyAgentModeration((int)$prop['user_id'], (int)$prop['id'], (string)$prop['title'], $action, $reason);
        }

        return $result;
    }

    private function moderateSql(int $id, string $action, string $reason): array
    {
        $exists = (bool)$this->query('SELECT 1 FROM properties WHERE id = ?', [$id])->fetchColumn();
        if (!$exists) {
            return ['ok' => false, 'message' => 'Tin không tồn tại hoặc đã bị xóa.'];
        }

        if ($action === 'approve') {
            $stmt = $this->query(
                "UPDATE properties SET status = 'approved', reject_reason = NULL, expired_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ? AND status = 'pending'",
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
        return ['ok' => false, 'message' => 'Chỉ duyệt hoặc từ chối được tin đang chờ duyệt.'];
    }

    private function notifyAgentModeration(int $agentId, int $propertyId, string $title, string $action, string $reason): void
    {
        try {
            $notif = new Notification();
            if ($action === 'approve') {
                $notif->create(
                    $agentId,
                    'Tin đăng đã được duyệt',
                    'Tin đăng "' . $title . '" của bạn đã được quản trị viên phê duyệt và hiển thị công khai (thời hạn 30 ngày).',
                    'property/detail/' . $propertyId
                );
            } elseif ($action === 'reject') {
                $notif->create(
                    $agentId,
                    'Tin đăng bị từ chối',
                    'Tin đăng "' . $title . '" chưa được duyệt. Lý do: ' . $reason,
                    'agent'
                );
            }
        } catch (Throwable $e) {
            error_log('Notification error on moderation: ' . $e->getMessage());
        }
    }

    private function successMessage(string $action): string
    {
        return ['approve' => 'Đã duyệt tin.', 'reject' => 'Đã từ chối tin.', 'delete' => 'Đã xóa tin.'][$action];
    }
}
