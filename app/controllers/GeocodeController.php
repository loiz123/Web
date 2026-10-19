<?php
// [C] Geocoding qua OpenStreetMap (Nominatim) - Hà Ngọc Thiện phụ trách
class GeocodeController extends Controller
{
    public function index(): void
    {
        $q = trim($_GET['q'] ?? $_GET['address'] ?? '');
        if ($q === '') {
            $this->json(null, false, 'Vui lòng cung cấp địa chỉ cần tìm.', 400);
        }

        // Chính sách sử dụng Nominatim: tối đa 1 request / giây
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $last = $_SESSION['last_nominatim_request'] ?? 0;
        $now = microtime(true);
        if ($now - $last < 1.0) {
            usleep((int)((1.0 - ($now - $last)) * 1000000));
        }
        $_SESSION['last_nominatim_request'] = microtime(true);

        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q'              => $q,
            'format'         => 'json',
            'addressdetails' => 1,
            'limit'          => 5,
            'countrycodes'   => 'vn',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_USERAGENT      => 'BdsVietApp/1.0 (ueh_project_demo; contact: dev@bdsviet.local)',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Accept-Language: vi,en;q=0.9',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status !== 200) {
            $this->json(null, false, 'Không thể kết nối đến dịch vụ bản đồ OpenStreetMap (' . ($err ?: "HTTP $status") . ').', 502);
        }

        $items = json_decode($raw, true);
        if (!is_array($items) || empty($items)) {
            $this->json([], false, 'Không tìm thấy tọa độ phù hợp cho địa chỉ này. Bạn có thể bấm chọn trực tiếp trên bản đồ.');
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'lat'          => (float)$item['lat'],
                'lng'          => (float)$item['lon'],
                'display_name' => $item['display_name'] ?? '',
            ];
        }

        $this->json($results, true, 'Tìm thấy ' . count($results) . ' kết quả phù hợp.');
    }

    public function search(): void
    {
        $this->index();
    }
}
