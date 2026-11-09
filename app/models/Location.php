<?php
// [A] Tỉnh / Quận / Phường (dùng cho dropdown phụ thuộc bằng AJAX)
class Location extends Model
{
    protected string $table = 'provinces';

    public function provinces(): array { return $this->query('SELECT id, name FROM provinces ORDER BY CASE WHEN id IN (1, 2, 3) THEN id ELSE 999 END, name')->fetchAll(); }
    public function districtsByProvince(int $id): array { return $this->query('SELECT id, name FROM districts WHERE province_id = ? ORDER BY name', [$id])->fetchAll(); }
    public function wardsByDistrict(int $id): array { return $this->query('SELECT id, name FROM wards WHERE district_id = ? ORDER BY name', [$id])->fetchAll(); }
    public function provinceByDistrict(int $districtId): ?int
    {
        $row = $this->query('SELECT province_id FROM districts WHERE id = ?', [$districtId])->fetch();
        return $row ? (int)$row['province_id'] : null;
    }
    public function isValidWard(int $provinceId, int $districtId, int $wardId): bool
    {
        return (bool)$this->query(
            'SELECT w.id FROM wards w
             JOIN districts d ON d.id = w.district_id
             WHERE w.id = ? AND d.id = ? AND d.province_id = ?',
            [$wardId, $districtId, $provinceId]
        )->fetch();
    }

