<?php
// Controller quản trị báo cáo vi phạm: /admin/reports
class AdminReportsController extends AdminController
{
    public function index(): void
    {
        $this->reports();
    }

    public function resolve(int|string $id = 0): void
    {
        $this->resolveReport($id);
    }
}
