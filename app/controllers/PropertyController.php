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
        // TODO [C]: lấy chi tiết + ảnh + tiện ích + bản đồ + tăng lượt xem
        $this->view('stub', ['title' => 'Chi tiết tin #' . (int)$id, 'owner' => 'C', 'todo' => 'Trang chi tiết: gallery ảnh, thông số, Google Maps, liên hệ môi giới, yêu thích, đánh giá, hẹn xem nhà.']);
    }
}
