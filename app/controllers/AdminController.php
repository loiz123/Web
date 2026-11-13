<?php
// Quản trị hệ thống: Báo cáo vi phạm và quản lý tin đăng
class AdminController extends Controller
{
    protected Report $reports;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->reports = new Report();
    }

    /**
     * Bảng điều khiển quản trị: GET /admin hoặc /admin/dashboard
     */
    public function index(): void
    {
        $this->dashboard();
    }

    public function dashboard(): void
    {
        $summary = new AdminStats();
        $byProvince = $summary->propertiesByProvince();
        $this->view('admin/dashboard', [
            'title'      => 'Bảng điều khiển quản trị',
            'stats'      => $summary->counts(),
            'byStatus'   => $summary->propertiesByStatus(),
            'byProvince' => $byProvince,
            'byDistrict' => $byProvince,
        ]);
    }

    /**
     * Quản lý tin đăng: GET /admin/properties
     */
    public function properties(): void
    {
        $moderation = new PropertyModeration();
        $statusCounts = $moderation->countByStatus();
        $defaultTab = ((int)($statusCounts['pending'] ?? 0) > 0) ? 'pending' : 'approved';

        $allowedTabs = ['pending', 'approved', 'hidden', 'rejected', 'closed', 'all', 'reports'];
        $rawTab = trim((string)($_GET['tab'] ?? $_GET['status'] ?? ''));
        $tab = in_array($rawTab, $allowedTabs, true) ? $rawTab : $defaultTab;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $result = $tab === 'reports'
            ? $this->reports->paginate('open', $page, $perPage)
            : $moderation->paginate($tab, $page, $perPage);

        $openReportsTotal = ($tab === 'reports')
            ? (int)($result['total'] ?? 0)
            : (int)($this->reports->paginate('open', 1, 1)['total'] ?? 0);

        $tabCounts = [
            'all'      => (int)array_sum($statusCounts),
            'pending'  => (int)($statusCounts['pending'] ?? 0),
            'approved' => (int)($statusCounts['approved'] ?? 0),
            'hidden'   => (int)($statusCounts['hidden'] ?? 0),
            'rejected' => (int)($statusCounts['rejected'] ?? 0),
            'closed'   => (int)($statusCounts['closed'] ?? 0),
            'reports'  => $openReportsTotal,
        ];

        $this->view('admin/properties', [
            'title'     => 'Quản lý tin đăng',
            'tab'       => $tab,
            'result'    => $result,
            'perPage'   => $perPage,
            'tabCounts' => $tabCounts,
        ]);
    }

    /**
     * Danh sách báo cáo vi phạm: GET /admin/reports
     */
    public function reports(): void
    {
        $status = (string)($_GET['status'] ?? 'all');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim((string)($_GET['q'] ?? ''));
        $perPage = 10;

        $filterStatus = ($status === 'all' || $status === '') ? null : $status;
        $result = $this->reports->paginate($filterStatus, $page, $perPage, $search);

        $db = Database::connection();
        $counts = [
            'total'     => (int)Database::routine('sp_rt_admin_controller_reports_1', [])->fetchColumn(),
            'pending'   => (int)Database::routine('sp_rt_admin_controller_reports_2', [])->fetchColumn(),
            'resolved'  => (int)Database::routine('sp_rt_admin_controller_reports_3', [])->fetchColumn(),
            'dismissed' => (int)Database::routine('sp_rt_admin_controller_reports_4', [])->fetchColumn(),
        ];

        $this->view('admin/reports', [
            'title'   => 'Quản lý báo cáo vi phạm',
            'reports' => $result['items'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => $result['pages'],
            'perPage' => $perPage,
            'status'  => $status,
            'search'  => $search,
            'counts'  => $counts,
        ]);
    }

    /**
     * Xử lý báo cáo vi phạm: POST /admin/resolveReport/{id}
     * - action = 'resolve': Duyệt vi phạm -> ẩn tin đăng & đổi trạng thái sang 'resolved'
     * - action = 'dismiss': Bác bỏ báo cáo -> đổi trạng thái sang 'dismissed'
     */
    public function resolveReport(int|string $id = 0): void
    {
        $id = (int)($id ?: ($_POST['id'] ?? 0));
        if ($id <= 0) {
            $this->respond('Báo cáo không hợp lệ.', false, 400);
            return;
        }

        if (!$this->isPost()) {
            $this->respond('Phương thức không hợp lệ.', false, 405);
            return;
        }
        $this->verifyCsrf();

        $action = (string)($_POST['action'] ?? 'resolve');
        $notes = trim((string)($_POST['admin_notes'] ?? ''));

        $report = $this->reports->getDetail($id);
        if (!$report) {
            $this->respond('Không tìm thấy báo cáo yêu cầu.', false, 404);
            return;
        }

        $isDismiss = ($action === 'dismiss');
        $newStatus = $isDismiss ? 'dismissed' : 'resolved';
        $hideProperty = !$isDismiss; // Duyệt vi phạm -> ẩn tin đăng

        $ok = $this->reports->resolve($id, $newStatus, $hideProperty, $notes);
        if (!$ok) {
            $this->respond('Không thể cập nhật trạng thái báo cáo. Vui lòng thử lại.', false, 500);
            return;
        }

        // Bắn thông báo theo nghiệp vụ
        try {
            if ($hideProperty && $newStatus === 'resolved') {
                // 1. Gửi thông báo cho môi giới / người đăng tin
                if (!empty($report['agent_id'])) {
                    Notification::create(
                        (int)$report['agent_id'],
                        'Tin đăng bị tạm ẩn do vi phạm',
                        'Tin đăng "' . ($report['property_title'] ?? ('#' . $report['property_id'])) . '" đã bị quản trị viên tạm ẩn do vi phạm quy định: ' . $report['reason'] . ($notes ? ' (Ghi chú: ' . $notes . ')' : '') . '. Vui lòng liên hệ hỗ trợ để được giải đáp.',
                        'agent',
                        'danger'
                    );
                }

                // 2. Gửi thông báo cho người gửi báo cáo
                if (!empty($report['reporter_id'])) {
                    Notification::create(
                        (int)$report['reporter_id'],
                        'Báo cáo vi phạm đã được xử lý',
                        'Cảm ơn bạn đã đóng góp phản ánh. Tin đăng "' . ($report['property_title'] ?? ('#' . $report['property_id'])) . '" đã được ban quản trị kiểm tra và xử lý tạm ẩn khỏi hệ thống.',
                        'property/detail/' . (int)$report['property_id'],
                        'success'
                    );
                }
            } elseif ($newStatus === 'dismissed') {
                // Báo cáo bị bác bỏ: gửi thông báo phản hồi cho người báo cáo
                if (!empty($report['reporter_id'])) {
                    Notification::create(
                        (int)$report['reporter_id'],
                        'Kết quả xử lý báo cáo vi phạm',
                        'Báo cáo vi phạm cho tin đăng "' . ($report['property_title'] ?? ('#' . $report['property_id'])) . '" đã được ban quản trị xem xét. Tin đăng tuân thủ đúng tiêu chuẩn hệ thống.',
                        'property/detail/' . (int)$report['property_id'],
                        'info'
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('Notification error on resolveReport: ' . $e->getMessage());
        }

        $msg = $isDismiss
            ? 'Đã bác bỏ báo cáo vi phạm thành công.'
            : 'Đã duyệt báo cáo vi phạm và tạm ẩn tin đăng thành công.';

        $this->respond($msg, true, 200, ['status' => $newStatus, 'id' => $id]);
    }

    public function resolve(int|string $id = 0): void
    {
        $this->resolveReport($id);
    }

    public function dismiss(int|string $id = 0): void
    {
        $_POST['action'] = 'dismiss';
        $this->resolveReport($id);
    }

    private function respond(string $message, bool $success, int $statusCode = 200, array $data = []): void
    {
        if ($this->wantsJson()) {
            $this->json($data ?: null, $success, $message, $statusCode);
            return;
        }

        flash($success ? 'success' : 'danger', $message);
        $this->redirect('admin/reports');
    }
}
