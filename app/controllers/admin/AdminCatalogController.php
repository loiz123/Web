<?php
// URL: /admin/catalog   [A]
class AdminCatalogController extends Controller
{
    public function __construct() { $this->requireRole('admin'); }

    public function index(): void
    {
        // TODO [A]: CRUD loại BĐS, tỉnh/quận/phường, tiện ích.
        $this->view('stub', ['title' => 'Quản lý danh mục', 'owner' => 'A', 'todo' => 'CRUD loại BĐS, tỉnh/quận/phường, tiện ích.']);
    }
}
