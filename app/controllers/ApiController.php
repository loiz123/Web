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
        $id = (int)($provinceId ?: ($_GET['province_id'] ?? $_GET['id'] ?? 0));
        $this->json((new Location())->districtsByProvince($id));
    }

    public function wards($parentId = 0): void
    {
        $id = (int)($parentId ?: ($_GET['province_id'] ?? $_GET['district_id'] ?? $_GET['id'] ?? 0));
        $loc = new Location();
        // Mô hình 2 cấp: ưu tiên lấy danh sách Phường/Xã trực tiếp theo Tỉnh/Thành phố (duy nhất 1 tên/phường)
        $wards = $loc->wardsByProvince($id);
        if (empty($wards) && $id > 0) {
            // Tương thích ngược nếu caller truyền district_id
            $wards = $loc->wardsByDistrict($id);
        }
        $this->json($this->deduplicateWards($wards));
    }

    public function wardsByProvince($provinceId = 0): void
    {
        $id = (int)($provinceId ?: ($_GET['province_id'] ?? $_GET['id'] ?? 0));
        $wards = (new Location())->wardsByProvince($id);
        $this->json($this->deduplicateWards($wards));
    }

    private function deduplicateWards(array $wards): array
    {
        $uniqueWards = [];
        $seenNames = [];
        foreach ($wards as $ward) {
            $name = trim((string)($ward['name'] ?? ''));
            // Loại trừ 100% các phường có dạng số chuẩn OSM (Phường 1, Phường 2...)
            if ($name === '' || preg_match('/^(Phường|Xã|Thị trấn)\s+[0-9]+/ui', $name)) {
                continue;
            }
            if (!isset($seenNames[$name])) {
                $seenNames[$name] = true;
                $ward['id'] = (int)$ward['id'];
                $ward['name'] = $name;
                $uniqueWards[] = $ward;
            }
        }
        return $uniqueWards;
    }

    public function locations($action = 'search', $subParam = 0): void
    {
        $location = new Location();
        $action = strtolower(trim((string)$action));

        if ($action === 'wards') {
            $id = (int)($subParam ?: ($_GET['province_id'] ?? $_GET['district_id'] ?? $_GET['id'] ?? 0));
            $this->wards($id);
            return;
        }

        if ($action === 'provinces') {
            $this->provinces();
            return;
        }

        if ($action === 'districts') {
            $id = (int)($subParam ?: ($_GET['province_id'] ?? $_GET['id'] ?? 0));
            $this->districts($id);
            return;
        }

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
