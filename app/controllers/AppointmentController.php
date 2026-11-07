<?php
// URL: /appointment   [D - Quốc: Lịch hẹn xem nhà]
//   POST appointment/book          khách đặt lịch (form trang chi tiết, hoặc AJAX)
//   GET  appointment/my            khách xem lịch của mình
//   POST appointment/cancel/{id}   khách hủy lịch của mình
//   POST appointment/status/{id}   môi giới xác nhận | hủy | đã xem (status = confirmed|cancelled|done)
class AppointmentController extends Controller
{
    public function book(): void
    {
        if (!Auth::check()) {
            if ($this->wantsJson()) {
                $this->json(['need_login' => true], false, 'Vui lòng đăng nhập để đặt lịch.', 401);
            }
            flash('warning', 'Vui lòng đăng nhập để đặt lịch xem nhà.');
            $this->redirect('auth/login');
        }
        if (!$this->isPost()) {
            $this->redirect('');
        }
        $this->verifyCsrf();

        $propertyId = (int)($_POST['property_id'] ?? 0);
        $raw = trim((string)($_POST['appointment_date'] ?? ($_POST['scheduled_at'] ?? '')));
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255);

        if ($propertyId <= 0) {
            $this->respond('Bất động sản không hợp lệ.', false, 400, $propertyId);
            return;
        }
        $ts = $raw !== '' ? strtotime($raw) : false;
        if ($ts === false) {
            $this->respond('Vui lòng chọn thời gian xem nhà hợp lệ.', false, 400, $propertyId);
            return;
        }
        if ($ts < time()) {
            $this->respond('Thời gian hẹn phải ở tương lai.', false, 400, $propertyId);
            return;
        }

        $property = (new Property())->find($propertyId);
        if (!$property || $property['status'] !== 'approved') {
            $this->respond('Tin đăng không tồn tại hoặc chưa được duyệt.', false, 404, $propertyId);
            return;
        }
        if ((int)$property['user_id'] === (int)Auth::id()) {
            $this->respond('Bạn không thể đặt lịch xem tin đăng của chính mình.', false, 403, $propertyId);
            return;
        }

        $model = new Appointment();
        if ($model->hasActiveBooking($propertyId, (int)Auth::id())) {
            $this->respond('Bạn đã có lịch hẹn đang chờ cho tin này.', false, 409, $propertyId);
            return;
        }

        $id = $model->book($propertyId, (int)Auth::id(), date('Y-m-d H:i:s', $ts), $note);
        if ($id > 0) {
            $this->respond('Đặt lịch thành công. Môi giới sẽ xác nhận với bạn.', true, 200, $propertyId, ['id' => $id]);
        } else {
            $this->respond('Không thể đặt lịch. Vui lòng thử lại sau.', false, 500, $propertyId);
        }
    }

    public function my(): void
    {
        $this->requireLogin();
        $items = (new Appointment())->forCustomer((int)Auth::id());
        if ($this->wantsJson()) {
            $this->json($items);
        }
        $this->view('appointment/my', ['title' => 'Lịch hẹn của tôi', 'items' => $items]);
    }

    public function cancel(int|string $id = 0): void
    {
        $this->requireLogin();
        if (!$this->isPost()) {
            $this->redirect('appointment/my');
        }
        $this->verifyCsrf();

        $ok = (new Appointment())->cancelByCustomer((int)$id, (int)Auth::id());
        $this->respond($ok ? 'Đã hủy lịch hẹn.' : 'Không thể hủy lịch hẹn này.', $ok, $ok ? 200 : 400, 0, [], 'appointment/my');
    }

    public function status(int|string $id = 0): void
    {
        $this->requireRole('agent');
        if (!$this->isPost()) {
            $this->redirect('agent');
        }
        $this->verifyCsrf();

        $status = (string)($_POST['status'] ?? '');
        $ok = (new Appointment())->changeStatusByAgent((int)$id, (int)Auth::id(), $status);
        $this->respond(
            $ok ? 'Đã cập nhật trạng thái lịch hẹn.' : 'Không cập nhật được (lịch không thuộc tin của bạn hoặc đã kết thúc).',
            $ok,
            $ok ? 200 : 400,
            0,
            [],
            'agent'
        );
    }

    /** JSON nếu client yêu cầu JSON, ngược lại flash rồi chuyển hướng. */
    private function respond(string $message, bool $success, int $status, int $propertyId, array $data = [], ?string $redirect = null): void
    {
        if ($this->wantsJson()) {
            $this->json($data ?: null, $success, $message, $status);
        }
        flash($success ? 'success' : 'danger', $message);
        $this->redirect($redirect ?? ($propertyId > 0 ? 'property/detail/' . $propertyId : ''));
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json')
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
}
