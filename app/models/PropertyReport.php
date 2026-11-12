<?php
// Model quản lý báo cáo vi phạm bất động sản (PropertyReport)
class PropertyReport extends Report
{
    protected string $table = 'reports';
    protected array $fillable = ['property_id', 'reporter_id', 'reason', 'description', 'status', 'admin_notes'];

    /**
     * Tạo báo cáo vi phạm mới
     */
    public function createReport(int $propertyId, ?int $userId, string $reason, ?string $description = null): int
    {
        return $this->submit($propertyId, (int)$userId, $reason, $description);
    }

    /**
     * Lấy danh sách báo cáo vi phạm phân trang cho Quản trị viên
     */
    public function getReports(?string $status = null, int $page = 1, int $perPage = 10, string $search = ''): array
    {
        return $this->paginate($status, $page, $perPage, $search);
    }
}
