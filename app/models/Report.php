<?php
// [D] Báo cáo tin vi phạm (reports)
class Report extends Model
{
    protected string $table = 'reports';
    protected array $fillable = ['property_id', 'reporter_id', 'reason', 'status'];

    public function hasOpenReport(int $propertyId, int $reporterId): bool
    {
        return $this->call('sp_report_exists', [$propertyId, $reporterId]) !== [];
    }

    /** Khách báo cáo tin. Trả id mới, 0 nếu tin chưa được duyệt (không phải approved) hoặc đã có báo cáo đang mở của người này. */
    public function submit(int $propertyId, int $reporterId, string $reason): int
    {
        return (int)($this->call('sp_report_create', [$propertyId, $reporterId, mb_substr(trim($reason), 0, 255)])[0]['id'] ?? 0);
    }

    /** Danh sách cho admin, có phân trang. $status = null để lấy tất cả. */
    public function paginate(?string $status = 'open', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $status = in_array($status, ['open', 'resolved', 'dismissed'], true) ? $status : null;
        $total = (int)($this->call('sp_report_count', [$status])[0]['total'] ?? 0);
        $items = $this->call('sp_report_list', [$status, $perPage, $offset]);

        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    public function countOpen(): int
    {
        return (int)($this->call('sp_report_count', ['open'])[0]['total'] ?? 0);
    }

    /** Admin xử lý: resolved hoặc dismissed. Nếu $hideProperty thì ẩn luôn tin vi phạm. */
    public function resolve(int $id, string $newStatus, bool $hideProperty = false): bool
    {
        if (!in_array($newStatus, ['resolved', 'dismissed'], true)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $rows = $this->call('sp_report_resolve', [$id, $newStatus, $hideProperty ? 1 : 0]);
            if ((int)($rows[0]['affected'] ?? 0) === 0) {
                $this->db->rollBack();
                return false;
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