    /**
     * Chuẩn hóa chuỗi tiếng Việt về dạng chữ thường không dấu (đ -> d)
     */
    public function normalizeVietnamese(string $str): string
    {
        $str = trim(mb_strtolower($str, 'UTF-8'));
        if ($str === '') {
            return '';
        }

        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
            'đ'=>'d',
        ];
        $str = strtr($str, $map);
        $str = preg_replace('/[^a-z0-9\s]/', ' ', $str) ?? $str;
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;
        return trim($str);
    }

    private function stripPrefix(string $norm, string $level): string
    {
        if ($norm === '') {
            return '';
        }
        if ($level === 'province') {
            return trim((string)preg_replace('/^(thanh pho|tp|tinh)\s+/', '', $norm));
        }
        if ($level === 'district') {
            $stripped = trim((string)preg_replace('/^(quan|q|huyen|h|thi xa|tx|thanh pho|tp)\s+/', '', $norm));
            if (preg_match('/^\d+$/', $stripped)) {
                return 'quan ' . ltrim($stripped, '0');
            }
            return $stripped;
        }
        if ($level === 'ward') {
            $stripped = trim((string)preg_replace('/^(phuong|p|xa|x|thi tran|tt)\s+/', '', $norm));
            $stripped = $this->normalizeRomanSuffix($stripped);
            if (preg_match('/^\d+$/', $stripped)) {
                return 'phuong ' . ltrim($stripped, '0');
            }
            return $stripped;
        }
        return $norm;
    }

    private function normalizeRomanSuffix(string $norm): string
    {
        $replacements = [
            '/\bviii$/' => '8',
            '/\bvii$/'  => '7',
            '/\bvi$/'   => '6',
            '/\biv$/'   => '4',
            '/\bv$/'    => '5',
            '/\biii$/'  => '3',
            '/\bii$/'   => '2',
            '/\bi$/'    => '1',
        ];
        return (string)preg_replace(array_keys($replacements), array_values($replacements), $norm);
    }

    private function provinceAliases(): array
    {
        return [
            'ho chi minh'     => ['tp hcm', 'tphcm', 'hcm', 'ho chi minh city', 'sai gon', 'saigon', 'sg'],
            'ha noi'          => ['tp hn', 'tphn', 'hn', 'hanoi', 'thu do ha noi'],
            'da nang'         => ['tp dn', 'tpdn', 'danang', 'da nang city'],
            'hai phong'       => ['tp hp', 'tphp', 'haiphong', 'hai phong city'],
            'can tho'         => ['tp ct', 'tpct', 'cantho', 'can tho city'],
            'hue'             => ['thua thien hue', 'tinh thua thien hue', 'tp hue', 'hue city'],
            'ba ria vung tau' => ['brvt', 'vung tau'],
            'dak lak'         => ['dac lac', 'daklak', 'buon ma thuot'],
            'dak nong'        => ['dac nong', 'daknong'],
            'kon tum'         => ['kontum'],
            'bac kan'         => ['bac can'],
        ];
    }

    /**
     * Ánh xạ các tên phường mới sau sắp xếp đơn vị hành chính (dữ liệu bản đồ hiện hành)
     * về đúng Quận/Huyện và Phường/Xã đại diện trong CSDL địa giới 3 cấp.
     */
    private function mergedWardAliases(): array
    {
        return [
            1 => [ // TP. Hồ Chí Minh
                'phuong sai gon'        => ['Quận 1', 'Phường Bến Nghé'],
                'phuong tan dinh'       => ['Quận 1', 'Phường Tân Định'],
                'phuong ben thanh'      => ['Quận 1', 'Phường Bến Thành'],
                'phuong cau ong lanh'   => ['Quận 1', 'Phường Cầu Ông Lãnh'],
                'phuong ban co'         => ['Quận 3', 'Phường 3'],
                'phuong xuan hoa'       => ['Quận 3', 'Phường Võ Thị Sáu'],
                'phuong nhieu loc'      => ['Quận 3', 'Phường 12'],
                'phuong khanh hoi'      => ['Quận 4', 'Phường 8'],
                'phuong vinh hoi'       => ['Quận 4', 'Phường 4'],
                'phuong xom chieu'      => ['Quận 4', 'Phường 13'],
                'phuong cho quan'       => ['Quận 5', 'Phường 2'],
                'phuong an dong'        => ['Quận 5', 'Phường 5'],
                'phuong cho lon'        => ['Quận 5', 'Phường 11'],
                'phuong binh tien'      => ['Quận 6', 'Phường 2'],
                'phuong binh tay'       => ['Quận 6', 'Phường 9'],
                'phuong binh phu'       => ['Quận 6', 'Phường 11'],
                'phuong phu lam'        => ['Quận 6', 'Phường 13'],
                'phuong tan thuan'      => ['Quận 7', 'Phường Tân Thuận Đông'],
                'phuong phu thuan'      => ['Quận 7', 'Phường Phú Thuận'],
                'phuong tan my'         => ['Quận 7', 'Phường Tân Phú'],
                'phuong tan hung'       => ['Quận 7', 'Phường Tân Hưng'],
                'phuong chanh hung'     => ['Quận 8', 'Phường Rạch Ông'],
                'phuong binh dong'      => ['Quận 8', 'Phường 5'],
                'phuong phu dinh'       => ['Quận 8', 'Phường 14'],
                'phuong dien hong'      => ['Quận 10', 'Phường 6'],
                'phuong vuon lai'       => ['Quận 10', 'Phường 2'],
                'phuong hoa hung'       => ['Quận 10', 'Phường 12'],
                'phuong hoa binh'       => ['Quận 11', 'Phường 5'],
                'phuong phu tho'        => ['Quận 11', 'Phường 11'],
                'phuong binh thoi'      => ['Quận 11', 'Phường 3'],
                'phuong minh phung'     => ['Quận 11', 'Phường 10'],
                'phuong dong hung thuan'=> ['Quận 12', 'Phường Đông Hưng Thuận'],
                'phuong trung my tay'   => ['Quận 12', 'Phường Trung Mỹ Tây'],
                'phuong tan thoi hiep'  => ['Quận 12', 'Phường Tân Thới Hiệp'],
                'phuong thoi an'        => ['Quận 12', 'Phường Thới An'],
                'phuong an phu dong'    => ['Quận 12', 'Phường An Phú Đông'],
                'phuong gia dinh'       => ['Quận Bình Thạnh', 'Phường 1'],
                'phuong binh thanh'     => ['Quận Bình Thạnh', 'Phường 12'],
                'phuong binh loi trung' => ['Quận Bình Thạnh', 'Phường 13'],
                'phuong thanh my tay'   => ['Quận Bình Thạnh', 'Phường 22'],
                'phuong binh quoi'      => ['Quận Bình Thạnh', 'Phường 27'],
                'phuong hanh thong'     => ['Quận Gò Vấp', 'Phường 1'],
                'phuong an nhon'        => ['Quận Gò Vấp', 'Phường 5'],
                'phuong go vap'         => ['Quận Gò Vấp', 'Phường 10'],
                'phuong thong tay hoi'  => ['Quận Gò Vấp', 'Phường 11'],
                'phuong an hoi tay'     => ['Quận Gò Vấp', 'Phường 12'],
                'phuong an hoi dong'    => ['Quận Gò Vấp', 'Phường 15'],
                'phuong duc nhuan'      => ['Quận Phú Nhuận', 'Phường 5'],
                'phuong cau kieu'       => ['Quận Phú Nhuận', 'Phường 2'],
                'phuong phu nhuan'      => ['Quận Phú Nhuận', 'Phường 10'],
                'phuong tan son hoa'    => ['Quận Tân Bình', 'Phường 2'],
                'phuong tan son nhat'   => ['Quận Tân Bình', 'Phường 4'],
                'phuong tan hoa'        => ['Quận Tân Bình', 'Phường 8'],
                'phuong bay hien'       => ['Quận Tân Bình', 'Phường 11'],
                'phuong tan binh'       => ['Quận Tân Bình', 'Phường 14'],
                'phuong tan son'        => ['Quận Tân Bình', 'Phường 15'],
                'phuong tay thanh'      => ['Quận Tân Phú', 'Phường Tây Thạnh'],
                'phuong tan son nhi'    => ['Quận Tân Phú', 'Phường Tân Sơn Nhì'],
                'phuong phu tho hoa'    => ['Quận Tân Phú', 'Phường Phú Thọ Hòa'],
                'phuong tan phu'        => ['Quận Tân Phú', 'Phường Tân Thành'],
                'phuong phu thanh'      => ['Quận Tân Phú', 'Phường Phú Thạnh'],
                'phuong binh hung hoa'  => ['Quận Bình Tân', 'Phường Bình Hưng Hòa'],
                'phuong binh tri dong'  => ['Quận Bình Tân', 'Phường Bình Trị Đông'],
                'phuong tan tao'        => ['Quận Bình Tân', 'Phường Tân Tạo'],
                'phuong an lac'         => ['Quận Bình Tân', 'Phường An Lạc'],
                'phuong thu duc'        => ['TP. Thủ Đức', 'Phường Trường Thọ'],
                'phuong hiep binh'      => ['TP. Thủ Đức', 'Phường Hiệp Bình Chánh'],
                'phuong tam binh'       => ['TP. Thủ Đức', 'Phường Tam Bình'],
                'phuong linh xuan'      => ['TP. Thủ Đức', 'Phường Linh Xuân'],
                'phuong tang nhon phu'  => ['TP. Thủ Đức', 'Phường Tăng Nhơn Phú A'],
                'phuong long binh'      => ['TP. Thủ Đức', 'Phường Long Bình'],
                'phuong long phuoc'     => ['TP. Thủ Đức', 'Phường Long Phước'],
                'phuong long truong'    => ['TP. Thủ Đức', 'Phường Long Trường'],
                'phuong cat lai'        => ['TP. Thủ Đức', 'Phường Cát Lái'],
                'phuong binh trung'     => ['TP. Thủ Đức', 'Phường Bình Trưng Tây'],
                'phuong phuoc long'     => ['TP. Thủ Đức', 'Phường Phước Long B'],
                'phuong an khanh'       => ['TP. Thủ Đức', 'Phường An Khánh'],
            ],
            2 => [ // Hà Nội
                'phuong hoan kiem'      => ['Quận Hoàn Kiếm', 'Phường Hàng Đào'],
                'phuong cua nam'        => ['Quận Hoàn Kiếm', 'Phường Hàng Bài'],
                'phuong ba dinh'        => ['Quận Ba Đình', 'Phường Điện Biên'],
                'phuong ngoc ha'        => ['Quận Ba Đình', 'Phường Ngọc Hà'],
                'phuong giang vo'       => ['Quận Ba Đình', 'Phường Giảng Võ'],
                'phuong hai ba trung'   => ['Quận Hai Bà Trưng', 'Phường Nguyễn Du'],
                'phuong bach mai'       => ['Quận Hai Bà Trưng', 'Phường Bạch Mai'],
                'phuong vinh tuy'       => ['Quận Hai Bà Trưng', 'Phường Vĩnh Tuy'],
                'phuong dong da'        => ['Quận Đống Đa', 'Phường Quang Trung'],
                'phuong kim lien'       => ['Quận Đống Đa', 'Phường Kim Liên'],
                'phuong van mieu quoc tu giam' => ['Quận Đống Đa', 'Phường Văn Miếu - Quốc Tử Giám'],
                'phuong lang'           => ['Quận Đống Đa', 'Phường Láng Thượng'],
                'phuong o cho dua'      => ['Quận Đống Đa', 'Phường Ô Chợ Dừa'],
                'phuong tay ho'         => ['Quận Tây Hồ', 'Phường Quảng An'],
                'phuong phu thuong'     => ['Quận Tây Hồ', 'Phường Phú Thượng'],
                'phuong cau giay'       => ['Quận Cầu Giấy', 'Phường Dịch Vọng'],
                'phuong nghia do'       => ['Quận Cầu Giấy', 'Phường Nghĩa Đô'],
                'phuong yen hoa'        => ['Quận Cầu Giấy', 'Phường Yên Hòa'],
                'phuong thanh xuan'     => ['Quận Thanh Xuân', 'Phường Thanh Xuân Trung'],
                'phuong khuong dinh'    => ['Quận Thanh Xuân', 'Phường Khương Đình'],
                'phuong phuong liet'    => ['Quận Thanh Xuân', 'Phường Phương Liệt'],
                'phuong hoang mai'      => ['Quận Hoàng Mai', 'Phường Hoàng Văn Thụ'],
                'phuong dinh cong'      => ['Quận Hoàng Mai', 'Phường Định Công'],
                'phuong long bien'      => ['Quận Long Biên', 'Phường Việt Hưng'],
                'phuong bo de'          => ['Quận Long Biên', 'Phường Bồ Đề'],
                'phuong ha dong'        => ['Quận Hà Đông', 'Phường Quang Trung'],
                'phuong tu liem'        => ['Quận Nam Từ Liêm', 'Phường Mỹ Đình 1'],
                'phuong tay mo'         => ['Quận Nam Từ Liêm', 'Phường Tây Mỗ'],
                'phuong dai mo'         => ['Quận Nam Từ Liêm', 'Phường Đại Mỗ'],
                'phuong xuan phuong'    => ['Quận Nam Từ Liêm', 'Phường Xuân Phương'],
            ],
            3 => [ // Đà Nẵng
                'phuong hai chau'       => ['Quận Hải Châu', 'Phường Hải Châu'],
                'phuong hoa cuong'      => ['Quận Hải Châu', 'Phường Hòa Cường Bắc'],
                'phuong thanh khe'      => ['Quận Thanh Khê', 'Phường Chính Gián'],
                'phuong an khe'         => ['Quận Thanh Khê', 'Phường An Khê'],
                'phuong son tra'        => ['Quận Sơn Trà', 'Phường Mân Thái'],
                'phuong an hai'         => ['Quận Sơn Trà', 'Phường An Hải Bắc'],
                'phuong ngu hanh son'   => ['Quận Ngũ Hành Sơn', 'Phường Mỹ An'],
                'phuong lien chieu'     => ['Quận Liên Chiểu', 'Phường Hòa Minh'],
                'phuong hoa khanh'      => ['Quận Liên Chiểu', 'Phường Hòa Khánh Bắc'],
                'phuong cam le'         => ['Quận Cẩm Lệ', 'Phường Khuê Trung'],
                'phuong hoa xuan'       => ['Quận Cẩm Lệ', 'Phường Hòa Xuân'],
            ],
        ];
    }

    /**
     * Tìm kiếm nhanh đơn vị hành chính trong CSDL (63 Tỉnh, 696 Quận, 10.052 Phường)
     */
    public function searchAdministrative(string $query, int $limit = 8): array
    {
        $raw = trim($query);
        if ($raw === '' || mb_strlen($raw, 'UTF-8') < 2) {
            return [];
        }

        $segments = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $raw) ?: [])));
        $normFull = $this->normalizeVietnamese($raw);
        if ($normFull === '') {
            return [];
        }

        // Bóc tách gợi ý cụm phường/quận nếu người dùng gõ nhiều phân đoạn hoặc gõ liền
        $primaryTerm = count($segments) > 0 ? $this->normalizeVietnamese($segments[0]) : $normFull;
        $secondaryTerm = count($segments) > 1 ? $this->normalizeVietnamese($segments[1]) : '';
        $wardCoreQuery = $this->stripPrefix($primaryTerm, 'ward');
        $distCoreQuery = $this->stripPrefix($primaryTerm, 'district');
        $provCoreQuery = $this->stripPrefix($primaryTerm, 'province');

        $results = [];
        $seenKeys = [];

        // 1. Tìm Tỉnh/Thành phố (chỉ khớp khi truy vấn thực sự hướng tới tên tỉnh/viết tắt phổ biến)
        $provinces = $this->provinces();
        $aliases = $this->provinceAliases();
        foreach ($provinces as $prov) {
            $pNorm = $this->normalizeVietnamese($prov['name']);
            $pCore = $this->stripPrefix($pNorm, 'province');
            $pAliases = $aliases[$pCore] ?? [];

            $matched = ($pCore === $provCoreQuery || $pNorm === $normFull);
            if (!$matched && strlen($provCoreQuery) >= 3 && !preg_match('/^\d+/', $provCoreQuery)) {
                $matched = str_contains($pCore, $provCoreQuery) || str_contains($provCoreQuery, $pCore);
            }
            if (!$matched) {
                foreach ($pAliases as $al) {
                    if ($al === $normFull || $al === $provCoreQuery) {
                        $matched = true;
                        break;
                    }
                }
            }
            if ($matched) {
                $key = 'p:' . $prov['id'];
                if (!isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $results[] = [
                        'level'         => 'province',
                        'province_id'   => (int)$prov['id'],
                        'province_name' => $prov['name'],
                        'district_id'   => null,
                        'district_name' => null,
                        'ward_id'       => null,
                        'ward_name'     => null,
                        'display_name'  => $prov['name'],
                        'score'         => ($pCore === $provCoreQuery) ? 100 : 70,
                        'address'       => ['city' => $prov['name'], 'country' => 'Việt Nam'],
                    ];
                }
            }
        }

        // 2. Tìm Quận/Huyện (xử lý được cả chữ đ -> d và viết tắt không dấu)
        $sqlDist = "SELECT d.id AS district_id, d.name AS district_name, p.id AS province_id, p.name AS province_name
                    FROM districts d
                    JOIN provinces p ON p.id = d.province_id";
        $allDistricts = $this->query($sqlDist)->fetchAll();

        foreach ($allDistricts as $d) {
            $dNorm = $this->normalizeVietnamese($d['district_name']);
            $dCore = $this->stripPrefix($dNorm, 'district');
            $pNorm = $this->normalizeVietnamese($d['province_name']);
            $pCore = $this->stripPrefix($pNorm, 'province');

            $score = 0;
            if ($dNorm === $normFull || $dCore === $distCoreQuery) {
                $score = 95;
            } elseif (strlen($distCoreQuery) >= 3 && !preg_match('/^\d+\s+/', $normFull) && str_contains($dCore, $distCoreQuery)) {
                $score = 75;
            } elseif ($secondaryTerm !== '' && (str_contains($dCore, $this->stripPrefix($secondaryTerm, 'district')) || str_contains($pCore, $this->stripPrefix($secondaryTerm, 'province')))) {
                if (str_contains($dCore, $distCoreQuery)) {
                    $score = 88;
                }
            }

            if ($score > 0) {
                $key = 'd:' . $d['district_id'];
                if (!isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $display = $d['district_name'] . ', ' . $d['province_name'];
                    $results[] = [
                        'level'         => 'district',
                        'province_id'   => (int)$d['province_id'],
                        'province_name' => $d['province_name'],
                        'district_id'   => (int)$d['district_id'],
                        'district_name' => $d['district_name'],
                        'ward_id'       => null,
                        'ward_name'     => null,
                        'display_name'  => $display,
                        'score'         => $score,
                        'address'       => [
                            'city_district' => $d['district_name'],
                            'city'          => $d['province_name'],
                            'country'       => 'Việt Nam',
                        ],
                    ];
                }
            }
        }

        // 3. Tìm Phường/Xã (ưu tiên khi gõ tên phường hoặc phường + quận)
        $tokens = array_values(array_filter(explode(' ', $normFull)));
        $startsWithStreetNumber = preg_match('/^\d+[a-z0-9\/\-]*\s+/i', $raw)
            && !preg_match('/^(phuong|p|xa|x|quan|q)\s*\d+/i', $normFull);

        if (!$startsWithStreetNumber && strlen($wardCoreQuery) >= 2) {
            // Tách hậu tố quận nếu người dùng gõ kiểu "Phường 12 Bình Thạnh" (không có dấu phẩy)
            $searchCandidates = [$wardCoreQuery];
            if ($secondaryTerm === '' && count($tokens) >= 3) {
                // Thử tách 2 hoặc 3 từ đầu làm phường, phần còn lại làm ngữ cảnh quận/tỉnh
                $head2 = $this->stripPrefix(implode(' ', array_slice($tokens, 0, 2)), 'ward');
                $tail2 = implode(' ', array_slice($tokens, 2));
                if ($head2 !== '' && strlen($tail2) >= 2) {
                    $searchCandidates[] = $head2 . '|' . $tail2;
                }
                $head3 = $this->stripPrefix(implode(' ', array_slice($tokens, 0, 3)), 'ward');
                $tail3 = implode(' ', array_slice($tokens, 3));
                if ($head3 !== '' && strlen($tail3) >= 2) {
                    $searchCandidates[] = $head3 . '|' . $tail3;
                }
            }

            $likeParam = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $wardCoreQuery) . '%';
            if (str_starts_with($wardCoreQuery, 'phuong ')) {
                $numOnly = trim(substr($wardCoreQuery, 7));
                $likeParam = '%' . $numOnly . '%';
            } elseif (count($searchCandidates) > 1) {
                $firstPart = explode('|', $searchCandidates[1])[0];
                $numOnly = preg_replace('/^phuong\s+/', '', $firstPart);
                $likeParam = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$numOnly) . '%';
            }

            $sqlWard = "SELECT w.id AS ward_id, w.name AS ward_name,
                               d.id AS district_id, d.name AS district_name,
                               p.id AS province_id, p.name AS province_name
                        FROM wards w
                        JOIN districts d ON d.id = w.district_id
                        JOIN provinces p ON p.id = d.province_id
                        WHERE REPLACE(REPLACE(LOWER(w.name), 'Đ', 'd'), 'đ', 'd') LIKE ?
                        LIMIT 150";
            $wardRows = $this->query($sqlWard, [$likeParam])->fetchAll();

            foreach ($wardRows as $w) {
                $wNorm = $this->normalizeVietnamese($w['ward_name']);
                $wCore = $this->stripPrefix($wNorm, 'ward');
                $dNorm = $this->normalizeVietnamese($w['district_name']);
                $dCore = $this->stripPrefix($dNorm, 'district');
                $pNorm = $this->normalizeVietnamese($w['province_name']);
                $pCore = $this->stripPrefix($pNorm, 'province');

                $score = 0;
                if ($wNorm === $normFull || $wCore === $wardCoreQuery) {
                    $score = 92;
                } elseif (strlen($wardCoreQuery) >= 3 && str_contains($wCore, $wardCoreQuery)) {
                    $score = 72;
                }

                // Khớp kèm ngữ cảnh quận/tỉnh (có dấu phẩy hoặc viết liền)
                if ($secondaryTerm !== '') {
                    $secDist = $this->stripPrefix($secondaryTerm, 'district');
                    $secProv = $this->stripPrefix($secondaryTerm, 'province');
                    if (($wCore === $wardCoreQuery || str_contains($wCore, $wardCoreQuery))
                        && (str_contains($dCore, $secDist) || str_contains($pCore, $secProv))) {
                        $score = 110;
                    } else {
                        $score = 0;
                    }
                } elseif (count($searchCandidates) > 1) {
                    for ($c = 1; $c < count($searchCandidates); $c++) {
                        [$wPart, $tailPart] = explode('|', $searchCandidates[$c]);
                        $tailDist = $this->stripPrefix($tailPart, 'district');
                        if (($wCore === $wPart || str_contains($wCore, $wPart))
                            && (str_contains($dCore, $tailDist) || str_contains($dNorm, $tailPart) || str_contains($pCore, $tailPart))) {
                            $score = max($score, 108);
                        }
                    }
                }

                if ($score > 0) {
                    $key = 'w:' . $w['ward_id'];
                    if (!isset($seenKeys[$key])) {
                        $seenKeys[$key] = true;
                        $display = $w['ward_name'] . ', ' . $w['district_name'] . ', ' . $w['province_name'];
                        $results[] = [
                            'level'         => 'ward',
                            'province_id'   => (int)$w['province_id'],
                            'province_name' => $w['province_name'],
                            'district_id'   => (int)$w['district_id'],
                            'district_name' => $w['district_name'],
                            'ward_id'       => (int)$w['ward_id'],
                            'ward_name'     => $w['ward_name'],
                            'display_name'  => $display,
                            'score'         => $score,
                            'address'       => [
                                'suburb'        => $w['ward_name'],
                                'city_district' => $w['district_name'],
                                'city'          => $w['province_name'],
                                'country'       => 'Việt Nam',
                            ],
                        ];
                    }
                }
            }
        }

        usort($results, static function (array $a, array $b): int {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            return mb_strlen($a['display_name'], 'UTF-8') <=> mb_strlen($b['display_name'], 'UTF-8');
        });

        $results = array_slice($results, 0, max(1, $limit));
        foreach ($results as &$item) {
            unset($item['score']);
        }
        unset($item);

        return $results;
    }

    /**
     * Đối soát chuỗi/tên Tỉnh - Quận - Phường (bao gồm phường sau sáp nhập 2025 trên bản đồ)
     * về đúng ID Tỉnh -> Quận -> Phường trong CSDL 3 cấp.
     */
    public function resolveFromAddress(
        string $wardName = '',
        string $provinceName = '',
        int $provinceId = 0,
        string $districtName = '',
        string $displayName = ''
    ): ?array {
        $provinces = $this->provinces();
        $aliases = $this->provinceAliases();

        $matchedProvince = null;
        if ($provinceId > 0) {
            foreach ($provinces as $p) {
                if ((int)$p['id'] === $provinceId) {
                    $matchedProvince = ['id' => (int)$p['id'], 'name' => $p['name']];
                    break;
                }
            }
        }

        $segments = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $displayName) ?: [])));
        if ($matchedProvince === null) {
            $provCandidates = array_filter(array_merge([$provinceName], array_reverse($segments)));
            foreach ($provCandidates as $cand) {
                $cNorm = $this->normalizeVietnamese((string)$cand);
                $cCore = $this->stripPrefix($cNorm, 'province');
                if ($cNorm === '' || $cNorm === 'viet nam' || $cNorm === 'vietnam' || preg_match('/^\d{5,6}$/', $cNorm)) {
                    continue;
                }
                foreach ($provinces as $p) {
                    $pNorm = $this->normalizeVietnamese($p['name']);
                    $pCore = $this->stripPrefix($pNorm, 'province');
                    $pAliases = $aliases[$pCore] ?? [];
                    if ($cCore === $pCore || $cNorm === $pNorm || in_array($cNorm, $pAliases, true) || in_array($cCore, $pAliases, true)) {
                        $matchedProvince = ['id' => (int)$p['id'], 'name' => $p['name']];
                        break 2;
                    }
                }
            }
        }

        if ($matchedProvince === null) {
            return null;
        }

        $pid = $matchedProvince['id'];
        $districts = $this->districtsByProvince($pid);
        $matchedDistrict = null;

        // 1. Thử khớp Quận/Huyện trực tiếp từ districtName hoặc các segment trong displayName
        $distCandidates = array_filter(array_merge([$districtName], array_reverse($segments)));
        foreach ($distCandidates as $cand) {
            $cNorm = $this->normalizeVietnamese((string)$cand);
            if ($cNorm === '' || preg_match('/^(khu pho|kp|to dan pho|to|hem|ngo|so)\b/', $cNorm)) {
                continue;
            }
            $cCore = $this->stripPrefix($cNorm, 'district');
            foreach ($districts as $d) {
                $dNorm = $this->normalizeVietnamese($d['name']);
                $dCore = $this->stripPrefix($dNorm, 'district');
                if ($cNorm === $dNorm || $cCore === $dCore) {
                    $matchedDistrict = ['id' => (int)$d['id'], 'name' => $d['name']];
                    break 2;
                }
            }
        }

        // 2. Tạo danh sách ứng viên Phường/Xã
        $wardCandidates = [];
        if ($wardName !== '') {
            $wardCandidates[] = $wardName;
        }
        foreach (array_reverse($segments) as $seg) {
            $sNorm = $this->normalizeVietnamese($seg);
            if ($sNorm === '' || $sNorm === 'viet nam' || $sNorm === 'vietnam' || preg_match('/^\d{5,6}$/', $sNorm)) {
                continue;
            }
            if (preg_match('/^(phuong|xa|thi tran|p|x|tt)\b/', $sNorm)) {
                $wardCandidates[] = $seg;
            }
        }
        foreach (array_reverse($segments) as $seg) {
            if (!in_array($seg, $wardCandidates, true)) {
                $wardCandidates[] = $seg;
            }
        }

        // 3. Kiểm tra bảng ánh xạ phường mới sau sắp xếp đơn vị hành chính -> (Quận, Phường đại diện)
        $aliasTable = $this->mergedWardAliases()[$pid] ?? [];
        $matchedWard = null;

        if (!empty($aliasTable)) {
            foreach ($wardCandidates as $cand) {
                $cNorm = $this->normalizeVietnamese((string)$cand);
                if (isset($aliasTable[$cNorm])) {
                    [$targetDistName, $targetWardName] = $aliasTable[$cNorm];
                    if ($matchedDistrict === null) {
                        $tDistNorm = $this->normalizeVietnamese($targetDistName);
                        foreach ($districts as $d) {
                            if ($this->normalizeVietnamese($d['name']) === $tDistNorm) {
                                $matchedDistrict = ['id' => (int)$d['id'], 'name' => $d['name']];
                                break;
                            }
                        }
                    }
                    if ($matchedDistrict !== null) {
                        $wards = $this->wardsByDistrict($matchedDistrict['id']);
                        $tWardNorm = $this->normalizeVietnamese($targetWardName);
                        foreach ($wards as $w) {
                            if ($this->normalizeVietnamese($w['name']) === $tWardNorm) {
                                $matchedWard = ['id' => (int)$w['id'], 'name' => $w['name']];
                                break;
                            }
                        }
                    }
                    if ($matchedDistrict !== null && $matchedWard !== null) {
                        return [
                            'province'     => $matchedProvince,
                            'district'     => $matchedDistrict,
                            'ward'         => $matchedWard,
                            'display_name' => $matchedWard['name'] . ', ' . $matchedDistrict['name'] . ', ' . $matchedProvince['name'],
                        ];
                    }
                }
            }
        }

        // 4. Nếu đã có Quận, tìm Phường trong Quận đó (khớp chính xác hoặc khớp tiền tố như Phước Long -> Phước Long A/B)
        if ($matchedDistrict !== null) {
            $wards = $this->wardsByDistrict($matchedDistrict['id']);
            foreach ($wardCandidates as $cand) {
                $cNorm = $this->normalizeRomanSuffix($this->normalizeVietnamese((string)$cand));
                $cCore = $this->stripPrefix($cNorm, 'ward');
                if ($cCore === '') {
                    continue;
                }
                foreach ($wards as $w) {
                    $wNorm = $this->normalizeRomanSuffix($this->normalizeVietnamese($w['name']));
                    $wCore = $this->stripPrefix($wNorm, 'ward');
                    if ($cNorm === $wNorm || $cCore === $wCore) {
                        $matchedWard = ['id' => (int)$w['id'], 'name' => $w['name']];
                        break 2;
                    }
                }
                if (strlen($cCore) >= 4) {
                    foreach ($wards as $w) {
                        $wNorm = $this->normalizeRomanSuffix($this->normalizeVietnamese($w['name']));
                        $wCore = $this->stripPrefix($wNorm, 'ward');
                        if (str_starts_with($wCore, $cCore . ' ') || str_starts_with($cCore, $wCore . ' ')) {
                            $matchedWard = ['id' => (int)$w['id'], 'name' => $w['name']];
                            break 2;
                        }
                    }
                }
            }
        } else {
            // 5. Nếu chưa có Quận (OSM bỏ city_district), tra cứu trực tiếp toàn bộ Phường trong Tỉnh đó
            $allWards = $this->query(
                'SELECT w.id AS ward_id, w.name AS ward_name, d.id AS district_id, d.name AS district_name
                 FROM wards w
                 JOIN districts d ON d.id = w.district_id
                 WHERE d.province_id = ?',
                [$pid]
            )->fetchAll();

            foreach ($wardCandidates as $cand) {
                $cNorm = $this->normalizeRomanSuffix($this->normalizeVietnamese((string)$cand));
                if (!preg_match('/^(phuong|xa|thi tran|p|x|tt)\s+/', $cNorm) && $cand !== $wardName) {
                    continue;
                }
                $cCore = $this->stripPrefix($cNorm, 'ward');
                if ($cCore === '') {
                    continue;
                }
                foreach ($allWards as $w) {
                    $wNorm = $this->normalizeRomanSuffix($this->normalizeVietnamese($w['ward_name']));
                    $wCore = $this->stripPrefix($wNorm, 'ward');
                    if ($cNorm === $wNorm || $cCore === $wCore) {
                        $matchedDistrict = ['id' => (int)$w['district_id'], 'name' => $w['district_name']];
                        $matchedWard = ['id' => (int)$w['ward_id'], 'name' => $w['ward_name']];
                        break 2;
                    }
                }
                if (strlen($cCore) >= 5) {
                    foreach ($allWards as $w) {
                        $wNorm = $this->normalizeRomanSuffix($this->normalizeVietnamese($w['ward_name']));
                        $wCore = $this->stripPrefix($wNorm, 'ward');
                        if (str_starts_with($wCore, $cCore . ' ')) {
                            $matchedDistrict = ['id' => (int)$w['district_id'], 'name' => $w['district_name']];
                            $matchedWard = ['id' => (int)$w['ward_id'], 'name' => $w['ward_name']];
                            break 2;
                        }
                    }
                }
            }
        }

        // 6. Nếu có Quận nhưng Ward vẫn khớp cùng tên Quận (VD: Phường Bình Tân -> Quận Bình Tân) -> chọn phường khớp tiền tố hoặc đại diện
        if ($matchedDistrict !== null && $matchedWard === null && $wardName !== '') {
            $wards = $this->wardsByDistrict($matchedDistrict['id']);
            if (!empty($wards)) {
                $wardCore = $this->stripPrefix($this->normalizeVietnamese($wardName), 'ward');
                foreach ($wards as $w) {
                    $wCore = $this->stripPrefix($this->normalizeVietnamese($w['name']), 'ward');
                    if ($wardCore !== '' && (str_contains($wCore, $wardCore) || str_contains($wardCore, $wCore))) {
                        $matchedWard = ['id' => (int)$w['id'], 'name' => $w['name']];
                        break;
                    }
                }
            }
        }

        $parts = [];
        if ($matchedWard !== null) {
            $parts[] = $matchedWard['name'];
        }
        if ($matchedDistrict !== null) {
            $parts[] = $matchedDistrict['name'];
        }
        $parts[] = $matchedProvince['name'];

        return [
            'province'     => $matchedProvince,
            'district'     => $matchedDistrict,
            'ward'         => $matchedWard,
            'display_name' => implode(', ', $parts),
        ];
    }
}
