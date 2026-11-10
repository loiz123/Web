<?php
// URL: /admin/properties            -> danh sách tin chờ duyệt / báo cáo (trang)
//      /admin/properties/moderate/5 -> POST duyệt | từ chối | xóa (AJAX JSON)
//      /admin/properties/report/3   -> POST xử lý báo cáo (AJAX JSON)
class AdminPropertiesController extends Controller
{
    private const PER_PAGE = 10;

    private PropertyModeration $moderation;
    private Report $reports;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->moderation = new PropertyModeration();
        $this->reports = new Report();
    }

    public function index(): void
    {
        $allowedTabs = ['pending', 'approved', 'reports', 'hidden', 'rejected'];
        $rawTab = $_GET['tab'] ?? 'pending';
        $tab = in_array($rawTab, $allowedTabs, true) ? $rawTab : 'pending';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = $tab === 'reports'
            ? $this->reports->paginate('open', $page, self::PER_PAGE)
            : $this->moderation->paginate($tab, $page, self::PER_PAGE);

        $this->view('admin/properties', [
            'title'   => 'Duyệt tin đăng',
            'tab'     => $tab,
            'result'  => $result,
            'perPage' => self::PER_PAGE,
        ]);
    }

    public function moderate($id = 0): void
    {
        if (!$this->isPost()) {
            $this->json(null, false, 'Phương thức không hợp lệ.', 405);
            return;
        }
        $this->verifyCsrf();

        $res = $this->moderation->moderate(
            (int)$id,
            (string)($_POST['action'] ?? ''),
            (string)($_POST['reason'] ?? '')
        );
        $this->json(null, $res['ok'], $res['message'], $res['ok'] ? 200 : 422);
    }

    public function report($id = 0): void
    {
        if (!$this->isPost()) {
            $this->json(null, false, 'Phương thức không hợp lệ.', 405);
            return;
        }
        $this->verifyCsrf();

        $dismiss = ($_POST['action'] ?? '') === 'dismiss';
        $status = $dismiss ? 'dismissed' : 'resolved';
        $hide = !$dismiss && !empty($_POST['hide']);

        $ok = $this->reports->resolve((int)$id, $status, $hide);
        $msg = $ok
            ? ($dismiss ? 'Đã bỏ qua báo cáo.' : ($hide ? 'Đã xử lý báo cáo và ẩn tin.' : 'Đã xử lý báo cáo.'))
            : 'Báo cáo không tồn tại hoặc đã được xử lý.';
        $this->json(null, $ok, $msg, $ok ? 200 : 422);
    }

    /**
     * Bật/Tắt huy hiệu Đã xác thực (is_verified = 0 <-> 1)
     * URL: /admin/properties/verify/{id}
     */
    public function verify($id = 0): void
    {
        if (!Auth::isAdmin() && (int)($_SESSION['user']['role_id'] ?? 0) !== 1) {
            if ($this->wantsJson()) {
                $this->json(null, false, 'Bạn không có quyền thực hiện hành động này.', 403);
            }
            flash('danger', 'Bạn không có quyền thực hiện hành động này.');
            $this->redirect('auth/login');
            return;
        }

        $propertyId = (int)$id;
        if ($propertyId <= 0) {
            if ($this->wantsJson()) {
                $this->json(null, false, 'ID tin đăng không hợp lệ.', 400);
            }
            flash('danger', 'ID tin đăng không hợp lệ.');
            $this->redirect('admin/properties');
            return;
        }

        $res = PropertyModeration::toggleVerified($propertyId);

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => (bool)($res['success'] ?? false),
                'is_verified' => (int)($res['is_verified'] ?? 0),
                'message' => (string)($res['message'] ?? ''),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        flash(!empty($res['success']) ? 'success' : 'danger', $res['message'] ?? '');
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer && (str_contains($referer, base_url()) || str_contains($referer, 'http'))) {
            header('Location: ' . $referer);
            exit;
        }
        $this->redirect('property/detail/' . $propertyId);
    }

    /**
     * Thay đổi trạng thái tin đăng (approved, pending, hidden, rejected, deleted)
     * URL: /admin/properties/changeStatus/{id}
     */
    public function changeStatus($id = 0): void
    {
        if (!Auth::isAdmin() && (int)($_SESSION['user']['role_id'] ?? 0) !== 1) {
            if ($this->wantsJson()) {
                $this->json(null, false, 'Bạn không có quyền thực hiện hành động này.', 403);
            }
            flash('danger', 'Bạn không có quyền thực hiện hành động này.');
            $this->redirect('auth/login');
            return;
        }

        $propertyId = (int)$id;
        $status = (string)($_POST['status'] ?? ($_GET['status'] ?? ''));

        if ($propertyId <= 0 || $status === '') {
            if ($this->wantsJson()) {
                $this->json(null, false, 'Tham số không hợp lệ.', 400);
            }
            flash('danger', 'Tham số không hợp lệ.');
            $this->redirect('admin/properties');
            return;
        }

        $res = PropertyModeration::updateStatus($propertyId, $status);

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => (bool)($res['success'] ?? false),
                'status' => (string)($res['status'] ?? $status),
                'message' => (string)($res['message'] ?? ''),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        flash(!empty($res['success']) ? 'success' : 'danger', $res['message'] ?? '');
        if ($status === 'deleted') {
            $this->redirect('admin/properties');
            return;
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer && (str_contains($referer, base_url()) || str_contains($referer, 'http'))) {
            header('Location: ' . $referer);
            exit;
        }
        $this->redirect('property/detail/' . $propertyId);
    }

    public function change_status($id = 0): void
    {
        $this->changeStatus($id);
    }
}
