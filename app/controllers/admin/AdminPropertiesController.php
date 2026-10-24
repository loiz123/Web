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
        $tab = ($_GET['tab'] ?? '') === 'reports' ? 'reports' : 'pending';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = $tab === 'reports'
            ? $this->reports->paginate('open', $page, self::PER_PAGE)
            : $this->moderation->paginate('pending', $page, self::PER_PAGE);

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
}
