<?php
// [C] Tìm kiếm & xem chi tiết tin đăng - Hà Ngọc Thiện (@hntrhunter) phụ trách
class PropertyFinder extends Model
{
    protected string $table = 'properties';

    private const BASE_SELECT = "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
            p.direction, p.is_verified, p.latitude, p.longitude, p.created_at,
            t.id AS type_id, t.name AS type_name,
            w.id AS ward_id, w.name AS ward_name,
            d.id AS district_id, d.name AS district_name,
            pr.id AS province_id, pr.name AS province_name,
            (SELECT image_url FROM property_images i WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
        FROM properties p
        JOIN property_types t ON t.id = p.type_id
        JOIN wards w ON w.id = p.ward_id
        JOIN districts d ON d.id = w.district_id
        JOIN provinces pr ON pr.id = d.province_id";

    public function latest(int $limit = 8): array
    {
        if (config('use_routines')) {
            try {
                return $this->call('sp_latest_properties', [$limit]);
            } catch (PDOException $e) {
                // Fallback nếu routine lỗi
            }
        }
        $sql = self::BASE_SELECT . " WHERE p.status = 'approved' ORDER BY p.created_at DESC, p.id DESC LIMIT " . (int)$limit;
        return $this->query($sql)->fetchAll();
    }

    /**
     * Chuẩn hóa và làm sạch bộ lọc tìm kiếm
     */
    public function parseFilters(array $f): array
    {
        $parsed = [
            'keyword'      => trim($f['keyword'] ?? '') ?: null,
            'listing_type' => in_array($f['listing_type'] ?? '', ['sale', 'rent'], true) ? $f['listing_type'] : null,
            'type_id'      => !empty($f['type_id']) ? (int)$f['type_id'] : null,
            'province_id'  => !empty($f['province_id']) ? (int)$f['province_id'] : null,
            'district_id'  => !empty($f['district_id']) ? (int)$f['district_id'] : null,
            'ward_id'      => !empty($f['ward_id']) ? (int)$f['ward_id'] : null,
            'min_price'    => ($f['min_price'] ?? '') !== '' ? (float)$f['min_price'] : null,
            'max_price'    => ($f['max_price'] ?? '') !== '' ? (float)$f['max_price'] : null,
            'min_area'     => ($f['min_area'] ?? '') !== '' ? (float)$f['min_area'] : null,
            'max_area'     => ($f['max_area'] ?? '') !== '' ? (float)$f['max_area'] : null,
            'area_range'   => trim($f['area_range'] ?? '') ?: null,
            'price_range'  => trim($f['price_range'] ?? '') ?: null,
            'bedrooms'     => trim((string)($f['bedrooms'] ?? '')) ?: null,
            'sort_by'      => trim($f['sort_by'] ?? 'newest') ?: 'newest',
        ];

        // Xử lý khoảng diện tích nhanh (area_range) nếu chưa có min_area / max_area cụ thể
        if ($parsed['area_range'] && $parsed['min_area'] === null && $parsed['max_area'] === null) {
            match ($parsed['area_range']) {
                '0-30', '<30'    => [$parsed['min_area'] = null,  $parsed['max_area'] = 30.0],
                '30-50'          => [$parsed['min_area'] = 30.0,  $parsed['max_area'] = 50.0],
                '50-80'          => [$parsed['min_area'] = 50.0,  $parsed['max_area'] = 80.0],
                '80-100'         => [$parsed['min_area'] = 80.0,  $parsed['max_area'] = 100.0],
                '100-150'        => [$parsed['min_area'] = 100.0, $parsed['max_area'] = 150.0],
                '150-200'        => [$parsed['min_area'] = 150.0, $parsed['max_area'] = 200.0],
                '200-250'        => [$parsed['min_area'] = 200.0, $parsed['max_area'] = 250.0],
                '250-300'        => [$parsed['min_area'] = 250.0, $parsed['max_area'] = 300.0],
                '300-500'        => [$parsed['min_area'] = 300.0, $parsed['max_area'] = 500.0],
                '500+', '>500'   => [$parsed['min_area'] = 500.0, $parsed['max_area'] = null],
                default          => null
            };
        }

        // Xử lý khoảng giá nhanh (price_range) nếu chưa có min_price / max_price cụ thể
        if ($parsed['price_range'] && $parsed['min_price'] === null && $parsed['max_price'] === null) {
            match ($parsed['price_range']) {
                // Bán
                '0-500m', '<500m'        => [$parsed['min_price'] = null,          $parsed['max_price'] = 500000000.0],
                '500m-800m'              => [$parsed['min_price'] = 500000000.0,   $parsed['max_price'] = 800000000.0],
                '800m-1b', '800m-1ty'    => [$parsed['min_price'] = 800000000.0,   $parsed['max_price'] = 1000000000.0],
                '<1ty'                   => [$parsed['min_price'] = null,          $parsed['max_price'] = 1000000000.0],
                '1b-2b', '1-2ty'         => [$parsed['min_price'] = 1000000000.0,  $parsed['max_price'] = 2000000000.0],
                '2b-3b', '2-3ty'         => [$parsed['min_price'] = 2000000000.0,  $parsed['max_price'] = 3000000000.0],
                '3b-5b', '3-5ty'         => [$parsed['min_price'] = 3000000000.0,  $parsed['max_price'] = 5000000000.0],
                '5b-7b', '5-7ty'         => [$parsed['min_price'] = 5000000000.0,  $parsed['max_price'] = 7000000000.0],
                '7b-10b', '7-10ty'       => [$parsed['min_price'] = 7000000000.0,  $parsed['max_price'] = 10000000000.0],
                '10b-20b', '10-20ty'     => [$parsed['min_price'] = 10000000000.0, $parsed['max_price'] = 20000000000.0],
                '20b+', '>20ty'          => [$parsed['min_price'] = 20000000000.0, $parsed['max_price'] = null],
                // Thuê
                '<3tr'                   => [$parsed['min_price'] = null,         $parsed['max_price'] = 3000000.0],
                '3-5tr'                  => [$parsed['min_price'] = 3000000.0,    $parsed['max_price'] = 5000000.0],
                '5-10tr'                 => [$parsed['min_price'] = 5000000.0,    $parsed['max_price'] = 10000000.0],
                '10-20tr'                => [$parsed['min_price'] = 10000000.0,   $parsed['max_price'] = 20000000.0],
                '20-40tr'                => [$parsed['min_price'] = 20000000.0,   $parsed['max_price'] = 40000000.0],
                '>40tr'                  => [$parsed['min_price'] = 40000000.0,   $parsed['max_price'] = null],
                default                  => null
            };
        }

        return $parsed;
    }

    /**
     * Xây dựng mệnh đề WHERE, Params và ORDER BY dùng chung cho search và countSearch
     */
    private function buildQueryParts(array $f): array
    {
        $f = $this->parseFilters($f);
        $where = " WHERE p.status = 'approved'";
        $params = [];

        if (!empty($f['keyword'])) {
            $kw = '%' . $f['keyword'] . '%';
            $where .= " AND (p.title LIKE ? OR p.address_detail LIKE ? OR w.name LIKE ? OR d.name LIKE ? OR pr.name LIKE ? OR t.name LIKE ?)";
            array_push($params, $kw, $kw, $kw, $kw, $kw, $kw);
        }

        if (!empty($f['listing_type'])) {
            $where .= " AND p.listing_type = ?";
            $params[] = $f['listing_type'];
        }

        if (!empty($f['type_id'])) {
            $where .= " AND p.type_id = ?";
            $params[] = (int)$f['type_id'];
        }

        if (!empty($f['ward_id'])) {
            $where .= " AND p.ward_id = ?";
            $params[] = (int)$f['ward_id'];
        } elseif (!empty($f['district_id'])) {
            $where .= " AND d.id = ?";
            $params[] = (int)$f['district_id'];
        } elseif (!empty($f['province_id'])) {
            $where .= " AND pr.id = ?";
            $params[] = (int)$f['province_id'];
        }

        if ($f['min_price'] !== null) {
            $where .= " AND p.price >= ?";
            $params[] = $f['min_price'];
        }
        if ($f['max_price'] !== null) {
            $where .= " AND p.price <= ?";
            $params[] = $f['max_price'];
        }

        if ($f['min_area'] !== null) {
            $where .= " AND p.area >= ?";
            $params[] = $f['min_area'];
        }
        if ($f['max_area'] !== null) {
            $where .= " AND p.area <= ?";
            $params[] = $f['max_area'];
        }

        if (!empty($f['bedrooms'])) {
            if ($f['bedrooms'] === '5+' || (int)$f['bedrooms'] >= 5) {
                $where .= " AND p.bedrooms >= 5";
            } elseif ((int)$f['bedrooms'] > 0) {
                $where .= " AND p.bedrooms = ?";
                $params[] = (int)$f['bedrooms'];
            }
        }

        $order = match ($f['sort_by'] ?? 'newest') {
            'oldest'     => " ORDER BY p.created_at ASC, p.id ASC",
            'price_asc'  => " ORDER BY p.price ASC, p.id ASC",
            'price_desc' => " ORDER BY p.price DESC, p.id DESC",
            'area_asc'   => " ORDER BY p.area ASC, p.id ASC",
            'area_desc'  => " ORDER BY p.area DESC, p.id DESC",
            default      => " ORDER BY p.created_at DESC, p.id DESC",
        };

        return [$where, $params, $order];
    }

    /**
     * Tìm kiếm tin BĐS với bộ lọc linh hoạt, sắp xếp và phân trang
     */
    public function search(array $f, int $limit = 12, int $offset = 0): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        // Thử gọi stored procedure nếu bật use_routines và có cài đặt mở rộng
        if (config('use_routines')) {
            $pf = $this->parseFilters($f);
            try {
                return $this->call('sp_search_properties_v2', [
                    $pf['keyword'],
                    $pf['listing_type'],
                    $pf['type_id'],
                    $pf['province_id'],
                    $pf['district_id'],
                    $pf['ward_id'],
                    $pf['min_price'],
                    $pf['max_price'],
                    $pf['min_area'],
                    $pf['max_area'],
                    $pf['bedrooms'] === '5+' ? 5 : ($pf['bedrooms'] !== null ? (int)$pf['bedrooms'] : null),
                    $pf['sort_by'],
                    $limit,
                    $offset
                ]);
            } catch (PDOException $e) {
                // Fallback nếu procedure chưa tạo hoặc thiếu tham số
            }
        }

        [$where, $params, $order] = $this->buildQueryParts($f);
        $sql = self::BASE_SELECT . $where . $order . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Đếm tổng số kết quả khớp với bộ lọc (phục vụ phân trang)
     */
    public function countSearch(array $f): int
    {
        if (config('use_routines')) {
            $pf = $this->parseFilters($f);
            try {
                $rows = $this->call('sp_count_search_properties_v2', [
                    $pf['keyword'],
                    $pf['listing_type'],
                    $pf['type_id'],
                    $pf['province_id'],
                    $pf['district_id'],
                    $pf['ward_id'],
                    $pf['min_price'],
                    $pf['max_price'],
                    $pf['min_area'],
                    $pf['max_area'],
                    $pf['bedrooms'] === '5+' ? 5 : ($pf['bedrooms'] !== null ? (int)$pf['bedrooms'] : null)
                ]);
                if (!empty($rows[0]['total'])) {
                    return (int)$rows[0]['total'];
                }
            } catch (PDOException $e) {
                // Fallback nếu procedure chưa tạo
            }
        }

        [$where, $params] = $this->buildQueryParts($f);
        $sql = "SELECT COUNT(*) FROM properties p
                JOIN property_types t ON t.id = p.type_id
                JOIN wards w ON w.id = p.ward_id
                JOIN districts d ON d.id = w.district_id
                JOIN provinces pr ON pr.id = d.province_id" . $where;
        return (int)$this->query($sql, $params)->fetchColumn();
    }

    /**
     * Lấy chi tiết đầy đủ 1 tin BĐS (kèm loại, địa chỉ, môi giới và tọa độ fallback)
     */
    public function findDetail(int $id): ?array
    {
        $sql = "SELECT p.*,
                       t.name AS type_name,
                       w.name AS ward_name,
                       d.id AS district_id, d.name AS district_name,
                       pr.id AS province_id, pr.name AS province_name,
                       u.full_name AS agent_name,
                       u.phone AS agent_phone,
                       u.email AS agent_email,
                       u.avatar AS agent_avatar
                FROM properties p
                JOIN property_types t ON t.id = p.type_id
                JOIN wards w ON w.id = p.ward_id
                JOIN districts d ON d.id = w.district_id
                JOIN provinces pr ON pr.id = d.province_id
                JOIN users u ON u.id = p.user_id
                WHERE p.id = ? LIMIT 1";
        $prop = $this->query($sql, [$id])->fetch();
        if (!$prop) return null;

        // Nếu tin chưa có tọa độ kinh độ / vĩ độ, cung cấp fallback theo quận/tỉnh
        if (empty($prop['latitude']) || empty($prop['longitude'])) {
            $fallback = $this->getDistrictFallbackCoords((int)$prop['district_id'], (int)$prop['province_id']);
            $prop['latitude'] = $fallback['lat'];
            $prop['longitude'] = $fallback['lng'];
            $prop['is_coord_fallback'] = true;
        } else {
            $prop['is_coord_fallback'] = false;
        }

        return $prop;
    }

    /**
     * Tọa độ trung tâm mặc định theo quận/huyện
     */
    public function getDistrictFallbackCoords(int $districtId, int $provinceId = 1): array
    {
        $districtCoords = [
            1 => ['lat' => 10.776889, 'lng' => 106.700806], // Quận 1
            2 => ['lat' => 10.784260, 'lng' => 106.684340], // Quận 3
            3 => ['lat' => 10.823099, 'lng' => 106.772598], // TP. Thủ Đức
            4 => ['lat' => 21.034100, 'lng' => 105.824300], // Ba Đình, Hà Nội
            5 => ['lat' => 21.036000, 'lng' => 105.790000], // Cầu Giấy, Hà Nội
            6 => ['lat' => 16.067800, 'lng' => 108.220800], // Hải Châu, Đà Nẵng
        ];

        if (isset($districtCoords[$districtId])) {
            return $districtCoords[$districtId];
        }

        $provinceCoords = [
            1 => ['lat' => 10.776889, 'lng' => 106.700806], // TP.HCM
            2 => ['lat' => 21.028511, 'lng' => 105.804817], // Hà Nội
            3 => ['lat' => 16.054407, 'lng' => 108.202167], // Đà Nẵng
        ];

        return $provinceCoords[$provinceId] ?? ['lat' => 10.776889, 'lng' => 106.700806];
    }

    /** Danh sách hình ảnh của tin */
    public function getImages(int $propertyId): array
    {
        return $this->query(
            "SELECT id, image_url, is_cover FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, id ASC",
            [$propertyId]
        )->fetchAll();
    }

    /** Danh sách tiện ích của tin */
    public function getAmenities(int $propertyId): array
    {
        return $this->query(
            "SELECT a.id, a.name
             FROM property_amenities pa
             JOIN amenities a ON a.id = pa.amenity_id
             WHERE pa.property_id = ?
             ORDER BY a.name ASC",
            [$propertyId]
        )->fetchAll();
    }

    /** Tăng lượt xem tin */
    public function incrementViews(int $propertyId): void
    {
        $this->query("UPDATE properties SET views = views + 1 WHERE id = ?", [$propertyId]);
    }

    /** Lấy các tin cùng loại tương tự */
    public function getSimilar(int $propertyId, int $typeId, int $limit = 3): array
    {
        $sql = self::BASE_SELECT . " WHERE p.status = 'approved' AND p.id != ? AND p.type_id = ?
                ORDER BY p.created_at DESC LIMIT " . (int)$limit;
        return $this->query($sql, [$propertyId, $typeId])->fetchAll();
    }
}
