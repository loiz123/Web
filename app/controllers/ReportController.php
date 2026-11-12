<?php
// URL: /report   [D - Quốc: Báo cáo tin vi phạm]
//   POST report/submit   khách báo cáo tin (property_id, reason)
// Quy tắc: chỉ báo cáo tin đã duyệt (approved), không báo cáo tin của mình, mỗi người một báo cáo đang mở cho mỗi tin.
class ReportController extends Controller
{
    public function submit(): void
    {
        if (!Auth::check()) {
            if ($this->wantsJson()) {
                $this->json(['need_login' => true], false, 'Vui lòng đăng nhập để báo cáo tin.', 401);
            }
            flash('warning', 'Vui lòng đăng nhập để báo cáo tin đăng.');
            $this->redirect('auth/login');
        }
        if (!$this->isPost()) {
            $this->redirect('');
        }
        $this->verifyCsrf();

        $propertyId = (int)($_POST['property_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($propertyId <= 0) {
            $this->respond('Bất động sản không hợp lệ.', false, 400, $propertyId);
            return;
        }
        if (strlen($reason) < 5) {
            $this->respond('Lý do báo cáo cần tối thiểu 5 ký tự.', false, 400, $propertyId);
            return;
        }

        $property = (new Property())->find($propertyId);
        if (!$property || $property['status'] !== 'approved') {
            $this->respond('Chỉ báo cáo được tin đang hiển thị công khai.', false, 404, $propertyId);
            return;
        }
        if ((int)$property['user_id'] === (int)Auth::id()) {
            $this->respond('Bạn không thể báo cáo tin đăng của chính mình.', false, 403, $propertyId);
            return;
        }

        $model = new Report();
        if ($model->hasOpenReport($propertyId, (int)Auth::id())) {
            $this->respond('Bạn đã báo cáo tin này và báo cáo đang chờ xử lý.', false, 409, $propertyId);
            return;
        }

        $description = trim((string)($_POST['description'] ?? ''));

        $id = $model->submit($propertyId, (int)Auth::id(), $reason, $description);
        if ($id > 0) {
            // Tự động bắn thông báo cho TẤT CẢ Admin (WHERE role = 'admin')
            try {
                $db = Database::connection();
                $admins = $db->query("SELECT u.id, u.email FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin'")->fetchAll();
                foreach ($admins as $admin) {
                    Notification::create(
                        (int)$admin['id'],
                        'Báo cáo vi phạm mới',
                        'Có báo cáo vi phạm mới cho tin ' . ($property['title'] ?? ('#' . $propertyId)) . ': ' . $reason,
                        'admin/reports',
                        'warning'
                    );
                }
            } catch (Throwable $e) {
                error_log('Notification error on report submit: ' . $e->getMessage());
            }

            $this->respond('Đã gửi báo cáo. Ban quản trị sẽ xem xét sớm.', true, 200, $propertyId, ['id' => $id]);
        } else {
            $this->respond('Không thể gửi báo cáo. Vui lòng thử lại sau.', false, 500, $propertyId);
        }
    }

    private function respond(string $message, bool $success, int $status, int $propertyId, array $data = []): void
    {
        if ($this->wantsJson()) {
            $this->json($data ?: null, $success, $message, $status);
        }
        flash($success ? 'success' : 'danger', $message);
        $this->redirect($propertyId > 0 ? 'property/detail/' . $propertyId : '');
    }

}
