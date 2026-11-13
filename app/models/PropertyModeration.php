<?php
// [D] Duyệt tin của admin qua stored procedure.
class PropertyModeration extends Model
{
    protected string $table = 'properties';

    public function paginate(string $status = 'pending', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $status = in_array($status, ['all','pending','approved','rejected','hidden','closed'], true) ? $status : 'pending';
        return ['items' => $this->call('sp_moderation_page', [$status, $perPage, ($page - 1) * $perPage]),
            'total' => (int)($this->call('sp_moderation_total', [$status])[0]['total'] ?? 0), 'page' => $page];
    }

    /** Số tin theo trạng thái (cho dashboard & các tab quản lý tin). */
    public function countByStatus(): array
    {
        $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'hidden' => 0, 'closed' => 0];
        $rows = $this->call('sp_dashboard_by_status');
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
        if (!in_array($action, ['approve','reject','delete'], true)) return ['ok'=>false,'message'=>'Hành động không hợp lệ.'];
        $reason = mb_substr(trim($reason), 0, 255);
        if ($action === 'reject' && $reason === '') return ['ok'=>false,'message'=>'Vui lòng nhập lý do từ chối.'];
        $prop = (new Property())->find($id);
        $res = $this->call('sp_moderation_apply', [$id, $action, $reason])[0] ?? [];
        $ok = (int)($res['affected'] ?? 0) > 0;
        if ($ok && $prop && in_array($action, ['approve','reject'], true)) {
            $this->notifyAgentModeration((int)$prop['user_id'], $id, (string)$prop['title'], $action, $reason);
        }
        return ['ok'=>$ok,'message'=>$ok ? $this->successMessage($action) : 'Tin không tồn tại hoặc không thể đổi trạng thái hiện tại.'];
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
        $stmt = Database::routine('sp_rt_property_moderation_toggle_verified_1', [$propertyId]);
        $prop = $stmt->fetch();

        if (!$prop) {
            return ['success' => false, 'message' => 'Tin đăng không tồn tại hoặc đã bị xóa.'];
        }

        $currentVal = (int)($prop['is_verified'] ?? 0);
        $newVal = $currentVal === 1 ? 0 : 1;

        $upStmt = Database::routine('sp_rt_property_moderation_toggle_verified_2', [$newVal, $propertyId]);

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
        $stmt = Database::routine('sp_rt_property_moderation_update_status_1', [$propertyId]);
        $prop = $stmt->fetch();

        if (!$prop) {
            return ['success' => false, 'message' => 'Tin đăng không tồn tại hoặc đã bị xóa.'];
        }

        if ($status === 'deleted') {
            $delStmt = Database::routine('sp_rt_property_moderation_update_status_2', [$propertyId]);

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
            $upStmt = Database::routine('sp_rt_property_moderation_update_status_3', [$propertyId]);
            $msg = 'Đã duyệt và công khai tin đăng thành công.';
        } elseif ($status === 'rejected') {
            $upStmt = Database::routine('sp_rt_property_moderation_update_status_4', [$propertyId]);
            $msg = 'Đã từ chối tin đăng.';
        } elseif ($status === 'hidden') {
            $upStmt = Database::routine('sp_rt_property_moderation_update_status_5', [$propertyId]);
            $msg = 'Đã tạm ẩn tin đăng.';
        } elseif ($status === 'pending') {
            $upStmt = Database::routine('sp_rt_property_moderation_update_status_6', [$propertyId]);
            $msg = 'Đã chuyển tin về trạng thái chờ duyệt.';
        } else {
            $upStmt = Database::routine('sp_rt_property_moderation_update_status_7', [$status, $propertyId]);
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
