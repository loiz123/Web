<?php
// [C] Trang chủ
class HomeController extends Controller
{
    public function index(): void
    {
        $allowedKeys = [
            'keyword', 'listing_type', 'type_id', 'province_id', 'district_id', 'ward_id',
            'min_price', 'max_price', 'price_range', 'min_area', 'max_area', 'area_range',
            'bedrooms', 'sort_by'
        ];
        $filters = array_intersect_key($_GET, array_flip($allowedKeys));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 8;
        $offset = ($page - 1) * $limit;

        $finder = new PropertyFinder();
        $total = $finder->countSearch($filters);
        $properties = (!empty($filters) || $page > 1)
            ? $finder->search($filters, $limit, $offset)
            : $finder->latest($limit);
        $totalPages = max(1, (int)ceil($total / $limit));

        $this->view('home/index', [
            'title'      => 'Trang chủ',
            'properties' => $properties,
            'provinces'  => (new Location())->provinces(),
            'filters'    => $filters,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }
}
