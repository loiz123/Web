<?php
// URL: /admin/users   [A]
class AdminUsersController extends Controller
{
    public function __construct() { $this->requireRole('admin'); }

    public function index(): void
    {
        // TODO [A]: Danh sách user, khóa/mở khóa tài khoản.
        $this->view('stub', ['title' => 'Quản lý người dùng', 'owner' => 'A', 'todo' => 'Danh sách user, khóa/mở khóa tài khoản.']);
    }
}
