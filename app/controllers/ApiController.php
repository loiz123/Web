<?php
// [A] Endpoint JSON cho AJAX. Dạng trả về: {success, message, data}
//   GET api/provinces          -> [{id,name}]
//   GET api/districts/{tinhId} -> [{id,name}]
//   GET api/wards/{quanId}     -> [{id,name}]
class ApiController extends Controller
{
    public function provinces(): void
    {
        $this->json((new Location())->provinces());
    }

    public function districts($provinceId = 0): void
    {
        $this->json((new Location())->districtsByProvince((int)$provinceId));
    }

    public function wards($districtId = 0): void
    {
        $this->json((new Location())->wardsByDistrict((int)$districtId));
    }

    public function locations($action = 'search'): void
    {
        $location = new Location();
        $action = strtolower(trim((string)$action));

        if ($action === 'resolve') {
            $provinceId = (int)($_GET['province_id'] ?? 0);
            $province = trim((string)($_GET['province'] ?? ''));
            $district = trim((string)($_GET['district'] ?? ''));
            $ward = trim((string)($_GET['ward'] ?? ''));
            $displayName = trim((string)($_GET['display_name'] ?? $_GET['q'] ?? ''));

            $resolved = $location->resolveFromAddress($ward, $province, $provinceId, $district, $displayName);
            $this->json(
                $resolved,
                $resolved !== null,
                $resolved !== null ? 'Đã đối soát đơn vị hành chính.' : 'Không xác định được đơn vị hành chính.'
            );
        }

        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '' || mb_strlen($q, 'UTF-8') < 2) {
            $this->json([], true, 'Vui lòng nhập ít nhất 2 ký tự.');
        }

        $limit = max(1, min(15, (int)($_GET['limit'] ?? 8)));
        $results = $location->searchAdministrative($q, $limit);
        $this->json($results, true, 'Tìm thấy ' . count($results) . ' đơn vị hành chính.');
    }
}
