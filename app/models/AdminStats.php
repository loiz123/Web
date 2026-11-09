<?php
// [D] Thống kê cho dashboard admin qua stored procedure.
class AdminStats extends Model
{
    protected string $table = 'properties';

    private const STATUSES = ['pending', 'approved', 'rejected', 'hidden', 'closed'];

    public function counts(): array
    {
        $r = $this->call('sp_dashboard_counts')[0] ?? [];
        return [
            'Người dùng' => (int)($r['users_total'] ?? 0),
            'Tin đăng' => (int)($r['properties_total'] ?? 0),
            'Tin chờ duyệt' => (int)($r['pending_total'] ?? 0),
            'Báo cáo mới' => (int)($r['reports_total'] ?? 0),
        ];
    }

    /** @return array<string,int> đủ 5 trạng thái, trạng thái không có tin thì bằng 0 */
    public function propertiesByStatus(): array
    {
        $rows = $this->call('sp_dashboard_by_status');
        $result = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $r) {
            if (isset($result[$r['status']])) $result[$r['status']] = (int)$r['total'];
        }
        return $result;
    }

    /** @return array<int,array{district:string,province:string,total:int,approved:int}> */
    public function propertiesByDistrict(): array
    {
        $rows = $this->call('sp_dashboard_by_district');
        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'district' => $r['district_name'],
                'province' => $r['province_name'],
                'total'    => (int)$r['total'],
                'approved' => (int)$r['approved'],
            ];
        }
        return $result;
    }

}
