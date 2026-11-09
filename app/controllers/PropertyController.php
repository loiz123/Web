<?php
// [C - Thiện] Tìm kiếm nâng cao, AJAX phân trang/lọc, Chi tiết tin, Bản đồ & Lưu bộ lọc
class PropertyController extends Controller
{
    public function search(): void
    {
        $allowedKeys = [
            'keyword', 'listing_type', 'type_id', 'province_id', 'district_id', 'ward_id',
            'min_price', 'max_price', 'price_range', 'min_area', 'max_area', 'area_range',
            'bedrooms', 'sort_by', 'page'
        ];
        $filters = array_intersect_key($_GET, array_flip($allowedKeys));

        $location = new Location();
        if (!empty($filters['district_id']) && empty($filters['province_id'])) {
            $filters['province_id'] = $location->provinceByDistrict((int)$filters['district_id']);
        }

        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $finder = new PropertyFinder();
        $total = $finder->countSearch($filters);
        $properties = $finder->search($filters, $limit, $offset);
        $totalPages = max(1, (int)ceil($total / $limit));

        // Trả kết quả JSON khi gọi bằng AJAX
        if ($this->wantsJson()) {
            ob_start();
            if (empty($properties)) {
                echo '<div class="col-12 py-5 text-center text-muted empty-state-box">';
                echo '  <div class="fs-1 mb-2">🔍</div>';
                echo '  <h5 class="fw-semibold text-dark">Không tìm thấy bất động sản phù hợp</h5>';
                echo '  <p class="small text-muted mb-3">Hãy thử nới rộng khoảng giá, diện tích hoặc đặt lại bộ lọc để xem nhiều kết quả hơn.</p>';
                echo '  <button type="button" class="btn btn-outline-primary btn-sm btn-reset-filters">Xóa bộ lọc & Đặt lại</button>';
                echo '</div>';
            } else {
                foreach ($properties as $p) {
                    require APP . '/views/partials/property_card.php';
                }
            }
            $html = ob_get_clean();

            ob_start();
            $this->renderPagination($page, $totalPages, $total, $limit);
            $paginationHtml = ob_get_clean();

            $mapProperties = array_map(function ($p) {
                return [
                    'id'              => (int)$p['id'],
                    'title'           => $p['title'],
                    'price'           => (float)$p['price'],
                    'formatted_price' => format_price($p['price']),
                    'area'            => (float)$p['area'],
                    'bedrooms'        => (int)$p['bedrooms'],
                    'listing_type'    => $p['listing_type'],
                    'type_name'       => $p['type_name'] ?? '',
                    'latitude'        => !empty($p['latitude']) ? (float)$p['latitude'] : null,
                    'longitude'       => !empty($p['longitude']) ? (float)$p['longitude'] : null,
                    'district_name'   => $p['district_name'] ?? '',
                    'ward_name'       => $p['ward_name'] ?? '',
                    'cover_url'       => !empty($p['cover_url']) ? image_url($p['cover_url']) : '',
                    'detail_url'      => base_url('property/detail/' . $p['id']),
                ];
            }, $properties);

            $this->json([
                'total'          => $total,
                'page'           => $page,
                'totalPages'     => $totalPages,
                'limit'          => $limit,
                'count'          => count($properties),
                'html'           => $html,
                'paginationHtml' => $paginationHtml,
                'properties'     => $mapProperties,
            ], true);
            return;
        }

        $propertyTypes = (new PropertyType())->allSorted();
        $districts = !empty($filters['province_id']) ? $location->districtsByProvince((int)$filters['province_id']) : [];
        $wards = !empty($filters['district_id']) ? $location->wardsByDistrict((int)$filters['district_id']) : [];
        $savedSearches = Auth::check() ? (new SavedSearch())->getByUser((int)Auth::id()) : [];

        $this->view('property/search', [
            'title'         => 'Tìm kiếm bất động sản',
            'filters'       => $filters,
            'properties'    => $properties,
            'total'         => $total,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'limit'         => $limit,
            'provinces'     => $location->provinces(),
            'districts'     => $districts,
            'wards'         => $wards,
            'propertyTypes' => $propertyTypes,
            'savedSearches' => $savedSearches,
        ]);
    }

