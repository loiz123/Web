<?php
// [D] Thống kê cho dashboard admin.
class AdminStats extends Model
{
    protected string $table = 'properties';

    private const STATUSES = ['pending', 'approved', 'rejected', 'hidden', 'closed'];

    public function counts(): array
    {
        try {
            $r = $this->call('sp_dashboard_counts')[0] ?? [];
        } catch (\Throwable $e) {
            $r = [
                'users_total' => (int)$this->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'properties_total' => (int)$this->query("SELECT COUNT(*) FROM properties WHERE status = 'approved'")->fetchColumn(),
                'pending_total' => (int)$this->query("SELECT COUNT(*) FROM properties WHERE status = 'pending'")->fetchColumn(),
                'reports_total' => (int)$this->query("SELECT COUNT(*) FROM reports WHERE status IN ('open', 'pending')")->fetchColumn(),
            ];
        }
        $approvedCount = (int)$this->query("SELECT COUNT(*) FROM properties WHERE status = 'approved'")->fetchColumn();
        return [
            'Người dùng' => (int)($r['users_total'] ?? 0),
            'Tin đã duyệt' => $approvedCount,
            'Tin chờ duyệt' => (int)($r['pending_total'] ?? 0),
            'Báo cáo mới' => (int)($r['reports_total'] ?? 0),
        ];
    }

    /** @return array<string,int> đủ 5 trạng thái, trạng thái không có tin thì bằng 0 */
    public function propertiesByStatus(): array
    {
        try {
            $rows = $this->call('sp_dashboard_by_status');
        } catch (\Throwable $e) {
            $rows = $this->query('SELECT status, COUNT(*) AS total FROM properties GROUP BY status')->fetchAll();
        }
        $result = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $r) {
            if (isset($result[$r['status']])) $result[$r['status']] = (int)$r['total'];
        }
        return $result;
    }

    /**
     * Thống kê tin đăng theo Tỉnh/Thành phố (GROUP BY provinces.id, provinces.name)
     * @return array<int,array{province_id:int,province:string,district:string,total:int,approved:int,pending:int,hidden:int}>
     */
    public function propertiesByProvince(): array
    {
        $rows = $this->query(
            "SELECT pv.id AS province_id,
                    pv.name AS province_name,
                    COUNT(pr.id) AS total,
                    COUNT(pr.id) AS approved,
                    0 AS pending,
                    0 AS hidden
             FROM properties pr
             JOIN wards w ON w.id = pr.ward_id
             JOIN provinces pv ON pv.id = w.province_id
             WHERE pr.status = 'approved'
             GROUP BY pv.id, pv.name
             ORDER BY approved DESC, pv.name ASC"
        )->fetchAll();

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'province_id' => (int)$r['province_id'],
                'province'    => (string)$r['province_name'],
                'district'    => (string)$r['province_name'],
                'total'       => (int)$r['total'],
                'approved'    => (int)$r['approved'],
                'pending'     => (int)$r['pending'],
                'hidden'      => (int)$r['hidden'],
            ];
        }
        return $result;
    }

    /** @return array<int,array{province_id:int,province:string,district:string,total:int,approved:int,pending:int,hidden:int}> */
    public function propertiesByDistrict(): array
    {
        return $this->propertiesByProvince();
    }
}
