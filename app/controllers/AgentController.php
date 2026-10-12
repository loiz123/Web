<?php
// [B] Khu vực môi giới: quản lý tin đăng
class AgentController extends Controller
{
    public function __construct() { $this->requireRole('agent', 'admin'); }

    public function index(): void
    {
        // TODO [B]: dashboard "tin của tôi" (danh sách + trạng thái + sửa/ẩn/xóa)
        $this->view('stub', ['title' => 'Tin của tôi', 'owner' => 'B', 'todo' => 'Dashboard môi giới: danh sách tin, đổi trạng thái (AJAX), sửa/xóa.']);
    }

    public function create(): void
    {
        // TODO [B]: form đăng tin Bán/Cho thuê (ẩn/hiện trường), upload nhiều ảnh + ảnh bìa
        $this->view('stub', ['title' => 'Đăng tin mới', 'owner' => 'B', 'todo' => 'Form đăng tin; lưu status = pending để admin duyệt.']);
    }
}