    public function detail($id = 0): void
    {
        $id = (int)$id;
        $finder = new PropertyFinder();
        $property = $finder->findDetail($id);

        if (!$property) {
            flash('danger', 'Không tìm thấy bất động sản yêu cầu hoặc tin đã bị gỡ.');
            $this->redirect('property/search');
            return;
        }

        $isOwner = Auth::check() && (int)$property['user_id'] === (int)Auth::id();
        $canModerate = Auth::hasRole('admin');
        if ($property['status'] !== 'approved' && !$isOwner && !$canModerate) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Không tìm thấy']);
            return;
        }

        // Tăng lượt xem tin
        $finder->incrementViews($id);
        $property['views'] = ((int)$property['views']) + 1;

        $images = $finder->getImages($id);
        $amenities = $finder->getAmenities($id);
        $similar = $finder->getSimilar($id, (int)$property['type_id'], 3);
        $propertyModel = new Property();
        $similarProperties = $propertyModel->getSimilarProperties(
            (int)$id,
            (int)($property['district_id'] ?? 0),
            (int)($property['type_id'] ?? 0),
            (float)($property['price'] ?? 0),
            4
        );
        $isFavorited = Auth::check() ? (new Favorite())->isFavorited((int)Auth::id(), $id) : false;

        // Đánh giá và báo cáo của tin đăng [C - Thiện & D - Quốc]
        $reviewModel = new Review();
        $reviews = $reviewModel->forProperty($id);
        $reviewStats = $reviewModel->getStats($id);
        $userReview = Auth::check() ? $reviewModel->getUserReview($id, (int)Auth::id()) : null;
        $hasReported = Auth::check() ? (new Report())->hasOpenReport($id, (int)Auth::id()) : false;

