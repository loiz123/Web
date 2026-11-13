<?php
// [C] Tìm kiếm & xem chi tiết tin đăng - Hà Ngọc Thiện (@hntrhunter) phụ trách
class PropertyFinder extends Model
{
    protected string $table = 'properties';


    public function latest(int $limit = 8): array
    {
        return $this->call('sp_latest_properties_v3', [max(0, $limit)]);
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
            'sort_by'      => trim($f['sort_by'] ?? $f['sort'] ?? 'newest') ?: 'newest',
        ];

        // Xử lý khoảng diện tích nhanh (area_range) nếu chưa có min_area / max_area cụ thể
        if ($parsed['area_range'] && $parsed['min_area'] === null && $parsed['max_area'] === null) {
            match ($parsed['area_range']) {
                '0-30', '<30', 'under_30' => [$parsed['min_area'] = null,  $parsed['max_area'] = 30.0],
                '30-50', '30_50'          => [$parsed['min_area'] = 30.0,  $parsed['max_area'] = 50.0],
                '50-80', '50_80'          => [$parsed['min_area'] = 50.0,  $parsed['max_area'] = 80.0],
                '80-100', '80_100'        => [$parsed['min_area'] = 80.0,  $parsed['max_area'] = 100.0],
                '100-150', '100_150'      => [$parsed['min_area'] = 100.0, $parsed['max_area'] = 150.0],
                '150-200', '150_200'      => [$parsed['min_area'] = 150.0, $parsed['max_area'] = 200.0],
                '150-250', '150_250'      => [$parsed['min_area'] = 150.0, $parsed['max_area'] = 250.0],
                '200-250', '200_250'      => [$parsed['min_area'] = 200.0, $parsed['max_area'] = 250.0],
                '250-300', '250_300'      => [$parsed['min_area'] = 250.0, $parsed['max_area'] = 300.0],
                '250-500', '250_500'      => [$parsed['min_area'] = 250.0, $parsed['max_area'] = 500.0],
                '300-500', '300_500'      => [$parsed['min_area'] = 300.0, $parsed['max_area'] = 500.0],
                '500+', '>500', 'over_500'=> [$parsed['min_area'] = 500.0, $parsed['max_area'] = null],
                default                   => null
            };
        }

        // Xử lý khoảng giá nhanh (price_range) nếu chưa có min_price / max_price cụ thể
        if ($parsed['price_range'] && $parsed['min_price'] === null && $parsed['max_price'] === null) {
            match ($parsed['price_range']) {
                // Bán
                '0-500m', '<500m'                  => [$parsed['min_price'] = null,          $parsed['max_price'] = 500000000.0],
                '500m-800m'                        => [$parsed['min_price'] = 500000000.0,   $parsed['max_price'] = 800000000.0],
                '800m-1b', '800m-1ty'              => [$parsed['min_price'] = 800000000.0,   $parsed['max_price'] = 1000000000.0],
                '<1ty', 'under_1b'                 => [$parsed['min_price'] = null,          $parsed['max_price'] = 1000000000.0],
                '1b-2b', '1-2ty', '1b_2b'          => [$parsed['min_price'] = 1000000000.0,  $parsed['max_price'] = 2000000000.0],
                '2b-3b', '2-3ty', '2b_3b'          => [$parsed['min_price'] = 2000000000.0,  $parsed['max_price'] = 3000000000.0],
                '3b-5b', '3-5ty', '3b_5b'          => [$parsed['min_price'] = 3000000000.0,  $parsed['max_price'] = 5000000000.0],
                '5b-7b', '5-7ty', '5b_7b'          => [$parsed['min_price'] = 5000000000.0,  $parsed['max_price'] = 7000000000.0],
                '7b-10b', '7-10ty', '7b_10b'       => [$parsed['min_price'] = 7000000000.0,  $parsed['max_price'] = 10000000000.0],
                '10b-20b', '10-20ty', '10b_20b'    => [$parsed['min_price'] = 10000000000.0, $parsed['max_price'] = 20000000000.0],
                '20b+', '>20ty', 'over_20b'        => [$parsed['min_price'] = 20000000000.0, $parsed['max_price'] = null],
                // Thuê
                '<3tr'                             => [$parsed['min_price'] = null,         $parsed['max_price'] = 3000000.0],
                '3-5tr'                            => [$parsed['min_price'] = 3000000.0,    $parsed['max_price'] = 5000000.0],
                '0-5m', 'rent_0_5m'                => [$parsed['min_price'] = null,         $parsed['max_price'] = 5000000.0],
                '5-10tr', '5m-10m', 'rent_5m_10m'  => [$parsed['min_price'] = 5000000.0,    $parsed['max_price'] = 10000000.0],
                '10-20tr', '10m-20m', 'rent_10m_20m'=> [$parsed['min_price'] = 10000000.0,   $parsed['max_price'] = 20000000.0],
                '20-40tr'                          => [$parsed['min_price'] = 20000000.0,   $parsed['max_price'] = 40000000.0],
                '20m+', 'rent_20m_plus'            => [$parsed['min_price'] = 20000000.0,   $parsed['max_price'] = null],
                '>40tr'                            => [$parsed['min_price'] = 40000000.0,   $parsed['max_price'] = null],
                default                            => null
            };
        }

