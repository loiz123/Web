<?php
// Báo cáo tin vi phạm (reports)
class Report extends Model
{
    protected string $table = 'reports';
    protected array $fillable = ['property_id', 'reporter_id', 'reason', 'description', 'status', 'admin_notes'];

    public function hasOpenReport(int $propertyId, int $reporterId): bool
    {
        return $this->call('sp_report_exists', [$propertyId, $reporterId]) !== [];
    }

    /**
     * Khách báo cáo tin. Trả id mới, 0 nếu tin chưa được duyệt (không phải approved) hoặc đã có báo cáo đang mở của người này.
     */
    public function submit(int $propertyId, int $reporterId, string $reason, ?string $description = null): int
    {
        $rows = $this->call('sp_report_submit_full', [$propertyId, $reporterId, mb_substr(trim($reason), 0, 255), $description ? trim($description) : null]);
        return (int)($rows[0]['id'] ?? 0);
    }

    /**
     * Lấy 1 báo cáo kèm đầy đủ thông tin tin đăng và người báo cáo
     */
    public function getDetail(int $id): ?array
    {
        $row = $this->routine('sp_rt_report_get_detail_1', [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Danh sách cho admin, có phân trang. $status = null hoặc 'all' để lấy tất cả.
     */
    public function paginate(?string $status = null, int $page = 1, int $perPage = 10, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $status = in_array($status, ['open','pending','resolved','dismissed'], true) ? $status : null;
        $args = [$status, trim($search)];
        $total = (int)($this->call('sp_report_total_full', $args)[0]['total'] ?? 0);
        $items = $this->call('sp_report_page_full', [...$args, $perPage, ($page - 1) * $perPage]);
        return ['items'=>$items,'total'=>$total,'page'=>$page,'perPage'=>$perPage,'pages'=>max(1,(int)ceil($total/$perPage))];
    }

    public function countOpen(): int
    {
        return (int)($this->call('sp_report_total_full', ['open', ''])[0]['total'] ?? 0);
    }

    /**
     * Admin xử lý: resolved hoặc dismissed. Nếu $hideProperty thì ẩn luôn tin vi phạm.
     */
    public function resolve(int $id, string $newStatus, bool $hideProperty = false, ?string $adminNotes = null): bool
    {
        if (!in_array($newStatus, ['resolved','dismissed'], true)) return false;
        $this->db->beginTransaction();
        try {
            $rows = $this->call('sp_report_resolve_full', [$id, $newStatus, $hideProperty ? 1 : 0, $adminNotes]);
            $this->db->commit();
            return (int)($rows[0]['affected'] ?? 0) > 0;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
