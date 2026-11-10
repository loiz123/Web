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

        if ($status === 'all') {
            $total = (int)$this->query('SELECT COUNT(*) FROM properties')->fetchColumn();
            $items = $this->query(
                'SELECT p.id, p.title, p.listing_type, p.price, p.area, p.address_detail, p.status, p.reject_reason, p.is_verified, p.created_at, u.full_name AS agent_name
                 FROM properties p
                 JOIN users u ON u.id = p.user_id
                 ORDER BY p.created_at DESC, p.id DESC
                 LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            )->fetchAll();

            return ['items' => $items, 'total' => $total, 'page' => $page];
        }

        if (!in_array($status, ['pending', 'approved', 'rejected', 'hidden', 'closed'], true)) {
            $status = 'pending';
        }

        if (config('use_routines')) {
            try {
                $total = (int)($this->call('sp_moderation_count', [$status])[0]['total'] ?? 0);
                $items = $this->call('sp_moderation_list', [$status, $perPage, $offset]);
                return ['items' => $items, 'total' => $total, 'page' => $page];
            } catch (PDOException $e) {
                $code = (int)($e->errorInfo[1] ?? 0);
                if ($code !== 1305) {
                    throw $e;
                }
            }
        }

        $total = (int)$this->query('SELECT COUNT(*) FROM properties WHERE status = ?', [$status])->fetchColumn();
        $items = $this->query(
            'SELECT p.id, p.title, p.listing_type, p.price, p.area, p.address_detail, p.status, p.reject_reason, p.is_verified, p.created_at, u.full_name AS agent_name
             FROM properties p
             JOIN users u ON u.id = p.user_id
             WHERE p.status = ?
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset,
            [$status]
        )->fetchAll();

        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    /** Số tin theo trạng thái (cho dashboard & các tab quản lý tin). */
    public function countByStatus(): array
    {
        $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'hidden' => 0, 'closed' => 0];
        try {
            $rows = $this->call('sp_dashboard_by_status');
        } catch (\Throwable $e) {
            $rows = $this->query('SELECT status, COUNT(*) AS total FROM properties GROUP BY status')->fetchAll();
        }
        foreach ($rows as $row) {
            if (isset($row['status'])) {
                $result[$row['status']] = (int)$row['total'];
            }
        }
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

        $prop = $this->query('SELECT id, user_id, title, status FROM properties WHERE id = ? LIMIT 1', [$id])->fetch();
        $result = null;

        if (config('use_routines') && (!$prop || ($prop['status'] ?? '') === 'pending' || $action === 'delete')) {
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
                "UPDATE properties SET status = 'approved', reject_reason = NULL, expired_at = COALESCE(expired_at, DATE_ADD(NOW(), INTERVAL 30 DAY)) WHERE id = ? AND status IN ('pending', 'hidden', 'rejected', 'closed')",
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

    public static function toggleVerified(int $propertyId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id, user_id, title, is_verified FROM properties WHERE id = ? LIMIT 1');
        $stmt->execute([$propertyId]);
        $prop = $stmt->fetch();

        if (!$prop) {
            return ['success' => false, 'message' => 'Tin đăng không tồn tại hoặc đã bị xóa.'];
        }

        $currentVal = (int)($prop['is_verified'] ?? 0);
        $newVal = $currentVal === 1 ? 0 : 1;

        $upStmt = $db->prepare('UPDATE properties SET is_verified = ? WHERE id = ?');
        $upStmt->execute([$newVal, $propertyId]);

        // Tạo thông báo in-app tự động cho chủ tin (user_id của tin đó) qua model Notification::create(...)
        try {
            $userId = (int)$prop['user_id'];
            $title = (string)$prop['title'];
            if ($userId > 0) {
                if ($newVal === 1) {
                    $notifTitle = 'Cấp huy hiệu Đã xác thực';
                    $notifMsg = "Tin đăng '{$title}' của bạn vừa được Quản trị viên cấp huy hiệu Đã xác thực!";
                    $notifType = 'success';
                } else {
                    $notifTitle = 'Gỡ huy hiệu xác thực';
                    $notifMsg = "Tin đăng '{$title}' của bạn đã gỡ huy hiệu xác thực.";
                    $notifType = 'warning';
                }
                Notification::create(
                    $userId,
                    $notifTitle,
                    $notifMsg,
                    'property/detail/' . $propertyId,
                    $notifType
                );
            }
        } catch (\Throwable $e) {
            error_log('Notification error on toggleVerified: ' . $e->getMessage());
        }

        $message = $newVal === 1
            ? "Tin đăng '{$prop['title']}' vừa được Quản trị viên cấp huy hiệu Đã xác thực!"
            : "Tin đăng '{$prop['title']}' đã gỡ huy hiệu xác thực.";

        return [
            'success' => true,
            'is_verified' => $newVal,
            'message' => $message,
        ];
    }

    public static function updateStatus(int $propertyId, string $status): array
    {
        $status = strtolower(trim($status));
        $validStatuses = ['approved', 'pending', 'hidden', 'rejected', 'deleted', 'closed'];
        if (!in_array($status, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ.'];
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id, user_id, title, status FROM properties WHERE id = ? LIMIT 1');
        $stmt->execute([$propertyId]);
        $prop = $stmt->fetch();

        if (!$prop) {
            return ['success' => false, 'message' => 'Tin đăng không tồn tại hoặc đã bị xóa.'];
        }

        if ($status === 'deleted') {
            $delStmt = $db->prepare('DELETE FROM properties WHERE id = ?');
            $delStmt->execute([$propertyId]);

            try {
                $userId = (int)$prop['user_id'];
                if ($userId > 0) {
                    Notification::create(
                        $userId,
                        'Tin đăng đã bị xóa',
                        "Tin đăng '{$prop['title']}' của bạn đã bị Quản trị viên xóa khỏi hệ thống.",
                        'agent',
                        'danger'
                    );
                }
            } catch (\Throwable $e) {
                error_log('Notification error on delete: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'status' => 'deleted',
                'message' => 'Đã xóa tin đăng thành công.',
            ];
        }

        if ($status === 'approved') {
            $upStmt = $db->prepare(
                "UPDATE properties SET status = 'approved', reject_reason = NULL, expired_at = COALESCE(expired_at, DATE_ADD(NOW(), INTERVAL 30 DAY)) WHERE id = ?"
            );
            $upStmt->execute([$propertyId]);
            $msg = 'Đã duyệt và công khai tin đăng thành công.';
        } elseif ($status === 'rejected') {
            $upStmt = $db->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
            $upStmt->execute([$propertyId]);
            $msg = 'Đã từ chối tin đăng.';
        } elseif ($status === 'hidden') {
            $upStmt = $db->prepare("UPDATE properties SET status = 'hidden' WHERE id = ?");
            $upStmt->execute([$propertyId]);
            $msg = 'Đã tạm ẩn tin đăng.';
        } elseif ($status === 'pending') {
            $upStmt = $db->prepare("UPDATE properties SET status = 'pending' WHERE id = ?");
            $upStmt->execute([$propertyId]);
            $msg = 'Đã chuyển tin về trạng thái chờ duyệt.';
        } else {
            $upStmt = $db->prepare("UPDATE properties SET status = ? WHERE id = ?");
            $upStmt->execute([$status, $propertyId]);
            $msg = "Đã cập nhật trạng thái tin thành {$status}.";
        }

        // Tạo thông báo in-app tự động cho chủ tin
        try {
            $userId = (int)$prop['user_id'];
            $title = (string)$prop['title'];
            if ($userId > 0) {
                if ($status === 'approved') {
                    Notification::create(
                        $userId,
                        'Tin đăng đã được duyệt',
                        "Tin đăng '{$title}' của bạn đã được Quản trị viên phê duyệt.",
                        'property/detail/' . $propertyId,
                        'success'
                    );
                } elseif ($status === 'hidden') {
                    Notification::create(
                        $userId,
                        'Tin đăng bị tạm ẩn',
                        "Tin đăng '{$title}' của bạn đã được Quản trị viên tạm ẩn.",
                        'property/detail/' . $propertyId,
                        'warning'
                    );
                } elseif ($status === 'rejected') {
                    Notification::create(
                        $userId,
                        'Tin đăng bị từ chối',
                        "Tin đăng '{$title}' của bạn đã bị Quản trị viên từ chối duyệt.",
                        'agent',
                        'danger'
                    );
                }
            }
        } catch (\Throwable $e) {
            error_log('Notification error on updateStatus: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'status' => $status,
            'message' => $msg,
        ];
    }
}