        $this->view('property/detail', [
            'title'             => $property['title'],
            'property'          => $property,
            'images'            => $images,
            'amenities'         => $amenities,
            'similar'           => $similar,
            'similarProperties' => $similarProperties,
            'isFavorited'       => $isFavorited,
            'reviews'           => $reviews,
            'reviewStats'       => $reviewStats,
            'userReview'        => $userReview,
            'hasReported'       => $hasReported,
        ]);
    }

    /**
     * POST /property/saveSearch - Lưu bộ lọc tìm kiếm hiện tại vào DB
     */
    public function saveSearch(): void
    {
        if (!Auth::check()) {
            $this->json(['need_login' => true], false, 'Vui lòng đăng nhập để lưu bộ lọc.', 401);
            return;
        }
        if (!$this->isPost()) {
            $this->json(null, false, 'Phương thức không được hỗ trợ.', 405);
            return;
        }
        $this->verifyCsrf();

        $name = trim($_POST['name'] ?? '');
        $filters = $_POST['filters'] ?? [];
        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?: [];
        }

        // Loại bỏ CSRF hay token khỏi filters
        unset($filters['_csrf'], $filters['ajax']);

        $savedSearchModel = new SavedSearch();
        $id = $savedSearchModel->saveSearch((int)Auth::id(), $name, $filters);

        $this->json([
            'id'   => $id,
            'name' => $name ?: 'Bộ lọc ' . date('d/m/Y H:i'),
        ], true, 'Đã lưu bộ lọc tìm kiếm thành công!');
    }

    /**
     * POST /property/deleteSavedSearch/{id} - Xóa bộ lọc đã lưu
     */
    public function deleteSavedSearch($id = 0): void
    {
        if (!Auth::check()) {
            $this->json(['need_login' => true], false, 'Vui lòng đăng nhập.', 401);
            return;
        }
        if (!$this->isPost()) {
            $this->json(null, false, 'Phương thức không được hỗ trợ.', 405);
            return;
        }
        $this->verifyCsrf();

        $id = (int)$id;
        $ok = (new SavedSearch())->deleteOwned($id, (int)Auth::id());
        if ($ok) {
            $this->json(['id' => $id], true, 'Đã xóa bộ lọc tìm kiếm.');
        } else {
            $this->json(null, false, 'Không tìm thấy bộ lọc hoặc không có quyền thao tác.', 404);
        }
    }

    /**
     * GET /property/savedSearches - Lấy danh sách bộ lọc đã lưu của user (JSON)
     */
    public function savedSearches(): void
    {
        if (!Auth::check()) {
            $this->json([], false, 'Vui lòng đăng nhập.', 401);
            return;
        }
        $list = (new SavedSearch())->getByUser((int)Auth::id());
        $this->json($list, true);
    }

    public function mapTest(): void
    {
        $this->view('property/map_test', [
            'title' => 'Kiểm tra bản đồ Leaflet & OpenStreetMap',
        ]);
    }

    /**
     * Trang so sánh bất động sản đối đầu (Side-by-side Comparison) [C - Thiện]
     * Hỗ trợ nhận danh sách ID qua query: /property/compare?ids=1,2,3
     */
    public function compare(): void
    {
        $rawIds = $_GET['ids'] ?? '';
        $idList = [];

        if (is_array($rawIds)) {
            $idList = array_map('intval', $rawIds);
        } elseif (is_string($rawIds) && trim($rawIds) !== '') {
            $parts = explode(',', $rawIds);
            foreach ($parts as $p) {
                $id = (int)trim($p);
                if ($id > 0 && !in_array($id, $idList, true)) {
                    $idList[] = $id;
                }
            }
        }

        // Tối đa 4 tin so sánh đối đầu
        $idList = array_slice($idList, 0, 4);

        $properties = [];
        $allAmenitiesMap = [];

        if (!empty($idList)) {
            $finder = new PropertyFinder();
            foreach ($idList as $id) {
                $prop = $finder->findDetail($id);
                if ($prop) {
                    $amenities = $finder->getAmenities($id);
                    $prop['amenities'] = $amenities;
                    $properties[] = $prop;

                    foreach ($amenities as $am) {
                        $allAmenitiesMap[$am['id']] = $am['name'];
                    }
                }
            }
        }

        $this->view('property/compare', [
            'title' => 'So sánh bất động sản',
            'properties' => $properties,
            'allAmenitiesMap' => $allAmenitiesMap,
            'idList' => $idList,
        ]);
    }

    /**
     * API JSON lấy dữ liệu chi tiết các tin BĐS phục vụ modal / so sánh nhanh [C - Thiện]
     */
    public function compareData(): void
    {
        $rawIds = $_GET['ids'] ?? '';
        $idList = [];

        if (is_array($rawIds)) {
            $idList = array_map('intval', $rawIds);
        } elseif (is_string($rawIds) && trim($rawIds) !== '') {
            $parts = explode(',', $rawIds);
            foreach ($parts as $p) {
                $id = (int)trim($p);
                if ($id > 0 && !in_array($id, $idList, true)) {
                    $idList[] = $id;
                }
            }
        }

        $idList = array_slice($idList, 0, 4);
        $result = [];

        if (!empty($idList)) {
            $finder = new PropertyFinder();
            foreach ($idList as $id) {
                $prop = $finder->findDetail($id);
                if ($prop) {
                    $prop['amenities'] = $finder->getAmenities($id);
                    $result[] = $prop;
                }
            }
        }

        $this->json($result, true);
    }

    /**
     * Hiển thị HTML thanh phân trang Bootstrap 5
     */
    public function renderPagination(int $page, int $totalPages, int $total, int $limit): void
    {
        if ($totalPages <= 1) return;

        $from = ($page - 1) * $limit + 1;
        $to = min($total, $page * $limit);

        echo '<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 my-4">';
        echo '  <div class="text-muted small">';
        echo '    Hiển thị <strong>' . $from . ' - ' . $to . '</strong> trên tổng số <strong>' . $total . '</strong> bất động sản';
        echo '  </div>';
        echo '  <nav aria-label="Phân trang kết quả">';
        echo '    <ul class="pagination pagination-sm mb-0">';

        // Nút Trước
        $prevDisabled = ($page <= 1) ? ' disabled' : '';
        echo '<li class="page-item' . $prevDisabled . '">';
        echo '  <a class="page-link" href="#" data-page="' . ($page - 1) . '" aria-label="Trước">&laquo;</a>';
        echo '</li>';

        // Danh sách số trang (thu gọn với dấu ... nếu > 7 trang)
        $range = 2;
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);

        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
            if ($start > 2) {
                echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = ($i === $page) ? ' active' : '';
            echo '<li class="page-item' . $active . '">';
            echo '  <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>';
            echo '</li>';
        }

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="#" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
        }

        // Nút Sau
        $nextDisabled = ($page >= $totalPages) ? ' disabled' : '';
        echo '<li class="page-item' . $nextDisabled . '">';
        echo '  <a class="page-link" href="#" data-page="' . ($page + 1) . '" aria-label="Sau">&raquo;</a>';
        echo '</li>';

        echo '    </ul>';
        echo '  </nav>';
        echo '</div>';
    }
}
