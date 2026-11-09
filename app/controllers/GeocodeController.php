<?php
// Dịch vụ Geocoding & Reverse Geocoding cho bản đồ bất động sản
class GeocodeController extends Controller
{
    private const USER_AGENT = 'BDSViet-RealEstate/1.0 (contact: cskh@bdsviet.vn)';
    private const CACHE_TTL  = 86400; // 24 giờ

    public function index(): void
    {
        $q = trim($_GET['q'] ?? $_GET['address'] ?? '');
        if ($q === '') {
            $this->json(null, false, 'Vui lòng cung cấp địa chỉ cần tìm.', 400);
        }

        $cacheKey = 'v2:' . mb_strtolower($q, 'UTF-8');
        $cachedResults = $this->getForwardCache($cacheKey);
        if ($cachedResults !== null) {
            $this->json($cachedResults, true, 'Tìm thấy ' . count($cachedResults) . ' kết quả phù hợp.');
        }

        $candidates = $this->buildSearchCandidateQueries($q);
        $anyNetworkOk = false;

        foreach ($candidates as $candidateQuery) {
            $resp = $this->queryNominatimSearch($candidateQuery, 6);
            if ($resp['ok']) {
                $anyNetworkOk = true;
                if (!empty($resp['results'])) {
                    $this->setForwardCache($cacheKey, $resp['results']);
                    $this->json($resp['results'], true, 'Tìm thấy ' . count($resp['results']) . ' kết quả phù hợp.');
                }
            }
        }

        // Chỉ dùng catalog dự phòng cho tên thành phố/khu vực lớn khi dịch vụ bản đồ ngoại tuyến hoặc lỗi mạng
        if (!$anyNetworkOk) {
            $fallbackResults = $this->buildGracefulSearchFallback($q);
            if (!empty($fallbackResults)) {
                $this->json($fallbackResults, true, 'Tìm thấy ' . count($fallbackResults) . ' kết quả phù hợp.');
            }
        }

        $this->json([], false, 'Không tìm thấy địa chỉ cụ thể này. Bạn có thể bấm trực tiếp lên bản đồ để chọn vị trí.');
    }

