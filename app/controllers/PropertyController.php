<?php
// [C] Tìm kiếm & xem chi tiết tin đăng
class PropertyController extends Controller
{
    public function search(): void
    {
        $filters = array_intersect_key($_GET, array_flip(['keyword', 'listing_type', 'district_id', 'min_price', 'max_price']));
        $this->view('property/search', [
            'title'      => 'Tìm kiếm',
            'filters'    => $filters,
            'properties' => (new PropertyFinder())->search($filters, 12, 0),
            'provinces'  => (new Location())->provinces(),
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

        // Tăng lượt xem tin
        $finder->incrementViews($id);
        $property['views'] = ((int)$property['views']) + 1;

        $images = $finder->getImages($id);
        $amenities = $finder->getAmenities($id);
        $similar = $finder->getSimilar($id, (int)$property['type_id'], 3);

        $this->view('property/detail', [
            'title'     => $property['title'],
            'property'  => $property,
            'images'    => $images,
            'amenities' => $amenities,
            'similar'   => $similar,
        ]);
    }

    public function mapTest(): void
    {
        $this->view('property/map_test', [
            'title' => 'Kiểm tra bản đồ Leaflet & OpenStreetMap',
        ]);
    }
}
