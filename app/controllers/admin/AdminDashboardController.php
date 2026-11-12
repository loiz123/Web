<?php
// URL: /admin hoặc /admin/dashboard
class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->requireRole('admin');
    }

    public function index(): void
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
}