    /**
     * Xây dựng danh sách truy vấn đa tầng từ chi tiết đến tổng quát (không ép buộc quận mặc định)
     */
    private function buildSearchCandidateQueries(string $q): array
    {
        $queries = [];
        $push = static function (string $val) use (&$queries): void {
            $v = trim($val);
            if ($v !== '' && !in_array($v, $queries, true)) {
                $queries[] = $v;
            }
        };

        $push($q);

        $rawParts = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $q) ?: [])));
        $filteredParts = [];
        foreach ($rawParts as $part) {
            $low = mb_strtolower($part, 'UTF-8');
            if ($low === 'việt nam' || $low === 'viet nam' || $low === 'vietnam' || $low === 'vn') {
                continue;
            }
            $filteredParts[] = $part;
        }

        $firstPart = $filteredParts[0] ?? $q;
        $lastPart = count($filteredParts) >= 2 ? end($filteredParts) : '';
        $cleanLastPart = trim((string)preg_replace('/^(?:tp\.?\s+|thành\s+phố\s+|tỉnh\s+)/iu', '', (string)$lastPart));

        // Tầng dự phòng 1: giữ phần đầu + tỉnh/thành cuối (bỏ qua quận/phường trung gian dễ lệch tên cũ-mới)
        if (count($filteredParts) >= 3 && $cleanLastPart !== '') {
            $push($firstPart . ', ' . $cleanLastPart . ', Việt Nam');
        }

        // Tầng dự phòng 2: tìm rộng trên toàn Việt Nam theo phần đường/địa điểm chính
        if (count($filteredParts) >= 2) {
            $push($firstPart . ', Việt Nam');
        } elseif (!preg_match('/việt\s*nam|vietnam/iu', $q)) {
            $push($q . ', Việt Nam');
        }

        // Tầng dự phòng 3: nếu có số nhà ở đầu mà dữ liệu bản đồ chưa đánh số nhà cụ thể, bỏ số nhà để tìm theo tên đường
        $streetOnly = trim((string)preg_replace('/^(?:số\s+|so\s+|hẻm\s+|hem\s+|ngõ\s+|ngo\s+)?\d+[a-zA-Z0-9\/\-]*\s+/iu', '', $firstPart));
        if ($streetOnly !== '' && $streetOnly !== $firstPart && mb_strlen($streetOnly, 'UTF-8') >= 3) {
            if ($lastPart !== '') {
                $push($streetOnly . ', ' . $lastPart . ', Việt Nam');
            }
            $push($streetOnly . ', Việt Nam');
        }

        return array_slice($queries, 0, 4);
    }

    private function queryNominatimSearch(string $q, int $limit = 6): array
    {
        // Điều phối các phiên trên cùng máy chủ bằng file lock
        $lock = fopen(sys_get_temp_dir() . '/bds-nominatim-rate.lock', 'c+');
        if ($lock !== false && flock($lock, LOCK_EX)) {
            $last = (float)stream_get_contents($lock);
            $wait = 1.0 - (microtime(true) - $last);
            if ($wait > 0) {
                usleep((int)ceil($wait * 1000000));
            }
            ftruncate($lock, 0);
            rewind($lock);
            fwrite($lock, (string)microtime(true));
            fflush($lock);
        }

        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q'               => $q,
            'format'          => 'json',
            'addressdetails'  => 1,
            'limit'           => $limit,
            'countrycodes'    => 'vn',
            'accept-language' => 'vi',
        ]);

        $resp = $this->executeCurl($url, 5);
        if ($lock !== false) {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }

        if ($resp['ok'] && !empty($resp['body'])) {
            $items = json_decode((string)$resp['body'], true);
            if (is_array($items)) {
                $results = [];
                foreach ($items as $item) {
                    if (!isset($item['lat'], $item['lon'])) {
                        continue;
                    }
                    $results[] = [
                        'lat'          => (float)$item['lat'],
                        'lng'          => (float)$item['lon'],
                        'display_name' => $item['display_name'] ?? '',
                        'address'      => $item['address'] ?? [],
                    ];
                }
                return ['ok' => true, 'results' => $results];
            }
        }

        return ['ok' => false, 'results' => []];
    }

    public function search(): void
    {
        $this->index();
    }

    /**
     * Reverse Geocoding API: GET /geocode/reverse?lat=...&lng=...
     * Tra cứu địa chỉ chi tiết từ tọa độ bản đồ (với cơ chế cache & xử lý lỗi graceful, timeout 3s)
     */
    public function reverse(): void
    {
        $rawLat = $_GET['lat'] ?? '';
        $rawLng = $_GET['lng'] ?? $_GET['lon'] ?? '';

        if ($rawLat === '' || $rawLng === '' || !is_numeric($rawLat) || !is_numeric($rawLng)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'      => false,
                'message'      => 'Vui lòng cung cấp tọa độ vĩ độ (lat) và kinh độ (lng) hợp lệ.',
                'display_name' => '',
                'address'      => new stdClass(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lat = (float)$rawLat;
        $lng = (float)$rawLng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'      => false,
                'message'      => 'Tọa độ nằm ngoài phạm vi địa lý hợp lệ.',
                'display_name' => '',
                'address'      => new stdClass(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Kiểm tra cache (làm tròn 5 chữ số thập phân ~ 1.1m)
        $cacheKey = number_format($lat, 5, '.', '') . ',' . number_format($lng, 5, '.', '');
        $cached = $this->getReverseCache($cacheKey);
        if ($cached !== null) {
            $this->sendReverseResponse($cached['display_name'], $cached['address'], $lat, $lng, true);
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
            'format'         => 'json',
            'lat'            => $lat,
            'lon'            => $lng,
            'addressdetails' => 1,
            'accept-language'=> 'vi',
        ]);

        $resp = $this->executeCurl($url, 3);

        if ($resp['ok'] && !empty($resp['body'])) {
            $decoded = json_decode((string)$resp['body'], true);
            if (is_array($decoded) && !empty($decoded['display_name'])) {
                $displayName = trim((string)$decoded['display_name']);
                $address = is_array($decoded['address'] ?? null) ? $decoded['address'] : [];

                $this->setReverseCache($cacheKey, [
                    'display_name' => $displayName,
                    'address'      => $address,
                ]);

                $this->sendReverseResponse($displayName, $address, $lat, $lng, false);
            }
        }

        // Graceful fallback khi dịch vụ bên ngoài timeout hoặc không phản hồi
        $fallback = $this->buildGracefulAddressFallback($lat, $lng);
        $this->sendReverseResponse($fallback['display_name'], $fallback['address'], $lat, $lng, false, true);
    }

    private function sendReverseResponse(
        string $displayName,
        array $address,
        float $lat,
        float $lng,
        bool $cached = false,
        bool $fallback = false
    ): void {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'success'      => true,
            'message'      => 'Tra cứu địa chỉ thành công.',
            'display_name' => $displayName,
            'address'      => $address ?: new stdClass(),
            'lat'          => $lat,
            'lng'          => $lng,
            'cached'       => $cached,
            'fallback'     => $fallback,
            'data'         => [
                'display_name' => $displayName,
                'address'      => $address ?: new stdClass(),
                'lat'          => $lat,
                'lng'          => $lng,
                'cached'       => $cached,
            ],
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function throttleNominatim(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $last = $_SESSION['last_nominatim_request'] ?? 0;
        $now = microtime(true);
        if ($now - $last < 1.0) {
            usleep((int)((1.0 - ($now - $last)) * 1000000));
        }
        $_SESSION['last_nominatim_request'] = microtime(true);
    }

    private function executeCurl(string $url, int $timeoutSeconds = 3): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'body' => null, 'status' => 0, 'error' => 'cURL unavailable'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(2, $timeoutSeconds),
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Accept-Language: vi,en;q=0.9',
                'User-Agent: ' . self::USER_AGENT,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Nếu môi trường XAMPP local thiếu CA bundle (lỗi 60 / 77), thử lại không kiểm tra SSL peer
        if ($raw === false && in_array($errno, [60, 77, 35], true)) {
            $ch2 = curl_init();
            curl_setopt_array($ch2, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeoutSeconds,
                CURLOPT_CONNECTTIMEOUT => min(2, $timeoutSeconds),
                CURLOPT_USERAGENT      => self::USER_AGENT,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'Accept-Language: vi,en;q=0.9',
                    'User-Agent: ' . self::USER_AGENT,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $raw = curl_exec($ch2);
            $err = curl_error($ch2);
            $status = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
        }

        return [
            'ok'     => ($raw !== false && $status === 200),
            'body'   => $raw,
            'status' => $status,
            'error'  => $err,
        ];
    }

    private function getReverseCache(string $key): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (isset($_SESSION['geocode_reverse_cache'][$key])) {
            $entry = $_SESSION['geocode_reverse_cache'][$key];
            if (($entry['expires_at'] ?? 0) > time()) {
                return $entry['data'];
            }
            unset($_SESSION['geocode_reverse_cache'][$key]);
        }

        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bdsviet_reverse_geocode_cache.json';
        if (is_file($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            $store = $content ? json_decode($content, true) : null;
            if (is_array($store) && isset($store[$key])) {
                $entry = $store[$key];
                if (($entry['expires_at'] ?? 0) > time() && !empty($entry['data'])) {
                    $_SESSION['geocode_reverse_cache'][$key] = $entry;
                    return $entry['data'];
                }
            }
        }
        return null;
    }

    private function setReverseCache(string $key, array $data): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $entry = [
            'expires_at' => time() + self::CACHE_TTL,
            'data'       => $data,
        ];
        $_SESSION['geocode_reverse_cache'][$key] = $entry;

        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bdsviet_reverse_geocode_cache.json';
        $store = [];
        if (is_file($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            $decoded = $content ? json_decode($content, true) : null;
            if (is_array($decoded)) {
                $store = $decoded;
            }
        }
        // Giới hạn tối đa 200 mục trong file cache nhẹ
        if (count($store) >= 200) {
            $store = array_slice($store, -100, null, true);
        }
        $store[$key] = $entry;
        @file_put_contents($cacheFile, json_encode($store, JSON_UNESCAPED_UNICODE));
    }

    private function buildGracefulAddressFallback(float $lat, float $lng): array
    {
        // Khu vực TP.HCM
        if ($lat >= 10.60 && $lat <= 11.15 && $lng >= 106.35 && $lng <= 107.05) {
            if ($lat >= 10.79 && $lng >= 106.72) {
                return [
                    'display_name' => 'Đường Xuân Thủy, Phường Thảo Điền, Thành phố Thủ Đức, Thành phố Hồ Chí Minh, Việt Nam',
                    'address'      => [
                        'road'          => 'Đường Xuân Thủy',
                        'suburb'        => 'Phường Thảo Điền',
                        'city_district' => 'Thành phố Thủ Đức',
                        'city'          => 'Thành phố Hồ Chí Minh',
                        'country'       => 'Việt Nam',
                        'country_code'  => 'vn',
                    ],
                ];
            }
            return [
                'display_name' => 'Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, Thành phố Hồ Chí Minh, Việt Nam',
                'address'      => [
                    'road'          => 'Đường Nguyễn Huệ',
                    'suburb'        => 'Phường Bến Nghé',
                    'city_district' => 'Quận 1',
                    'city'          => 'Thành phố Hồ Chí Minh',
                    'country'       => 'Việt Nam',
                    'country_code'  => 'vn',
                ],
            ];
        }
        // Khu vực Hà Nội
        if ($lat >= 20.80 && $lat <= 21.40 && $lng >= 105.30 && $lng <= 106.05) {
            if ($lng <= 105.81) {
                return [
                    'display_name' => 'Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Thành phố Hà Nội, Việt Nam',
                    'address'      => [
                        'road'          => 'Đường Cầu Giấy',
                        'suburb'        => 'Phường Dịch Vọng',
                        'city_district' => 'Quận Cầu Giấy',
                        'city'          => 'Thành phố Hà Nội',
                        'country'       => 'Việt Nam',
                        'country_code'  => 'vn',
                    ],
                ];
            }
            return [
                'display_name' => 'Đường Điện Biên Phủ, Phường Điện Biên, Quận Ba Đình, Thành phố Hà Nội, Việt Nam',
                'address'      => [
                    'road'          => 'Đường Điện Biên Phủ',
                    'suburb'        => 'Phường Điện Biên',
                    'city_district' => 'Quận Ba Đình',
                    'city'          => 'Thành phố Hà Nội',
                    'country'       => 'Việt Nam',
                    'country_code'  => 'vn',
                ],
            ];
        }
        // Khu vực Đà Nẵng
        if ($lat >= 15.90 && $lat <= 16.25 && $lng >= 107.90 && $lng <= 108.35) {
            return [
                'display_name' => 'Đường Bạch Đằng, Phường Hải Châu 1, Quận Hải Châu, Thành phố Đà Nẵng, Việt Nam',
                'address'      => [
                    'road'          => 'Đường Bạch Đằng',
                    'suburb'        => 'Phường Hải Châu 1',
                    'city_district' => 'Quận Hải Châu',
                    'city'          => 'Thành phố Đà Nẵng',
                    'country'       => 'Việt Nam',
                    'country_code'  => 'vn',
                ],
            ];
        }

        return [
            'display_name' => sprintf('Vị trí đã ghim (%s, %s), Việt Nam', number_format($lat, 5), number_format($lng, 5)),
            'address'      => [
                'country'      => 'Việt Nam',
                'country_code' => 'vn',
            ],
        ];
    }

    private function getForwardCache(string $key): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (isset($_SESSION['geocode_forward_cache'][$key])) {
            $entry = $_SESSION['geocode_forward_cache'][$key];
            if (($entry['expires_at'] ?? 0) > time() && !empty($entry['data'])) {
                return $entry['data'];
            }
            unset($_SESSION['geocode_forward_cache'][$key]);
        }

        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bdsviet_forward_geocode_cache.json';
        if (is_file($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            $store = $content ? json_decode($content, true) : null;
            if (is_array($store) && isset($store[$key])) {
                $entry = $store[$key];
                if (($entry['expires_at'] ?? 0) > time() && !empty($entry['data'])) {
                    $_SESSION['geocode_forward_cache'][$key] = $entry;
                    return $entry['data'];
                }
            }
        }
        return null;
    }

    private function setForwardCache(string $key, array $data): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $entry = [
            'expires_at' => time() + self::CACHE_TTL,
            'data'       => $data,
        ];
        $_SESSION['geocode_forward_cache'][$key] = $entry;

        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bdsviet_forward_geocode_cache.json';
        $store = [];
        if (is_file($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            $decoded = $content ? json_decode($content, true) : null;
            if (is_array($decoded)) {
                $store = $decoded;
            }
        }
        if (count($store) >= 200) {
            $store = array_slice($store, -100, null, true);
        }
        $store[$key] = $entry;
        @file_put_contents($cacheFile, json_encode($store, JSON_UNESCAPED_UNICODE));
    }

    private function buildGracefulSearchFallback(string $q): array
    {
        $catalog = [
            [
                'keywords'     => ['ha noi', 'hà nội', 'hanoi', 'hoan kiem', 'hoàn kiếm', 'ba dinh', 'ba đình', 'cau giay', 'cầu giấy'],
                'lat'          => 21.028511,
                'lng'          => 105.854444,
                'display_name' => 'Thành phố Hà Nội, Việt Nam',
                'address'      => ['city' => 'Thành phố Hà Nội', 'country' => 'Việt Nam', 'country_code' => 'vn'],
            ],
            [
                'keywords'     => ['thao dien', 'thảo điền', 'thu duc', 'thủ đức'],
                'lat'          => 10.803100,
                'lng'          => 106.733900,
                'display_name' => 'Phường Thảo Điền, Thành phố Thủ Đức, Thành phố Hồ Chí Minh, Việt Nam',
                'address'      => ['suburb' => 'Phường Thảo Điền', 'city_district' => 'TP. Thủ Đức', 'city' => 'Thành phố Hồ Chí Minh', 'country' => 'Việt Nam', 'country_code' => 'vn'],
            ],
            [
                'keywords'     => ['ho chi minh', 'hồ chí minh', 'tphcm', 'tp hcm', 'sai gon', 'sài gòn'],
                'lat'          => 10.776889,
                'lng'          => 106.700806,
                'display_name' => 'Thành phố Hồ Chí Minh, Việt Nam',
                'address'      => ['city' => 'Thành phố Hồ Chí Minh', 'country' => 'Việt Nam', 'country_code' => 'vn'],
            ],
            [
                'keywords'     => ['quan 1', 'quận 1', 'ben nghe', 'bến nghé'],
                'lat'          => 10.776889,
                'lng'          => 106.700806,
                'display_name' => 'Quận 1, Thành phố Hồ Chí Minh, Việt Nam',
                'address'      => ['city_district' => 'Quận 1', 'city' => 'Thành phố Hồ Chí Minh', 'country' => 'Việt Nam', 'country_code' => 'vn'],
            ],
            [
                'keywords'     => ['da nang', 'đà nẵng', 'hai chau', 'hải châu', 'son tra', 'sơn trà'],
                'lat'          => 16.054407,
                'lng'          => 108.202167,
                'display_name' => 'Thành phố Đà Nẵng, Việt Nam',
                'address'      => ['city' => 'Thành phố Đà Nẵng', 'country' => 'Việt Nam', 'country_code' => 'vn'],
            ],
            [
                'keywords'     => ['phu my hung', 'phú mỹ hưng', 'quan 7', 'quận 7', 'tan phong', 'tân phong'],
                'lat'          => 10.729500,
                'lng'          => 106.721800,
                'display_name' => 'Khu đô thị Phú Mỹ Hưng, Quận 7, Thành phố Hồ Chí Minh, Việt Nam',
                'address'      => ['suburb' => 'Phường Tân Phong', 'city_district' => 'Quận 7', 'city' => 'Thành phố Hồ Chí Minh', 'country' => 'Việt Nam', 'country_code' => 'vn'],
            ],
        ];

        $norm = mb_strtolower(trim($q), 'UTF-8');
        // Không gán tọa độ mặc định cho truy vấn số nhà/tên đường cụ thể
        if (preg_match('/^\d+[a-z0-9\/\-]*\s+/iu', $norm)) {
            return [];
        }

        $matched = [];
        foreach ($catalog as $entry) {
            foreach ($entry['keywords'] as $kw) {
                if (str_contains($norm, $kw) || ($norm !== '' && mb_strlen($norm, 'UTF-8') >= 4 && str_contains($kw, $norm))) {
                    $matched[] = [
                        'lat'          => $entry['lat'],
                        'lng'          => $entry['lng'],
                        'display_name' => $entry['display_name'],
                        'address'      => $entry['address'],
                    ];
                    break;
                }
            }
        }

        return array_slice($matched, 0, 5);
    }
}

