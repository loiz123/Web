<?php
// [C] Tìm kiếm & xem chi tiết tin đăng
class PropertyController extends Controller
{
    public function search(): void
    {
        $filters = array_intersect_key($_GET, array_flip(['keyword', 'listing_type', 'province_id', 'district_id', 'min_price', 'max_price']));
        $location = new Location();
        if (!empty($filters['district_id']) && empty($filters['province_id'])) {
            $filters['province_id'] = $location->provinceByDistrict((int)$filters['district_id']);
        }

        $this->view('property/search', [
            'title'      => 'Tìm kiếm',
            'filters'    => $filters,
            'properties' => (new PropertyFinder())->search($filters, 12, 0),
            'provinces'  => $location->provinces(),
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
        $isFavorited = Auth::check() ? (new Favorite())->isFavorited((int)Auth::id(), $id) : false;

        // Đánh giá và báo cáo của tin đăng [C - Thiện & D - Quốc]
        $reviewModel = new Review();
        $reviews = $reviewModel->forProperty($id);
        $reviewStats = $reviewModel->getStats($id);
        $userReview = Auth::check() ? $reviewModel->getUserReview($id, (int)Auth::id()) : null;
        $hasReported = Auth::check() ? (new Report())->hasOpenReport($id, (int)Auth::id()) : false;

        $this->view('property/detail', [
            'title'       => $property['title'],
            'property'    => $property,
            'images'      => $images,
            'amenities'   => $amenities,
            'similar'     => $similar,
            'isFavorited' => $isFavorited,
            'reviews'     => $reviews,
            'reviewStats' => $reviewStats,
            'userReview'  => $userReview,
            'hasReported' => $hasReported,
        ]);
    }

    public function mapTest(): void
    {
        $this->view('property/map_test', [
            'title' => 'Kiểm tra bản đồ Leaflet & OpenStreetMap',
        ]);
    }
}
