<?php
// [D] Thống kê cho dashboard admin.
class AdminStats extends Model
{
    protected string $table = 'properties';

    private const STATUSES = ['pending', 'approved', 'rejected', 'hidden', 'closed'];

    public function counts(): array
    {
        $r = $this->call('sp_dashboard_counts')[0] ?? [];
        return ['Người dùng' => (int)($r['users_total'] ?? 0),
            'Tin đã duyệt' => (int)($r['approved_total'] ?? 0),
            'Tin chờ duyệt' => (int)($r['pending_total'] ?? 0),
            'Báo cáo mới' => (int)($r['reports_total'] ?? 0)];
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

    /**
     * Thống kê tin đăng theo Tỉnh/Thành phố (GROUP BY provinces.id, provinces.name)
     * @return array<int,array{province_id:int,province:string,district:string,total:int,approved:int,pending:int,hidden:int}>
     */
    public function propertiesByProvince(): array
    {
        $rows = $this->routine('sp_rt_admin_stats_properties_by_province_1', [])->fetchAll();

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
