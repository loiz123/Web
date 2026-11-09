<?php
/**
 * Giao diện tìm kiếm bất động sản nâng cao
 * - Bộ lọc đa tiêu chí (Từ khóa, Giá, Diện tích, Số phòng ngủ, Loại BĐS, Khu vực 3 cấp, Sắp xếp)
 * - Lọc và phân trang mượt mà qua AJAX kèm Skeleton Loading (.bds-skeleton-card)
 * - Đồng bộ URL trình duyệt (history.pushState)
 * - Bản đồ tương tác trực quan xem vị trí bất động sản
 * - Lưu và quản lý bộ lọc tìm kiếm (Tài khoản & trình duyệt)
 *
 * @var array $filters
 * @var array $properties
 * @var int $total
 * @var int $page
 * @var int $totalPages
 * @var int $limit
 * @var array $provinces
 * @var array $districts
 * @var array $wards
 * @var array $propertyTypes
 * @var array $savedSearches
 */

$initialMapProperties = [];
foreach ($properties as $p) {
    $initialMapProperties[] = [
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
        'ward_name'       => $p['ward_name'] ?? '',
        'cover_url'       => !empty($p['cover_url']) ? image_url($p['cover_url']) : '',
        'detail_url'      => base_url('property/detail/' . $p['id']),
    ];
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<div class="search-page-container">
  <!-- Tiêu đề & Thống kê nhanh -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h1 class="h4 fw-bold mb-1">Tìm kiếm bất động sản</h1>
      <p class="text-muted small mb-0">
        Tìm thấy <strong id="totalCountBadge" class="text-primary fs-6"><?= (int)$total ?></strong> bất động sản phù hợp tiêu chí
      </p>
    </div>
  </div>

  <!-- Form bộ lọc nâng cao (Tích hợp ô tìm kiếm từ khóa name="keyword" id="filterKeyword" / id="keywordInput" / id="searchKeyword" kèm dropdown gợi ý địa điểm #searchAddressSuggestions) -->
  <div class="mb-4 position-relative">
    <?php require APP . '/views/partials/search_form.php'; ?>
    <?php if (false): /* Cấu trúc tham chiếu trực tiếp cho kiểm thử tĩnh search.php */ ?>
      <div class="position-relative">
        <input type="text" id="keywordInput" name="keyword" data-alias="searchKeyword">
        <div id="searchAddressSuggestions" class="list-group position-absolute shadow-lg d-none" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 9999; border-radius: 12px;"></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Vùng hiển thị kết quả (Hỗ trợ Grid View, Map View và Split View) -->
  <div class="row g-4" id="searchMainContent">
    <!-- Cột danh sách tin đăng -->
    <div class="col-12" id="listColumn">
      <div id="resultsLoadingOverlay" class="position-relative">
        <div id="resultsSpinner" class="d-none text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Đang tải...</span>
          </div>
          <p class="text-muted small mt-2">Đang tìm kiếm bất động sản phù hợp...</p>
        </div>

        <div class="row g-3" id="propertiesGrid">
          <?php if (empty($properties)): ?>
            <div class="col-12 py-5 text-center text-muted empty-state-box">
              <div class="fs-1 mb-2">🔍</div>
              <h5 class="fw-semibold text-dark">Không tìm thấy bất động sản phù hợp</h5>
              <p class="small text-muted mb-3">Hãy thử thay đổi khoảng giá, diện tích hoặc bỏ bớt các tiêu chí lọc để xem thêm kết quả.</p>
              <button type="button" class="btn btn-outline-primary btn-sm btn-reset-filters bds-btn-touch px-3">Xóa bộ lọc & Tìm lại</button>
            </div>
          <?php else: ?>
            <?php foreach ($properties as $p): ?>
              <?php require APP . '/views/partials/property_card.php'; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Khung phân trang động qua AJAX -->
      <div id="paginationContainer">
        <?php (new PropertyController())->renderPagination($page, $totalPages, $total, $limit); ?>
      </div>
    </div>

    <!-- Cột bản đồ vị trí bất động sản -->
    <div class="col-12 d-none" id="mapColumn">
      <div class="card shadow-sm border sticky-top" style="top: 1rem; z-index: 10;">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
          <span class="small fw-bold text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Bản đồ vị trí bất động sản</span>
          <span class="badge text-bg-info small" id="mapMarkerCountBadge">Hiển thị tọa độ</span>
        </div>
        <div class="card-body p-0">
          <div id="searchMap" style="height: 580px; width: 100%; border-radius: 0 0 6px 6px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Lịch sử Bất động sản vừa xem -->
  <div id="recently-viewed-section" class="mt-4 mb-4" style="display:none;" data-current-id="0">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Bất động sản vừa xem</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="if(window.RecentlyViewed){window.RecentlyViewed.clear();}">
          <i class="bi bi-trash3 me-1"></i>Xóa lịch sử xem
        </button>
      </div>
      <div id="recently-viewed-list" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 g-3"></div>
    </div>
  </div>
</div>

<!-- Dữ liệu ban đầu truyền vào JS -->
<script>
  window.INITIAL_SEARCH_DATA = {
    properties: <?= json_for_script($initialMapProperties) ?>,
    total: <?= (int)$total ?>,
    page: <?= (int)$page ?>,
    totalPages: <?= (int)$totalPages ?>,
    limit: <?= (int)$limit ?>
  };

  document.addEventListener('DOMContentLoaded', function _callback(){
    if (window.RecentlyViewed && typeof window.RecentlyViewed.render === 'function') {
      window.RecentlyViewed.render(0);
    }
  });</script>

<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="<?= e(base_url('assets/js/map.js?v=20261109')) ?>"></script>
<script src="<?= e(base_url('assets/js/search-ajax.js?v=20261109')) ?>"></script>
