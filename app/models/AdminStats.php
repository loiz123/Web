<?php
// [D] Quốc: thống kê cho dashboard admin. Dùng routine, tự chuyển sang SQL thường khi use_routines = false
// hoặc khi routine chưa được import trên máy này.
class AdminStats extends Model
{
    protected string $table = 'properties';

    private const STATUSES = ['pending', 'approved', 'rejected', 'hidden', 'closed'];

    /** @return array<string,int> đủ 5 trạng thái, trạng thái không có tin thì bằng 0 */
    public function propertiesByStatus(): array
    {
        $rows = $this->fetchStats(
            'sp_stats_properties_by_status',
            'SELECT status, COUNT(*) AS total FROM properties GROUP BY status'
        );
        $result = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $r) {
            if (isset($result[$r['status']])) $result[$r['status']] = (int)$r['total'];
        }
        return $result;
    }

    /** @return array<int,array{district:string,province:string,total:int,approved:int}> */
    public function propertiesByDistrict(): array
    {
        $rows = $this->fetchStats(
            'sp_stats_properties_by_district',
            "SELECT d.id, d.name AS district_name, pv.name AS province_name,
                    COUNT(pr.id) AS total, SUM(pr.status = 'approved') AS approved
             FROM districts d
             JOIN provinces pv ON pv.id = d.province_id
             JOIN wards w ON w.district_id = d.id
             JOIN properties pr ON pr.ward_id = w.id
             GROUP BY d.id, d.name, pv.name
             ORDER BY total DESC, d.name"
        );
        return array_map(fn(array $r): array => [
            'district' => $r['district_name'],
            'province' => $r['province_name'],
            'total'    => (int)$r['total'],
            'approved' => (int)$r['approved'],
        ], $rows);
    }

    private function fetchStats(string $procedure, string $sql): array
    {
        if (config('use_routines')) {
            try {
                return $this->call($procedure);
            } catch (PDOException $e) {
                // Routine chưa được import: dùng SQL thường để trang vẫn chạy.
            }
        }
        return $this->query($sql)->fetchAll();
    }
}
