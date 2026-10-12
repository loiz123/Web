<?php
// URL: /admin
class AdminDashboardController extends Controller
{
    public function __construct() { $this->requireRole('admin'); }

    public function index(): void
    {
        $db = Database::connection();
        $stats = [
            'Người dùng'      => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'Tin đăng'        => (int)$db->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
            'Tin chờ duyệt'   => (int)$db->query("SELECT COUNT(*) FROM properties WHERE status='pending'")->fetchColumn(),
            'Báo cáo mới'     => (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='open'")->fetchColumn(),
        ];
        $this->view('admin/dashboard', ['title' => 'Quản trị', 'stats' => $stats]);
    }
}
