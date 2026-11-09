<?php
// URL: /admin
class AdminDashboardController extends Controller
{
    public function __construct() { $this->requireRole('admin'); }

    public function index(): void
    {
        $summary = new AdminStats();
        $this->view('admin/dashboard', [
            'title'      => 'Quản trị',
            'stats'      => $summary->counts(),
            'byStatus'   => $summary->propertiesByStatus(),
            'byDistrict' => $summary->propertiesByDistrict(),
        ]);
    }
}
