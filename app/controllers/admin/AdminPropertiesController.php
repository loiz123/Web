<?php
// URL: /admin/properties   [D]
class AdminPropertiesController extends Controller
{
    public function __construct() { $this->requireRole('admin'); }

    public function index(): void
    {
        // TODO [D]: Danh sách tin chờ duyệt: duyệt / từ chối (kèm lý do) / xóa; xử lý báo cáo.
        $this->view('stub', ['title' => 'Duyệt tin đăng', 'owner' => 'D', 'todo' => 'Danh sách tin chờ duyệt: duyệt / từ chối (kèm lý do) / xóa; xử lý báo cáo.']);
    }
}