        return $parsed;
    }

    /** Chuẩn bị các tham số dùng chung cho danh sách và tổng kết quả. */
    private function searchParameters(array $filters): array
    {
        $f = $this->parseFilters($filters);
        $segment = null;
        if ($f['keyword'] !== null && str_contains($f['keyword'], ',')) {
            $first = trim(explode(',', $f['keyword'])[0]);
            $clean = trim((string)preg_replace('/^(Phường|Xã|Thị trấn|Quận|Huyện|Thành phố|TP\.?|Tỉnh)\s+/iu', '', $first));
            $segment = $clean !== '' ? $clean : $first;
        }
        $bedrooms = $f['bedrooms'] === '5+' ? 5 : ($f['bedrooms'] !== null ? (int)$f['bedrooms'] : null);
        return [$f['keyword'], $segment, $f['listing_type'], $f['type_id'], $f['province_id'], $f['district_id'], $f['ward_id'], $f['min_price'], $f['max_price'], $f['min_area'], $f['max_area'], $bedrooms];
    }



    public function search(array $f, int $limit = 12, int $offset = 0): array
    {
        $parsed = $this->parseFilters($f);
        return $this->call('sp_search_properties_v3', [...$this->searchParameters($f), $parsed['sort_by'], max(1, min(100, $limit)), max(0, $offset)]);
    }

    /**
     * Lấy danh sách tất cả BĐS đang hiển thị (approved) phục vụ hiển thị bản đồ trực quan
     */
    public function forMap(array $filters = []): array
    {
        $parsed = $this->parseFilters($filters);
        $properties = $this->call('sp_property_map', [...$this->searchParameters($filters), $parsed['sort_by']]);

        // Đảm bảo mọi tin đều có tọa độ (fallback theo quận/tỉnh nếu thiếu)
        foreach ($properties as &$p) {
            if (empty($p['latitude']) || empty($p['longitude'])) {
                $fallback = $this->getDistrictFallbackCoords((int)($p['district_id'] ?? 0), (int)($p['province_id'] ?? 1));
                $p['latitude'] = $fallback['lat'];
                $p['longitude'] = $fallback['lng'];
                $p['is_coord_fallback'] = true;
            } else {
                $p['is_coord_fallback'] = false;
            }
        }
        unset($p);

        return $properties;
    }

    /**
     * Đếm tổng số kết quả khớp với bộ lọc (phục vụ phân trang)
     */
    public function countSearch(array $f): int
    {
        return (int)($this->call('sp_count_search_properties_v3', $this->searchParameters($f))[0]['total'] ?? 0);
    }

    /**
     * Lấy chi tiết đầy đủ 1 tin BĐS (kèm loại, địa chỉ, môi giới và tọa độ fallback)
     */
    public function findDetail(int $id): ?array
    {
        $prop = $this->call('sp_property_detail', [$id])[0] ?? null;
        if (!$prop) return null;
        $prop['is_coord_fallback'] = empty($prop['latitude']) || empty($prop['longitude']);
        if ($prop['is_coord_fallback']) {
            $coords = $this->getDistrictFallbackCoords((int)($prop['district_id'] ?? 0), (int)($prop['province_id'] ?? 1));
            $prop['latitude'] = $coords['lat'];
            $prop['longitude'] = $coords['lng'];
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
        return $this->call('sp_property_detail_images', [$propertyId]);
    }

    /** Danh sách tiện ích của tin */
    public function getAmenities(int $propertyId): array
    {
        return $this->call('sp_property_detail_amenities', [$propertyId]);
    }

    /** Tăng lượt xem tin */
    public function incrementViews(int $propertyId): void
    {
        $this->call('sp_property_increment_views', [$propertyId]);
    }

    /** Lấy các tin cùng loại tương tự */
    public function getSimilar(int $propertyId, int $typeId, int $limit = 3): array
    {
        return $this->call('sp_property_same_type', [$propertyId, $typeId, max(0, $limit)]);
    }

    /** Lấy danh sách bất động sản tương tự theo khu vực, phân khúc & mức giá */
    public function getSimilarProperties(int $currentId, int $districtId, int $typeId, float $price, int $limit = 4): array
    {
        return (new Property())->getSimilarProperties($currentId, $districtId, $typeId, $price, $limit);
    }
}
