<?php
// Tọa độ an toàn phục vụ hiển thị vị trí bất động sản & tiện ích khu vực
$propLat = (float)($property['latitude'] ?? 0);
$propLng = (float)($property['longitude'] ?? 0);
$hasValidCoords = ($propLat >= 8.0 && $propLat <= 24.0 && $propLng >= 102.0 && $propLng <= 110.0);

if (!$hasValidCoords) {
    $pName = mb_strtolower($property['province_name'] ?? '');
    $wName = mb_strtolower($property['ward_name'] ?? '');
    if (str_contains($pName, 'đà nẵng') || str_contains($pName, 'da nang') || str_contains($wName, 'hải châu') || str_contains($wName, 'sơn trà')) {
        $propLat = 16.054400;
        $propLng = 108.202200;
    } elseif (str_contains($pName, 'hà nội') || str_contains($pName, 'ha noi') || str_contains($wName, 'điện biên') || str_contains($wName, 'dịch vọng')) {
        $propLat = 21.028511;
        $propLng = 105.804817;
    } else {
        $propLat = 10.776889;
        $propLng = 106.700806;
    }
}
?>
<!-- Thư viện hiển thị bản đồ tương tác -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<style>
  #propertyMap { height: 450px; min-height: 450px; width: 100%; border-radius: 16px; z-index: 1; background-color: #f8fafc; position: relative; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
  .leaflet-container { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important; }
  .gallery-main { height: 420px; object-fit: cover; border-radius: 8px; width: 100%; }
  .gallery-thumb { height: 80px; object-fit: cover; border-radius: 6px; cursor: pointer; transition: opacity .2s; }
  .gallery-thumb:hover, .gallery-thumb.active { opacity: .75; border: 2px solid #0d6efd; }
  .gallery-open { cursor: zoom-in; }
  .gallery-modal-img { height: min(70vh, 650px); object-fit: contain; width: 100%; }
  @media (max-width: 991.98px) {
    .property-detail-page { padding-bottom: 85px; }
    .property-mobile-actions { padding: 8px 12px calc(8px + env(safe-area-inset-bottom)); }
  }
  .property-spec-item { padding: 12px; background-color: #f8f9fa; border-radius: 8px; }
</style>

<div class="container py-2 property-detail-page">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="<?= e(base_url('property/search')) ?>">Tìm kiếm</a></li>
      <li class="breadcrumb-item"><a href="<?= e(base_url('property/search?ward_id=' . $property['ward_id'])) ?>"><?= e($property['ward_name']) ?></a></li>
      <li class="breadcrumb-item active text-truncate" style="max-width: 320px;" aria-current="page"><?= e($property['title']) ?></li>
    </ol>
  </nav>

  <?php if (!empty($_SESSION['user']) && ((int)($_SESSION['user']['role_id'] ?? 0) === 1 || ($_SESSION['user']['role'] ?? '') === 'admin')): ?>
    <!-- Admin Toolbar (Thanh công cụ Quản trị viên) -->
    <div class="bg-light border rounded-3 p-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center flex-wrap gap-2">
        <span class="d-inline-flex align-items-center">
          <i class="bi bi-shield-check text-primary me-1"></i> <strong>Kiểm duyệt tin đăng</strong> <span class="text-muted small ms-1">#<?= (int)$property['id'] ?></span>
        </span>
        <span class="text-muted d-none d-sm-inline">|</span>
        <span class="small text-muted">
          Trạng thái: 
          <span id="adminCurrentStatus" class="badge <?= $property['status'] === 'approved' ? 'bg-success' : ($property['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
            <?= $property['status'] === 'approved' ? 'Đã duyệt' : ($property['status'] === 'pending' ? 'Chờ duyệt' : 'Đã ẩn') ?>
          </span>
        </span>
        <span class="small text-muted">
          Huy hiệu: 
          <span id="adminCurrentVerify" class="badge <?= !empty($property['is_verified']) ? 'bg-success' : 'bg-secondary' ?>">
            <?= !empty($property['is_verified']) ? 'Đã xác thực' : 'Chưa xác thực' ?>
          </span>
        </span>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Nút Toggle Xác thực -->
        <?php if (!empty($property['is_verified'])): ?>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAdminVerify" data-id="<?= (int)$property['id'] ?>" data-url="<?= e(base_url('admin/properties/verify/' . (int)$property['id'])) ?>">
            <i class="bi bi-patch-minus"></i> Hủy xác thực
          </button>
        <?php else: ?>
          <button type="button" class="btn btn-sm btn-outline-success" id="btnAdminVerify" data-id="<?= (int)$property['id'] ?>" data-url="<?= e(base_url('admin/properties/verify/' . (int)$property['id'])) ?>">
            <i class="bi bi-patch-check"></i> Cấp xác thực
          </button>
        <?php endif; ?>

        <!-- Nút Duyệt tin (khi chưa được duyệt) -->
        <?php if ($property['status'] !== 'approved'): ?>
          <button type="button" class="btn btn-sm btn-outline-success btn-admin-status" id="btnAdminApprove" data-id="<?= (int)$property['id'] ?>" data-status="approved" data-url="<?= e(base_url('admin/properties/changeStatus/' . (int)$property['id'])) ?>">
            <i class="bi bi-check-circle"></i> Duyệt tin
          </button>
        <?php endif; ?>

        <!-- Nút Tạm ẩn / Hiện lại tin -->
        <?php if ($property['status'] !== 'hidden'): ?>
          <button type="button" class="btn btn-sm btn-outline-warning btn-admin-status" id="btnAdminHide" data-id="<?= (int)$property['id'] ?>" data-status="hidden" data-url="<?= e(base_url('admin/properties/changeStatus/' . (int)$property['id'])) ?>">
            <i class="bi bi-eye-slash"></i> Tạm ẩn
          </button>
        <?php else: ?>
          <button type="button" class="btn btn-sm btn-outline-info btn-admin-status" id="btnAdminShow" data-id="<?= (int)$property['id'] ?>" data-status="approved" data-url="<?= e(base_url('admin/properties/changeStatus/' . (int)$property['id'])) ?>">
            <i class="bi bi-eye"></i> Hiện lại tin
          </button>
        <?php endif; ?>

        <!-- Nút Xóa tin -->
        <button type="button" class="btn btn-sm btn-outline-danger btn-admin-status" id="btnAdminDelete" data-id="<?= (int)$property['id'] ?>" data-status="deleted" data-url="<?= e(base_url('admin/properties/changeStatus/' . (int)$property['id'])) ?>">
          <i class="bi bi-trash"></i> Xóa
        </button>

        <!-- Nút Về danh sách tin -->
        <a href="<?= e(base_url('admin/properties')) ?>" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-card-checklist"></i> Danh sách tin
        </a>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Cột trái: Thông tin chính, gallery, tiện ích, bản đồ -->
    <div class="col-lg-8">
      <!-- Tiêu đề & Tag -->
      <div class="mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
          <span class="badge <?= $property['listing_type'] === 'sale' ? 'badge-sale text-bg-warning text-white' : 'badge-rent text-bg-info text-white' ?> fs-6">
            <?= $property['listing_type'] === 'sale' ? 'Bán' : 'Cho thuê' ?>
          </span>
          <span class="badge text-bg-primary fs-6"><?= e($property['type_name']) ?></span>
          <span id="verifiedBadgeWrapper">
            <?php if (!empty($property['is_verified'])): ?>
              <span class="badge badge-verified text-bg-success text-white fs-6">✓ Đã xác thực</span>
            <?php endif; ?>
          </span>
          <span class="badge text-bg-light border text-muted">👁 <?= e($property['views']) ?> lượt xem</span>
          <span class="badge text-bg-light border text-muted">Mã tin: #<?= (int)$property['id'] ?></span>
          <span class="badge text-bg-light border text-muted">Cập nhật: <?= date('d/m/Y', strtotime($property['updated_at'] ?: $property['created_at'])) ?></span>
        </div>
        <h1 class="h3 fw-bold mb-2"><?= e($property['title']) ?></h1>
        <p class="text-muted mb-0">
          📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['province_name']) ?>
        </p>
      </div>

      <!-- Mức giá & Diện tích nổi bật -->
      <div class="p-3 bg-light rounded-3 border mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <span class="text-muted small d-block">Mức giá:</span>
          <span class="text-danger fw-bold fs-3">
            <?= format_price($property['price']) ?><?= $property['listing_type'] === 'rent' ? '/tháng' : '' ?>
          </span>
          <?php if ($property['listing_type'] === 'rent' && !empty($property['deposit'])): ?>
            <span class="text-muted small d-block">Tiền cọc: <?= format_price($property['deposit']) ?></span>
          <?php endif; ?>
        </div>
        <div class="text-md-end">
          <span class="text-muted small d-block">Diện tích:</span>
          <span class="fw-bold fs-4 text-dark"><?= e($property['area']) ?> m²</span>
          <?php if ($property['area'] > 0 && $property['listing_type'] === 'sale'): ?>
            <span class="text-muted small d-block">~<?= number_format($property['price'] / $property['area'] / 1e6, 1) ?> triệu/m²</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Gallery hình ảnh -->
      <div class="mb-4">
        <?php if (!empty($images)): ?>
          <div class="mb-2">
            <button type="button" class="border-0 bg-transparent p-0 w-100 gallery-open" data-bs-toggle="modal" data-bs-target="#galleryModal" aria-label="Xem ảnh lớn">
              <img id="mainGalleryImg" src="<?= e(image_url($images[0]['image_url'])) ?>" alt="<?= e($property['title']) ?>" class="gallery-main shadow-sm border">
            </button>
          </div>
          <?php if (count($images) > 1): ?>
            <div class="d-flex gap-2 overflow-auto pb-2">
              <?php foreach ($images as $idx => $img): ?>
                <img src="<?= e(image_url($img['image_url'])) ?>" data-gallery-url="<?= e(image_url($img['image_url'])) ?>" alt="Ảnh <?= $idx + 1 ?>" class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="gallery-main bg-secondary-subtle d-flex flex-column align-items-center justify-content-center text-muted border rounded-3">
            <span class="fs-1 mb-2">🏠</span>
            <span>Chưa có hình ảnh đăng tải</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Đặc điểm bất động sản -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-3 border-bottom pb-2">Đặc điểm bất động sản</h5>
          <div class="row g-3">
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Phòng ngủ</span>
                <span class="fw-bold"><?= (int)$property['bedrooms'] > 0 ? (int)$property['bedrooms'] . ' phòng' : 'Chưa cập nhật' ?></span>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Phòng tắm / WC</span>
                <span class="fw-bold"><?= (int)$property['bathrooms'] > 0 ? (int)$property['bathrooms'] . ' phòng' : 'Chưa cập nhật' ?></span>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Hướng nhà</span>
                <span class="fw-bold"><?= e($property['direction'] ?: 'Không xác định') ?></span>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Pháp lý</span>
                <span class="fw-bold"><?= e($property['legal_status'] ?: 'Đang chờ sổ / Khác') ?></span>
              </div>
            </div>
            <?php if ($property['listing_type'] === 'rent'): ?>
              <div class="col-6 col-md-4">
                <div class="property-spec-item">
                  <span class="text-muted small d-block">Tình trạng nội thất</span>
                  <span class="fw-bold">
                    <?php
                      switch ($property['furniture']) {
                        case 'full': echo 'Đầy đủ (Full)'; break;
                        case 'basic': echo 'Cơ bản'; break;
                        default: echo 'Không có (Trống)'; break;
                      }
                    ?>
                  </span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="property-spec-item">
                  <span class="text-muted small d-block">Nuôi thú cưng</span>
                  <span class="fw-bold"><?= !empty($property['pets_allowed']) ? '✓ Cho phép' : '✕ Không cho phép' ?></span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Mô tả chi tiết -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-3 border-bottom pb-2">Thông tin mô tả</h5>
          <div class="lh-lg text-secondary">
            <?= nl2br(e($property['description'] ?: 'Chưa có thông tin mô tả chi tiết cho bất động sản này.')) ?>
          </div>
        </div>
      </div>

      <!-- Danh sách tiện ích -->
      <?php if (!empty($amenities)): ?>
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-body">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Tiện ích đi kèm</h5>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($amenities as $am): ?>
                <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fs-6">
                  <span class="text-success me-1">✓</span> <?= e($am['name']) ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Vị trí & Tiện ích khu vực xung quanh bất động sản -->
      <div class="card shadow-sm border-0 mb-4" id="mapSection">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 border-bottom pb-2">
            <div>
              <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger"></i> Vị trí & Tiện ích khu vực
              </h5>
              <span class="text-muted small">Bản đồ vị trí bất động sản trực quan • Khám phá tiện ích bán kính 1 - 2km</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Nút tắt / đặt lại tiện ích -->
              <button type="button" id="btnResetPoi" class="btn btn-outline-secondary btn-sm d-none" onclick="resetNearbyPoi()">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Xóa tiện ích
              </button>
            </div>
          </div>

          <!-- Thanh nút bấm lọc tiện ích xung quanh -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted mb-2">
              <i class="bi bi-compass me-1"></i> Chọn tiện ích muốn xem quanh bất động sản:
            </label>
            <div class="d-flex flex-wrap gap-2" id="poiFilterGroup">
              <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn" data-poi="school">
                🏫 Trường học
              </button>
              <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn" data-poi="hospital">
                🏥 Bệnh viện / Y tế
              </button>
              <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn" data-poi="market">
                🛒 Siêu thị / Chợ
              </button>
              <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn text-dark" data-poi="bus">
                🚌 Trạm xe buýt
              </button>
              <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn text-dark" data-poi="park">
                🌳 Công viên
              </button>
            </div>
          </div>

          <!-- Trạng thái đang tải tiện ích -->
          <div id="poiLoadingNotice" class="alert alert-light border small text-muted py-2 px-3 d-none align-items-center gap-2 mb-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span id="poiLoadingText">Đang tra cứu dữ liệu tiện ích xung quanh...</span>
          </div>

          <?php if (!empty($property['is_coord_fallback']) || !$hasValidCoords): ?>
            <div class="alert alert-warning py-2 small mb-3">
              <i class="bi bi-exclamation-triangle me-1"></i> Tin này chưa có tọa độ được xác nhận chính xác từ người đăng; bản đồ đang hiển thị vị trí khu vực tham khảo.
            </div>
          <?php endif; ?>

          <!-- Khung bản đồ bất động sản trực quan (chiều cao 450px, bo góc 16px) -->
          <div id="propertyMap" class="shadow-sm position-relative" style="height: 450px; min-height: 450px; width: 100%; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; background-color: #f8fafc;"></div>

          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3">
            <p class="small text-muted mb-0">
              📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['province_name']) ?>
            </p>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <a href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=%2C%3B<?= $propLat ?>%2C<?= $propLng ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.8rem;" title="Xem lộ trình di chuyển chi tiết">
                <i class="bi bi-signpost-2 me-1"></i>Chỉ đường trực tuyến
              </a>
              <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $propLat ?>,<?= $propLng ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.8rem;" title="Mở chỉ đường trên Google Maps">
                <i class="bi bi-geo-alt me-1"></i>Chỉ đường (Google Maps)
              </a>
            </div>
          </div>

          <!-- Danh sách kết quả tiện ích gần nhất -->
          <div id="poiResultsWrap" class="mt-3 pt-3 border-top d-none">
            <h6 class="fw-bold small text-dark mb-2 d-flex align-items-center justify-content-between">
              <span id="poiResultTitle">Tiện ích xung quanh</span>
              <span class="badge bg-primary-subtle text-primary" id="poiResultCount">0 địa điểm</span>
            </h6>
            <div class="row g-2" id="poiItemsList">
              <!-- Rendered via JS -->
            </div>
          </div>
        </div>
      </div>

      <!-- Đánh giá & Nhận xét -->
      <div class="card shadow-sm border-0 mb-4" id="reviewsSection">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold mb-0">Đánh giá & Nhận xét</h5>
            <div class="text-end">
              <?php if (!empty($reviewStats['total_reviews'])): ?>
                <span class="fs-5 fw-bold text-warning">★ <?= number_format((float)$reviewStats['avg_rating'], 1) ?></span>
                <span class="text-muted small">/ 5 (<?= (int)$reviewStats['total_reviews'] ?> đánh giá)</span>
              <?php else: ?>
                <span class="text-muted small">Chưa có đánh giá</span>
              <?php endif; ?>
            </div>
          </div>
          <!-- Form gửi / cập nhật đánh giá -->
          <?php if (Auth::check()): ?>
            <?php if ((int)Auth::id() === (int)$property['user_id']): ?>
              <div class="alert alert-light border small text-muted mb-4">
                <i class="bi bi-info-circle me-1"></i> Bạn là người đăng tin bất động sản này nên không thể tự đánh giá.
              </div>
            <?php else: ?>
              <div class="bg-light p-3 rounded mb-4 border">
                <h6 class="fw-bold mb-2"><?= $userReview ? 'Cập nhật đánh giá của bạn' : 'Để lại đánh giá của bạn' ?></h6>
                <form method="post" action="<?= e(base_url('review/submit')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                  <div class="mb-2">
                    <label class="form-label small fw-semibold me-2">Số sao:</label>
                    <div class="d-inline-flex gap-3">
                      <?php for ($s = 5; $s >= 1; $s--): ?>
                        <div class="form-check form-check-inline m-0">
                          <input class="form-check-input" type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>" <?= ($userReview && (int)$userReview['rating'] === $s) || (!$userReview && $s === 5) ? 'checked' : '' ?> required>
                          <label class="form-check-label text-warning fw-bold" for="star<?= $s ?>"><?= $s ?> ★</label>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <div class="mb-2">
                    <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Chia sẻ cảm nhận của bạn về bất động sản này, vị trí, giá cả..."><?= e($userReview['comment'] ?? '') ?></textarea>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                      <?= $userReview ? 'Lưu thay đổi' : 'Gửi đánh giá' ?>
                    </button>
                    <?php if ($userReview): ?>
                      <button type="button" data-delete-action="<?= e(base_url('review/delete/' . (int)$userReview['id'])) ?>" class="btn btn-outline-danger btn-sm" onclick="handleDeleteReview(this)">
                        Xóa đánh giá
                      </button>
                    <?php endif; ?>
                  </div>
                </form>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-light border text-center p-3 mb-4">
              <p class="small text-muted mb-2">Vui lòng đăng nhập để gửi đánh giá và nhận xét về bất động sản này.</p>
              <a href="<?= e(base_url('auth/login')) ?>" class="btn btn-sm btn-outline-primary fw-semibold">Đăng nhập ngay</a>
            </div>
          <?php endif; ?>

          <!-- Danh sách nhận xét -->
          <?php if (empty($reviews)): ?>
            <p class="text-muted small text-center my-3">Chưa có đánh giá nào cho bất động sản này. Hãy là người đầu tiên để lại nhận xét!</p>
          <?php else: ?>
            <div class="review-list d-flex flex-column gap-3">
              <?php foreach ($reviews as $rev): ?>
                <div class="border-bottom pb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                      <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold small" style="width: 32px; height: 32px;">
                        <?= mb_substr(e($rev['user_name'] ?: 'U'), 0, 1) ?>
                      </div>
                      <span class="fw-semibold text-dark small"><?= e($rev['user_name']) ?></span>
                    </div>
                    <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></span>
                  </div>
                  <div class="text-warning small mb-1">
                    <?= str_repeat('★', (int)$rev['rating']) ?><?= str_repeat('☆', 5 - (int)$rev['rating']) ?>
                  </div>
                  <?php if (!empty($rev['comment'])): ?>
                    <p class="text-secondary small mb-0"><?= nl2br(e($rev['comment'])) ?></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Cột phải: Thông tin người đăng / môi giới & hành động -->
    <div class="col-lg-4">
      <div class="position-sticky" style="top: 20px;">
        <!-- Card Môi giới -->
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body text-center p-4">
            <div class="mb-3">
              <?php if (!empty($property['agent_avatar'])): ?>
                <img src="<?= e(base_url($property['agent_avatar'])) ?>" alt="<?= e($property['agent_name']) ?>" class="rounded-circle border" width="80" height="80">
              <?php else: ?>
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-3 border" style="width: 80px; height: 80px;">
                  <?= mb_substr(e($property['agent_name'] ?: 'U'), 0, 1) ?>
                </div>
              <?php endif; ?>
            </div>
            <h5 class="fw-bold mb-1"><?= e($property['agent_name'] ?: 'Môi giới BĐS') ?></h5>
            <p class="text-muted small mb-2">Đại diện đăng tin bất động sản</p>
            <?php if (!empty($property['agent_bio'])): ?>
              <p class="small text-secondary bg-light rounded-3 p-2 mb-3 border"><?= nl2br(e($property['agent_bio'])) ?></p>
            <?php else: ?>
              <div class="mb-2"></div>
            <?php endif; ?>

            <div class="d-grid gap-2">
              <?php if (!empty($property['agent_phone'])): ?>
                <a href="tel:<?= e($property['agent_phone']) ?>" class="btn btn-success fw-bold py-2">
                  📞 Gọi <?= e($property['agent_phone']) ?>
                </a>
              <?php else: ?>
                <button class="btn btn-secondary disabled py-2">Chưa cập nhật SĐT</button>
              <?php endif; ?>

              <?php if (!empty($property['agent_email'])): ?>
                <a href="mailto:<?= e($property['agent_email']) ?>?subject=Quan tâm tin đăng: <?= rawurlencode($property['title']) ?>" class="btn btn-outline-primary py-2">
                  ✉️ Gửi Email liên hệ
                </a>
              <?php endif; ?>

              <button type="button" id="btnOpenVietQr" class="btn btn-accent-gradient fw-bold py-2 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#vietQrModal">
                <i class="bi bi-qr-code-scan"></i> Đặt cọc giữ chỗ qua VietQR
              </button>
            </div>
          </div>
        </div>

        <!-- Card Đặt lịch hẹn xem nhà -->
        <div class="card shadow-sm border-0 mb-3" id="appointmentSection">
          <div class="card-body p-3">
            <h6 class="fw-bold mb-2">Đặt lịch xem nhà</h6>
            <p class="small text-muted mb-3">Chọn thời gian thuận tiện nhất, môi giới sẽ xác nhận và đón bạn đến xem bất động sản.</p>
            <?php if (Auth::check()): ?>
              <?php if ((int)Auth::id() === (int)$property['user_id']): ?>
                <div class="alert alert-secondary small mb-0 py-2">
                  Đây là tin đăng của bạn. <?php if (Auth::hasRole('agent')): ?><a href="<?= e(base_url('appointment/agent')) ?>">Xem lịch hẹn khách đặt</a>.<?php endif; ?>
                </div>
              <?php else: ?>
                <form method="post" action="<?= e(base_url('appointment/book')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                  <div class="mb-2">
                    <label class="form-label small fw-semibold">Thời gian muốn xem:</label>
                    <input type="datetime-local" name="appointment_date" class="form-control form-control-sm" required min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Ghi chú cho môi giới:</label>
                    <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Ví dụ: Tôi có thể xem vào buổi chiều, gọi trước 15 phút..."></textarea>
                  </div>
                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Xác nhận đặt lịch</button>
                  </div>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <div class="alert alert-light border small text-center p-2 mb-0">
                <p class="text-muted mb-2">Vui lòng đăng nhập để đặt lịch hẹn xem nhà trực tiếp.</p>
                <a href="<?= e(base_url('auth/login')) ?>" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Đăng nhập đặt lịch</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Chia sẻ, Yêu thích, So sánh & Báo cáo vi phạm -->
        <div class="d-flex flex-column gap-2">
          <div class="d-flex gap-2 align-items-center">
            <button id="btnFavorite" class="btn <?= !empty($isFavorited) ? 'btn-danger' : 'btn-outline-danger' ?> rounded-pill py-2 flex-grow-1 fw-medium bds-btn-touch shadow-sm" onclick="handleToggleFavorite(<?= (int)$property['id'] ?>)">
              <span id="favIcon" class="me-1"><?= !empty($isFavorited) ? '❤️' : '🤍' ?></span>
              <span id="favText"><?= !empty($isFavorited) ? 'Đã lưu tin' : 'Lưu tin' ?></span>
            </button>
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#shareModal"><i class="bi bi-share me-1"></i> Chia sẻ</button>
          </div>

          <!-- Nút So sánh BĐS này -->
          <div>
            <button type="button" id="btnDetailCompare" data-compare-id="<?= (int)$property['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill w-100 d-flex align-items-center justify-content-center gap-1 py-2 fw-semibold bds-btn-touch" onclick="handleToggleDetailCompare()">
              <i class="bi bi-arrow-left-right"></i>
              <span id="detailCompareText">So sánh tin này</span>
            </button>
          </div>

          <?php if (Auth::check()): ?>
            <?php if (!empty($hasReported)): ?>
              <button class="btn btn-outline-warning btn-sm w-100" disabled>
                🚩 Bạn đã báo cáo tin đăng này
              </button>
            <?php elseif ((int)Auth::id() !== (int)$property['user_id']): ?>
              <button class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#reportModal">
                🚩 Báo cáo tin đăng vi phạm
              </button>
            <?php endif; ?>
          <?php else: ?>
            <a href="<?= e(base_url('auth/login')) ?>" class="btn btn-outline-danger btn-sm w-100">
              🚩 Đăng nhập để báo cáo vi phạm
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Gợi ý Bất động sản tương tự trong khu vực -->
  <?php $similarList = !empty($similarProperties) ? $similarProperties : ($similar ?? []); ?>
  <div class="card border-0 shadow-sm rounded-4 p-4 mt-4 mb-4" id="similarPropertiesSection">
    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-houses text-primary me-2"></i>Bất động sản tương tự trong khu vực</h4>
    <?php if (!empty($similarList)): ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
        <?php foreach ($similarList as $item): ?>
          <?php
            $simAvatar = !empty($item['cover_url'])
              ? image_url($item['cover_url'])
              : (!empty($item['avatar_url']) ? $item['avatar_url'] : property_avatar_url($item['avatar'] ?? null));
            $simDetailUrl = base_url('property/detail/' . (int)$item['id']);
            $simListingType = $item['listing_type'] ?? 'sale';
          ?>
          <div class="col">
            <div class="card h-100 property-card position-relative border shadow-sm">
              <div class="property-card-img-wrap" style="height: 185px;">
                <a href="<?= e($simDetailUrl) ?>" class="d-block h-100 w-100">
                  <img src="<?= e($simAvatar) ?>" class="card-img-top h-100 w-100" style="object-fit: cover;" alt="<?= e($item['title']) ?>" loading="lazy">
                </a>
                <button type="button" class="btn btn-sm btn-light position-absolute top-0 start-0 m-2 rounded-circle btn-compare-card"
                        title="Thêm vào danh sách so sánh"
                        data-compare-id="<?= (int)$item['id'] ?>"
                        onclick="toggleCardCompare(event, <?= (int)$item['id'] ?>, this, <?= e(json_encode([
                            'id'            => (int)$item['id'],
                            'title'         => $item['title'],
                            'price'         => format_price($item['price']) . ($simListingType === 'rent' ? '/tháng' : ''),
                            'raw_price'     => (float)$item['price'],
                            'area'          => $item['area'] . ' m²',
                            'raw_area'      => (float)$item['area'],
                            'bedrooms'      => (int)($item['bedrooms'] ?? 0),
                            'ward_name'     => $item['ward_name'] ?? '',
                            'listing_type'  => $simListingType,
                            'cover_url'     => $simAvatar,
                            'detail_url'    => $simDetailUrl,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)) ?>)">
                  <i class="bi bi-arrow-left-right"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle btn-fav-card"
                        title="Lưu tin yêu thích"
                        onclick="toggleCardFavorite(event, <?= (int)$item['id'] ?>, this)">
                  🤍
                </button>
              </div>
              <div class="card-body d-flex flex-column p-3">
                <div class="d-flex flex-wrap gap-1 align-items-center mb-2">
                  <span class="badge <?= $simListingType === 'rent' ? 'badge-rent' : 'badge-sale' ?>">
                    <?= $simListingType === 'rent' ? 'Cho thuê' : 'Bán' ?>
                  </span>
                  <?php if (!empty($item['type_name'])): ?>
                    <span class="badge badge-type"><?= e($item['type_name']) ?></span>
                  <?php endif; ?>
                </div>
                <h6 class="card-title mb-2">
                  <a class="text-decoration-none text-dark fw-bold" href="<?= e($simDetailUrl) ?>" title="<?= e($item['title']) ?>">
                    <?= e($item['title']) ?>
                  </a>
                </h6>
                <div class="d-flex align-items-baseline gap-2 mb-2 mt-auto">
                  <span class="property-price text-danger fw-bold">
                    <?= e(format_price($item['price'])) ?><?= $simListingType === 'rent' ? '<small class="fs-6 fw-normal text-muted">/tháng</small>' : '' ?>
                  </span>
                </div>
                <div class="property-specs pt-2 border-top mb-3 d-block">
                  <div class="d-flex align-items-center text-muted small mb-1">
                    <span><i class="bi bi-aspect-ratio text-primary me-1"></i><?= e($item['area']) ?> m²</span>
                  </div>
                  <div class="property-location text-truncate text-secondary small d-flex align-items-center" title="<?= htmlspecialchars($item['ward_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-geo-alt-fill text-danger me-1 flex-shrink-0"></i>
                    <span class="text-truncate"><?= htmlspecialchars($item['ward_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                </div>
                <a href="<?= e($simDetailUrl) ?>" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold">
                  Xem chi tiết <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted small mb-0">Hiện chưa có bất động sản tương tự khác trong cùng khu vực.</p>
    <?php endif; ?>
  </div>

  <!-- Lịch sử Bất động sản vừa xem -->
  <div id="recently-viewed-section" class="mt-4 mb-4" style="display:none;" data-current-id="<?= (int)$property['id'] ?>">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Bất động sản vừa xem</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="clearRecentlyViewedHistory()">
          <i class="bi bi-trash3 me-1"></i>Xóa lịch sử xem
        </button>
      </div>
      <div id="recently-viewed-list" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 g-3"></div>
    </div>
  </div>
</div>

<?php
  $shareFullUrl = full_url('property/detail/' . (int)$property['id']);
  $shareMailSubject = 'Chia sẻ bất động sản: ' . ($property['title'] ?? '');
  $shareMailBody = ($property['title'] ?? '') . ' - Giá: ' . format_price($property['price']) . ' - Xem chi tiết tại: ' . $shareFullUrl;
  $vietQrConfig = config('vietqr', []);
  $vietQrBankId = !empty($vietQrConfig['bank_id']) ? (string)$vietQrConfig['bank_id'] : 'MB';
  $vietQrAccountNo = !empty($vietQrConfig['account_no']) ? (string)$vietQrConfig['account_no'] : '0902910780';
  $vietQrAccountName = !empty($vietQrConfig['account_name']) ? (string)$vietQrConfig['account_name'] : 'HA NGOC THIEN';
  $vietQrTemplate = !empty($vietQrConfig['template']) ? (string)$vietQrConfig['template'] : 'compact2';
  $vietQrBankNames = [
      'MB'   => 'Ngân hàng Quân Đội (MBBank)',
      'VCB'  => 'Ngân hàng Ngoại thương (Vietcombank)',
      'TCB'  => 'Ngân hàng Kỹ thương (Techcombank)',
      'ACB'  => 'Ngân hàng Á Châu (ACB)',
      'BIDV' => 'Ngân hàng Đầu tư & Phát triển (BIDV)',
      'ICB'  => 'Ngân hàng Công thương (VietinBank)',
  ];
  $vietQrBankLabel = $vietQrConfig['bank_name'] ?? ($vietQrBankNames[strtoupper($vietQrBankId)] ?? ('Ngân hàng ' . strtoupper($vietQrBankId)));
  $vietQrDefaultAmount = 5000000;
  $vietQrTransferNote = 'BDS' . (int)$property['id'] . ' DAT COC';
  $vietQrAddInfoEncoded = 'BDS' . (int)$property['id'] . '%20DAT%20COC';
  $vietQrAccountNameEncoded = rawurlencode($vietQrAccountName);
  // Fallback mặc định: https://img.vietqr.io/image/MB-0902910780-compact2.png?amount=...&addInfo=...&accountName=HA%20NGOC%20THIEN
  $vietQrImgDefault = 'https://img.vietqr.io/image/' . rawurlencode($vietQrBankId) . '-' . rawurlencode($vietQrAccountNo) . '-' . rawurlencode($vietQrTemplate) . '.png?amount=' . $vietQrDefaultAmount . '&addInfo=' . $vietQrAddInfoEncoded . '&accountName=' . $vietQrAccountNameEncoded;
?>

<!-- Modal Đặt cọc giữ chỗ BĐS qua VietQR (Napas 247) -->
<div class="modal fade" id="vietQrModal" tabindex="-1" aria-labelledby="vietQrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header text-white border-0 px-4 py-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-warning text-dark fw-bold px-2 py-1">NAPAS 247</span>
          <h5 class="modal-title fw-bold mb-0" id="vietQrModalLabel">
            <i class="bi bi-qr-code-scan me-1"></i> Đặt cọc giữ chỗ qua VietQR
          </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-4 align-items-center">
          <!-- Cột trái: Ảnh mã VietQR động -->
          <div class="col-md-5 text-center">
            <div class="p-3 bg-light rounded-4 border shadow-xs">
              <div class="small fw-bold text-primary mb-2">MÃ QR THANH TOÁN TỰ ĐỘNG</div>
              <img id="vietQrImage"
                   src="<?= e($vietQrImgDefault) ?>"
                   alt="Mã VietQR đặt cọc bất động sản #<?= (int)$property['id'] ?>"
                   class="img-fluid rounded-3 border bg-white p-2 shadow-sm mb-2"
                   style="max-width: 250px; width: 100%;">
              <div class="small text-muted mt-1">
                <i class="bi bi-shield-check text-success me-1"></i>Tự động điền chính xác số tiền & nội dung
              </div>
            </div>
          </div>

          <!-- Cột phải: Chọn mức cọc & Thông tin chuyển khoản -->
          <div class="col-md-7">
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark mb-2">1. Chọn mức tiền đặt cọc giữ chỗ:</label>
              <div class="d-flex flex-wrap gap-2 mb-2" id="vietQrPresetGroup">
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold vietqr-preset-btn active" data-amount="5000000" onclick="selectVietQrPreset(5000000, this)">
                  5.000.000đ
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold vietqr-preset-btn" data-amount="10000000" onclick="selectVietQrPreset(10000000, this)">
                  10.000.000đ
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold vietqr-preset-btn" data-amount="20000000" onclick="selectVietQrPreset(20000000, this)">
                  20.000.000đ
                </button>
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light fw-semibold">Số tiền tùy chọn</span>
                <input type="number" id="vietQrCustomAmount" class="form-control" value="5000000" min="500000" step="500000" placeholder="Nhập số tiền đặt cọc (VNĐ)..." oninput="handleVietQrCustomInput(this.value)">
                <span class="input-group-text bg-light text-muted">VNĐ</span>
              </div>
            </div>

            <div class="p-3 rounded-4 border bg-light mb-3">
              <div class="small fw-bold text-dark mb-2 border-bottom pb-2">2. Thông tin tài khoản thụ hưởng:</div>
              <div class="d-flex justify-content-between align-items-center py-1 small">
                <span class="text-muted">Ngân hàng:</span>
                <strong class="text-dark"><?= e($vietQrBankLabel) ?></strong>
              </div>
              <div class="d-flex justify-content-between align-items-center py-1 small">
                <span class="text-muted">Chủ TK:</span>
                <strong class="text-primary"><?= e($vietQrAccountName === 'CONG TY BDS VIET' ? 'CÔNG TY BĐS VIỆT' : $vietQrAccountName) ?></strong>
              </div>
              <div class="d-flex justify-content-between align-items-center py-1 small">
                <span class="text-muted">Số tài khoản:</span>
                <div class="d-flex align-items-center gap-2">
                  <strong class="text-dark fs-6 font-monospace" id="vietQrAccountNo"><?= e($vietQrAccountNo) ?></strong>
                  <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="btnCopyVietQrStk" onclick="copyVietQrText('<?= e($vietQrAccountNo) ?>', 'Đã sao chép Số tài khoản <?= e($vietQrAccountNo) ?>!')">
                    <i class="bi bi-clipboard me-1"></i>Sao chép STK
                  </button>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center py-1 small">
                <span class="text-muted">Số tiền cọc:</span>
                <strong class="text-danger fs-6" id="vietQrAmountDisplay">5.000.000đ</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center py-1 small">
                <span class="text-muted">Nội dung CK:</span>
                <div class="d-flex align-items-center gap-2">
                  <strong class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 font-monospace" id="vietQrTransferNote"><?= e($vietQrTransferNote) ?></strong>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btnCopyVietQrNote" onclick="copyVietQrText('<?= e($vietQrTransferNote) ?>', 'Đã sao chép nội dung chuyển khoản <?= e($vietQrTransferNote) ?>!')">
                    <i class="bi bi-clipboard me-1"></i>Sao chép nội dung
                  </button>
                </div>
              </div>
            </div>

            <div class="alert alert-info border-0 small mb-0 py-2 px-3">
              <i class="bi bi-phone-vibrate me-1"></i>
              <strong>Hướng dẫn thanh toán:</strong> Mở bất kỳ ứng dụng Ngân hàng nào (<strong>Vietcombank, MB, Techcombank, BIDV, ACB, MoMo,...</strong>) chọn tính năng <strong>Quét mã QR (VietQR / Napas 247)</strong> để hoàn tất đặt cọc giữ chỗ ưu tiên xem nhà.
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between align-items-center">
        <span class="small text-muted"><i class="bi bi-lock-fill text-success me-1"></i>Hoàn cọc 100% nếu thông tin thực tế không đúng mô tả</span>
        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal Chia sẻ đa kênh & Quét mã QR -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header border-bottom px-4 py-3">
        <h5 class="modal-title fw-bold mb-0" id="shareModalLabel"><i class="bi bi-share-fill text-primary me-2"></i>Chia sẻ bất động sản</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Mã QR Code xem trên điện thoại -->
        <div class="text-center mb-4">
          <img id="share-qr-code"
               src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode($shareFullUrl) ?>"
               alt="QR Code"
               width="180"
               height="180"
               class="img-fluid rounded-3 border p-2 shadow-sm mb-2">
          <p class="small text-muted mb-0"><em>Quét mã QR bằng điện thoại để xem nhanh tin này</em></p>
        </div>

        <!-- Khung sao chép liên kết -->
        <div class="mb-4">
          <label for="shareModalUrlInput" class="form-label small fw-semibold text-dark">Liên kết tin đăng</label>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-link-45deg"></i></span>
            <input type="text" id="shareModalUrlInput" class="form-control bg-light small" value="<?= e($shareFullUrl) ?>" readonly>
            <button type="button" class="btn btn-primary fw-semibold px-3" id="btnCopyShareModalUrl" onclick="copyShareModalUrl()">
              <i class="bi bi-clipboard me-1"></i>Sao chép
            </button>
          </div>
        </div>

        <!-- Chia sẻ nhanh qua Mạng xã hội -->
        <div>
          <label class="form-label small fw-semibold text-dark d-block mb-2">Chia sẻ nhanh qua mạng xã hội</label>
          <div class="row g-2">
            <div class="col-4">
              <a id="shareZaloLink"
                 href="https://zalo.me/share?url=<?= rawurlencode($shareFullUrl) ?>"
                 target="_blank"
                 rel="noopener noreferrer"
                 class="btn btn-outline-info w-100 rounded-3 py-2 fw-semibold d-flex flex-column align-items-center gap-1 text-dark">
                <span class="badge bg-primary rounded-pill px-2">Zalo</span>
                <span class="small">Zalo</span>
              </a>
            </div>
            <div class="col-4">
              <a id="shareFacebookLink"
                 href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareFullUrl) ?>"
                 target="_blank"
                 rel="noopener noreferrer"
                 class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold d-flex flex-column align-items-center gap-1">
                <i class="bi bi-facebook fs-5"></i>
                <span class="small">Facebook</span>
              </a>
            </div>
            <div class="col-4">
              <a id="shareEmailLink"
                 href="mailto:?subject=<?= rawurlencode($shareMailSubject) ?>&body=<?= rawurlencode($shareMailBody) ?>"
                 class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-semibold d-flex flex-column align-items-center gap-1">
                <i class="bi bi-envelope-fill fs-5"></i>
                <span class="small">Gửi Email</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($images)): ?>
<div class="modal fade" id="galleryModal" tabindex="-1" aria-label="Xem ảnh bất động sản" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h2 class="modal-title fs-5">Ảnh bất động sản</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
      <div class="modal-body p-2">
        <div id="propertyGalleryCarousel" class="carousel slide" data-bs-interval="false">
          <div class="carousel-inner">
            <?php foreach ($images as $index => $image): ?>
              <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <img src="<?= e(image_url($image['image_url'])) ?>" class="gallery-modal-img" alt="Ảnh <?= $index + 1 ?> của <?= e($property['title']) ?>">
                <div class="text-center py-2"><?= $index + 1 ?> / <?= count($images) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#propertyGalleryCarousel" data-bs-slide="prev" aria-label="Ảnh trước"><span class="carousel-control-prev-icon bg-dark rounded" aria-hidden="true"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#propertyGalleryCarousel" data-bs-slide="next" aria-label="Ảnh tiếp theo"><span class="carousel-control-next-icon bg-dark rounded" aria-hidden="true"></span></button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="property-mobile-actions d-lg-none fixed-bottom bg-white border-top shadow d-flex gap-2">
  <?php if (!empty($property['agent_phone'])): ?>
    <a href="tel:<?= e($property['agent_phone']) ?>" class="btn btn-success flex-fill">Gọi ngay</a>
  <?php else: ?>
    <button type="button" class="btn btn-secondary flex-fill" disabled>Chưa có SĐT</button>
  <?php endif; ?>
  <a href="#appointmentSection" class="btn btn-primary flex-fill">Đặt lịch</a>
  <button type="button" id="mobileFavorite" class="btn btn-outline-danger flex-fill" onclick="document.getElementById('btnFavorite').click()"><?= !empty($isFavorited) ? 'Đã lưu' : 'Lưu tin' ?></button>
</div>

<!-- Modal Báo cáo vi phạm -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= e(base_url('report/submit')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger" id="reportModalLabel">🚩 Báo cáo vi phạm tin đăng</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">Nếu bạn phát hiện thông tin không chính xác, lừa đảo hoặc vi phạm quy định, vui lòng gửi phản ánh để ban quản trị xử lý.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Chọn lý do phổ biến</label>
            <select class="form-select form-select-sm mb-2" onchange="if(this.value){document.getElementById('reportReason').value = this.value;}">
              <option value="">-- Chọn lý do thường gặp --</option>
              <option value="Tin giả mạo / Bất động sản không có thật">Tin giả mạo / Bất động sản không có thật</option>
              <option value="Thông tin sai lệch (giá, diện tích, vị trí thực tế)">Thông tin sai lệch (giá, diện tích, vị trí thực tế)</option>
              <option value="Bất động sản đã bán hoặc đã cho thuê từ trước">Bất động sản đã bán hoặc đã cho thuê từ trước</option>
              <option value="Không liên lạc được người đăng tin / Số điện thoại ảo">Không liên lạc được người đăng tin / Số điện thoại ảo</option>
              <option value="Nội dung phản cảm, hình ảnh không đúng chuẩn mực">Nội dung phản cảm, hình ảnh không đúng chuẩn mực</option>
            </select>
            <label class="form-label fw-semibold small">Mô tả cụ thể <span class="text-danger">*</span></label>
            <textarea id="reportReason" name="reason" class="form-control form-control-sm" rows="3" placeholder="Chi tiết lý do vi phạm (tối thiểu 5 ký tự)..." required minlength="5"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm fw-semibold">Gửi báo cáo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Thư viện hiển thị bản đồ vị trí và tiện ích xung quanh -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= e(base_url('assets/js/map.js?v=20261110')) ?>"></script>
<script>
  function switchGalleryImg(url, el) {
    const mainImg = document.getElementById('mainGalleryImg');
    if (mainImg) mainImg.src = url;
    document.querySelectorAll('.gallery-thumb').forEach(function _callback     (t       ){return t.classList.remove('active');});
    if (el) el.classList.add('active');
  }

  function handleCopyShareLink() {
    copyShareModalUrl();
  }

  function copyShareModalUrl() {
    const input = document.getElementById('shareModalUrlInput');
    const urlToCopy = (input && input.value) ? input.value : window.location.href;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(urlToCopy).then(function _callback2(){
        if (window.BDS && typeof window.BDS.toast === 'function') {
          BDS.toast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success');
        }
      }).catch(function _callback3(){
        if (input) {
          input.select();
          document.execCommand('copy');
        }
        if (window.BDS && typeof window.BDS.toast === 'function') {
          BDS.toast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success');
        }
      });
    } else {
      if (input) {
        input.select();
        document.execCommand('copy');
      }
      if (window.BDS && typeof window.BDS.toast === 'function') {
        BDS.toast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success');
      }
    }
  }

  function syncShareModalLinks() {
    const currentUrl = window.location.href;
    const qrImg = document.getElementById('share-qr-code');
    const urlInput = document.getElementById('shareModalUrlInput');
    const zaloLink = document.getElementById('shareZaloLink');
    const fbLink = document.getElementById('shareFacebookLink');
    const emailLink = document.getElementById('shareEmailLink');

    if (qrImg) {
      qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(currentUrl);
    }
    if (urlInput) {
      urlInput.value = currentUrl;
    }
    if (zaloLink) {
      zaloLink.href = 'https://zalo.me/share?url=' + encodeURIComponent(currentUrl);
    }
    if (fbLink) {
      fbLink.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentUrl);
    }
    if (emailLink) {
      const subject = 'Chia sẻ bất động sản: ' +( currentPropertyData.title || '');
      const body = (currentPropertyData.title || '') + ' - Giá: ' +( currentPropertyData.price_formatted || '') + '\nXem chi tiết tại: ' + currentUrl;
      emailLink.href = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    }
  }

  // =========================================================================
  // TÍNH NĂNG ĐẶT CỌC GIỮ CHỖ BẰNG VIETQR (NAPAS 247)
  // =========================================================================
  const vietQrPropertyId = <?= (int)$property['id'] ?>;
  const vietQrBankId = <?= json_for_script($vietQrBankId) ?>;
  const vietQrAccountNo = <?= json_for_script($vietQrAccountNo) ?>;
  const vietQrAccountName = <?= json_for_script($vietQrAccountName) ?>;
  const vietQrTemplate = <?= json_for_script($vietQrTemplate) ?>;

  function updateVietQrModal(amount) {
    const cleanAmount = Math.max(100000, Math.round(Number(amount) || 5000000));
    const qrImg = document.getElementById('vietQrImage');
    const amountDisplay = document.getElementById('vietQrAmountDisplay');
    const addInfo = 'BDS' + vietQrPropertyId + '%20DAT%20COC';
    const qrUrl = 'https://img.vietqr.io/image/' + encodeURIComponent(vietQrBankId) + '-' + encodeURIComponent(vietQrAccountNo) + '-' + encodeURIComponent(vietQrTemplate) + '.png?amount=' + cleanAmount + '&addInfo=' + addInfo + '&accountName=' + encodeURIComponent(vietQrAccountName);

    if (qrImg) {
      qrImg.src = qrUrl;
    }
    if (amountDisplay) {
      amountDisplay.textContent = new Intl.NumberFormat('vi-VN').format(cleanAmount) + 'đ';
    }
  }

  function selectVietQrPreset(amount, btn) {
    document.querySelectorAll('.vietqr-preset-btn').forEach(function _callback4(b){
      b.classList.remove('btn-primary', 'active');
      b.classList.add('btn-outline-primary');
    });
    if (btn) {
      btn.classList.remove('btn-outline-primary');
      btn.classList.add('btn-primary', 'active');
    }
    const customInput = document.getElementById('vietQrCustomAmount');
    if (customInput) {
      customInput.value = amount;
    }
    updateVietQrModal(amount);
  }

  function handleVietQrCustomInput(val) {
    const num = Math.round(Number(val) || 0);
    document.querySelectorAll('.vietqr-preset-btn').forEach(function _callback5(b){
      if (Number(b.dataset.amount) === num) {
        b.classList.remove('btn-outline-primary');
        b.classList.add('btn-primary', 'active');
      } else {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline-primary');
      }
    });
    if (num >= 100000) {
      updateVietQrModal(num);
    }
  }

  function copyVietQrText(textToCopy, toastMessage) {
    const msg = toastMessage || 'Đã sao chép vào bộ nhớ tạm!';
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(textToCopy).then(function _callback6(){
        if (window.BDS && typeof window.BDS.toast === 'function') {
          BDS.toast(msg, 'success');
        }
      }).catch(function _callback7(){
        fallbackCopyText(textToCopy, msg);
      });
    } else {
      fallbackCopyText(textToCopy, msg);
    }
  }

  function fallbackCopyText(textToCopy, msg) {
    const tempInput = document.createElement('input');
    tempInput.value = textToCopy;
    document.body.appendChild(tempInput);
    tempInput.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(tempInput);
    if (window.BDS && typeof window.BDS.toast === 'function') {
      BDS.toast(msg, 'success');
    }
  }

  function clearRecentlyViewedHistory() {
    if (window.RecentlyViewed && typeof window.RecentlyViewed.clear === 'function') {
      window.RecentlyViewed.clear();
      return;
    }
    try {
      localStorage.removeItem('bds_recently_viewed');
    } catch (e) {}
    const section = document.getElementById('recently-viewed-section');
    if (section) section.style.display = 'none';
    if (window.BDS && typeof window.BDS.toast === 'function') {
      BDS.toast('Đã xóa lịch sử bất động sản vừa xem.', 'info', 'Lịch sử xem tin');
    }
  }

  function initRecentlyViewedDetail() {
    const detailItem = {
      id: <?= (int)$property['id'] ?>,
      title: <?= json_for_script($property['title']) ?>,
      price: <?= json_for_script(format_price($property['price']) . (($property['listing_type'] ?? 'sale') === 'rent' ? '/tháng' : '')) ?>,
      price_formatted: <?= json_for_script(format_price($property['price']) . (($property['listing_type'] ?? 'sale') === 'rent' ? '/tháng' : '')) ?>,
      area: <?= json_for_script($property['area'] . ' m²') ?>,
      district: <?= json_for_script($property['district_name'] ?? ($property['ward_name'] ?? '')) ?>,
      district_name: <?= json_for_script($property['district_name'] ?? ($property['ward_name'] ?? '')) ?>,
      ward: <?= json_for_script($property['ward_name'] ?? '') ?>,
      ward_name: <?= json_for_script($property['ward_name'] ?? '') ?>,
      image: <?= json_for_script(!empty($images[0]['image_url']) ? image_url($images[0]['image_url']) : property_avatar_url($property['avatar'] ?? null)) ?>,
      avatar_url: <?= json_for_script(!empty($images[0]['image_url']) ? image_url($images[0]['image_url']) : property_avatar_url($property['avatar'] ?? null)) ?>,
      url: <?= json_for_script(base_url('property/detail/' . (int)$property['id'])) ?>
    };

    if (window.RecentlyViewed && typeof window.RecentlyViewed.record === 'function') {
      window.RecentlyViewed.record(detailItem);
      window.RecentlyViewed.render(detailItem.id);
      return;
    }

    try {
      const key = 'bds_recently_viewed';
      const raw = localStorage.getItem(key);
      let list = raw ? JSON.parse(raw) : [];
      if (!Array.isArray(list)) list = [];
      list = list.filter(function _callback8(item){
        return item && Number(item.id) !== Number(detailItem.id);
      });
      list.unshift(detailItem);
      list = list.slice(0, 6);
      localStorage.setItem(key, JSON.stringify(list));
    } catch (e) {}
  }

  function handleDeleteReview(btn) {
    const deleteAction = btn?.dataset?.deleteAction;
    const form = btn?.closest('form');
    if (!deleteAction || !form) return;
    if (window.BDS && typeof window.BDS.confirm === 'function') {
      window.BDS.confirm(
        'Bạn có chắc chắn muốn xóa đánh giá này? Hành động này không thể hoàn tác.',
        'Xác nhận xóa đánh giá',
        function _callback9(){
          form.action = deleteAction;
          form.submit();
        }
      );
    }
  }

  async function handleToggleFavorite(propertyId) {
    const btn = document.getElementById('btnFavorite');
    const icon = document.getElementById('favIcon');
    const text = document.getElementById('favText');
    if (btn) btn.disabled = true;

    try {
      const res = await api('favorite/toggle/' + propertyId, { method: 'POST' });
      if (res.success) {
        const liked = res.data.liked;
        const mobileFavorite = document.getElementById('mobileFavorite');
        if (mobileFavorite) mobileFavorite.textContent = liked ? 'Đã lưu' : 'Lưu tin';
        if (liked) {
          btn.classList.remove('btn-outline-danger');
          btn.classList.add('btn-danger');
          if (icon) icon.textContent = '❤️';
          if (text) text.textContent = 'Đã lưu tin';
          if (window.BDS && typeof window.BDS.toast === 'function') {
            window.BDS.toast('Đã thêm bất động sản vào danh sách yêu thích.', 'success', 'Yêu thích');
          }
        } else {
          btn.classList.remove('btn-danger');
          btn.classList.add('btn-outline-danger');
          if (icon) icon.textContent = '🤍';
          if (text) text.textContent = 'Lưu tin';
          if (window.BDS && typeof window.BDS.toast === 'function') {
            window.BDS.toast('Đã xóa bất động sản khỏi danh sách yêu thích.', 'warning', 'Yêu thích');
          }
        }
      } else {
        if (res.data && res.data.require_login) {
          if (window.BDS && typeof window.BDS.confirm === 'function') {
            window.BDS.confirm(
              'Vui lòng đăng nhập tài khoản để lưu bất động sản yêu thích. Bạn có muốn chuyển đến trang đăng nhập ngay không?',
              'Yêu cầu đăng nhập',
              function _callback0(){
                window.location.href = '<?= e(base_url('auth/login')) ?>';
              }
            );
          } else {
            window.location.href = '<?= e(base_url('auth/login')) ?>';
          }
        } else if (window.BDS && typeof window.BDS.toast === 'function') {
          window.BDS.toast(res.message || 'Không thể thực hiện thao tác.', 'danger', 'Lỗi');
        }
      }
    } catch (err) {
      if (window.BDS && typeof window.BDS.toast === 'function') {
        window.BDS.toast('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.', 'danger', 'Lỗi kết nối');
      }
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  // Dữ liệu BĐS phục vụ tính năng so sánh
  const currentPropertyData = {
    id: <?= (int)$property['id'] ?>,
    title: <?= json_for_script($property['title']) ?>,
    price: <?= (float)($property['price'] ?? 0) ?>,
    price_formatted: <?= json_for_script(format_price($property['price'])) ?>,
    area: <?= (float)($property['area'] ?? 0) ?>,
    avatar: <?= json_for_script(!empty($images[0]['image_url']) ? image_url($images[0]['image_url']) : property_avatar_url($property['avatar'] ?? null)) ?>,
    listing_type: <?= json_for_script($property['listing_type'] ?? 'sale') ?>,
    address: <?= json_for_script($property['ward_name'] . ', ' . $property['province_name']) ?>,
    url: window.location.href
  };

  function updateDetailCompareBtn() {
    const btn = document.getElementById('btnDetailCompare');
    const text = document.getElementById('detailCompareText');
    if (!btn || !window.PropertyCompare) return;

    if (window.PropertyCompare.has(currentPropertyData.id)) {
      btn.classList.remove('btn-outline-primary');
      btn.classList.add('btn-primary');
      if (text) text.innerHTML = '<i class="bi bi-check2"></i> Đã thêm so sánh';
    } else {
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-outline-primary');
      if (text) text.innerHTML = '<i class="bi bi-arrow-left-right"></i> So sánh tin này';
    }
  }

  function handleToggleDetailCompare() {
    if (!window.PropertyCompare) return;
    window.PropertyCompare.toggle(currentPropertyData);
    updateDetailCompareBtn();
  }

  // =========================================================================
  // BẢN ĐỒ VỊ TRÍ & TIỆN ÍCH KHU VỰC XUNG QUANH
  // =========================================================================
  const mapLat = <?= $propLat ?>;
  const mapLng = <?= $propLng ?>;
  const propTitle = <?= json_for_script($property['title']) ?>;
  const propPriceFormatted = <?= json_for_script(format_price($property['price'])) ?>;
  const propAreaFormatted = <?= json_for_script($property['area'] . ' m²') ?>;
  const propAddressFormatted = <?= json_for_script(($property['address_detail'] ? $property['address_detail'] . ', ' : '') . $property['ward_name'] . ', ' . $property['province_name']) ?>;
  const propWard = <?= json_for_script($property['ward_name'] ?? '') ?>;

  let detailMapInstance = null;
  let activePoiCat = null;
  let currentPoiItems = [];

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  async function handlePoiCategoryClick(catKey, btn) {
    if (!detailMapInstance || !detailMapInstance.loadPoi) return;

    if (activePoiCat === catKey) {
      resetNearbyPoi();
      return;
    }

    activePoiCat = catKey;
    document.querySelectorAll('.poi-btn').forEach(function _callback1(b){
      b.classList.remove('btn-navy', 'text-white');
    });
    btn.classList.add('btn-navy', 'text-white');

    const notice = document.getElementById('poiLoadingNotice');
    const resetBtn = document.getElementById('btnResetPoi');
    const resultsWrap = document.getElementById('poiResultsWrap');
    const itemsList = document.getElementById('poiItemsList');
    const titleEl = document.getElementById('poiResultTitle');
    const countEl = document.getElementById('poiResultCount');

    if (notice) notice.classList.remove('d-none');
    if (resetBtn) resetBtn.classList.remove('d-none');

    const wardName = <?= json_for_script($property['ward_name'] ?? '') ?>;
    const items = await detailMapInstance.loadPoi(catKey, wardName);

    if (notice) notice.classList.add('d-none');

    if (resultsWrap && itemsList) {
      resultsWrap.classList.remove('d-none');
      const conf = (detailMapInstance.POI_CONFIG && detailMapInstance.POI_CONFIG[catKey]) ? detailMapInstance.POI_CONFIG[catKey] : { label: 'Tiện ích', icon: '📍' };
      if (titleEl) titleEl.innerHTML = conf.icon + ' ' + conf.label + ' gần bất động sản:';
      if (countEl) countEl.textContent = items.length + ' địa điểm';

      itemsList.innerHTML = items.map(function _callback10(it){
        const walkMin = Math.max(1, Math.round(it.distance / 75));
        return '<div class="col-md-6">' +
          '<div class="p-2 border rounded-3 bg-white h-100 d-flex align-items-center justify-content-between poi-item-card" onclick="panToPoi(' + it.lat + ', ' + it.lng + ')" style="cursor: pointer;">' +
            '<div class="d-flex align-items-center gap-2 overflow-hidden me-2">' +
              '<span class="fs-5">' + it.icon + '</span>' +
              '<div class="text-truncate">' +
                '<div class="fw-semibold text-dark small text-truncate" title="' + escapeHtml(it.name) + '">' + escapeHtml(it.name) + '</div>' +
                '<div class="text-muted" style="font-size: 0.75rem;">' + escapeHtml(it.type) + ' • ~' + walkMin + ' phút đi bộ</div>' +
              '</div>' +
            '</div>' +
            '<span class="badge bg-danger-subtle text-danger fw-bold flex-shrink-0">' + it.formattedDistance + '</span>' +
          '</div>' +
        '</div>';
      }).join('');
    }
  }

  function panToPoi(poiLat, poiLng) {
    if (!detailMapInstance || !detailMapInstance.map) return;
    detailMapInstance.map.setView([poiLat, poiLng], 16, { animate: true });
    if (detailMapInstance.poiLayer) {
      detailMapInstance.poiLayer.eachLayer(function _callback11(layer){
        if (layer.getLatLng) {
          const pos = layer.getLatLng();
          if (Math.abs(pos.lat - poiLat) < 0.0001 && Math.abs(pos.lng - poiLng) < 0.0001) {
            layer.openPopup();
          }
        }
      });
    }
  }

  function resetNearbyPoi() {
    activePoiCat = null;
    document.querySelectorAll('.poi-btn').forEach(function _callback12   (b                       ){return b.classList.remove('btn-navy','text-white');});
    const resetBtn = document.getElementById('btnResetPoi');
    if (resetBtn) resetBtn.classList.add('d-none');
    const resultsWrap = document.getElementById('poiResultsWrap');
    if (resultsWrap) resultsWrap.classList.add('d-none');
    if (detailMapInstance && detailMapInstance.clearPoi) {
      detailMapInstance.clearPoi();
    }
  }

  // CÔNG CỤ TÍNH LÃI SUẤT VAY MUA NHÀ
  function initMortgageCalculator() {
    const priceInput = document.getElementById('calcHomePrice');
    const ratioSlider = document.getElementById('calcLoanRatio');
    const ratioText = document.getElementById('calcLoanRatioText');
    const displayLoanAmount = document.getElementById('displayLoanAmount');
    const termSlider = document.getElementById('calcLoanTerm');
    const termText = document.getElementById('calcLoanTermText');
    const monthsText = document.getElementById('calcLoanMonthsText');
    const interestInput = document.getElementById('calcInterestRate');
    const displayPriceFormatted = document.getElementById('displayHomePriceFormatted');

    // Kết quả
    const resMonthly = document.getElementById('resMonthlyPayment');
    const resPrincipal = document.getElementById('resPrincipalMonthly');
    const resInterest = document.getElementById('resInterestFirst');
    const resTotalInterest = document.getElementById('resTotalInterest');
    const resTotalPayment = document.getElementById('resTotalPayment');
    const labelMonthly = document.getElementById('labelMonthlyPayment');
    const labelInterest = document.getElementById('labelInterestFirst');

    const barEquity = document.getElementById('barEquity');
    const barPrincipal = document.getElementById('barPrincipal');
    const barInterest = document.getElementById('barInterest');
    const scheduleBody = document.getElementById('scheduleTableBody');

    if (!priceInput || !ratioSlider || !termSlider || !interestInput) return;

    function formatVND(num) {
      return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';
    }

    function calculate() {
      const homePrice = parseFloat(priceInput.value) || 0;
      const ratio = parseFloat(ratioSlider.value) || 70;
      const loanAmount = homePrice *( ratio / 100);
      const years = parseInt(termSlider.value) || 20;
      const months = years * 12;
      const annualRate = parseFloat(interestInput.value) || 8.5;
      const monthlyRate = (annualRate / 100) / 12;
      const method = document.querySelector('input[name="calcMethod"]:checked') ? document.querySelector('input[name="calcMethod"]:checked').value : 'reducing';

      if (ratioText) ratioText.textContent = ratio;
      if (displayLoanAmount) displayLoanAmount.textContent = formatVND(loanAmount);
      if (termText) termText.textContent = years;
      if (monthsText) monthsText.textContent = months;
      if (displayPriceFormatted) displayPriceFormatted.textContent = formatVND(homePrice);

      if (loanAmount <= 0 || months <= 0) return;

      const monthlyPrincipal = loanAmount / months;
      const firstInterest = loanAmount * monthlyRate;
      let monthlyPayment = 0;
      let totalInterest = 0;
      let totalPayment = 0;

      if (method === 'reducing') {
        if (labelMonthly) labelMonthly.textContent = 'Số tiền trả tháng đầu tiên:';
        if (labelInterest) labelInterest.textContent = 'Tiền lãi tháng đầu:';
        monthlyPayment = monthlyPrincipal + firstInterest;
        totalInterest = (loanAmount + monthlyPrincipal) / 2 * months * monthlyRate;
        totalPayment = loanAmount + totalInterest;
      } else {
        if (labelMonthly) labelMonthly.textContent = 'Số tiền trả đều mỗi tháng:';
        if (labelInterest) labelInterest.textContent = 'Lãi ước tính tháng 1:';
        if (monthlyRate > 0) {
          monthlyPayment = (loanAmount * monthlyRate * Math.pow(1 + monthlyRate, months)) /( Math.pow(1 + monthlyRate, months) - 1);
          totalPayment = monthlyPayment * months;
          totalInterest = totalPayment - loanAmount;
        } else {
          monthlyPayment = monthlyPrincipal;
          totalPayment = loanAmount;
          totalInterest = 0;
        }
      }

      if (resMonthly) resMonthly.textContent = formatVND(monthlyPayment);
      if (resPrincipal) resPrincipal.textContent = formatVND(monthlyPrincipal);
      if (resInterest) resInterest.textContent = formatVND(firstInterest);
      if (resTotalInterest) resTotalInterest.textContent = formatVND(totalInterest);
      if (resTotalPayment) resTotalPayment.textContent = formatVND(totalPayment);

      // Phân bổ thanh toán trực quan
      const equity = Math.max(0, homePrice - loanAmount);
      const grandSum = equity + totalPayment;
      if (grandSum > 0) {
        const pEq = Math.round((equity / grandSum) * 100);
        const pPr = Math.round((loanAmount / grandSum) * 100);
        const pIn = Math.max(0, 100 - pEq - pPr);
        if (barEquity) { barEquity.style.width = pEq + '%'; barEquity.title = 'Vốn tự có: ' + formatVND(equity); }
        if (barPrincipal) { barPrincipal.style.width = pPr + '%'; barPrincipal.title = 'Gốc vay: ' + formatVND(loanAmount); }
        if (barInterest) { barInterest.style.width = pIn + '%'; barInterest.title = 'Tiền lãi: ' + formatVND(totalInterest); }
      }

      // Render 12 tháng đầu bảng trả nợ
      if (scheduleBody) {
        let remaining = loanAmount;
        let rowsHtml = '';
        for (let m = 1; m <= Math.min(12, months); m++) {
          const mInterest = remaining * monthlyRate;
          let mPrincipal = 0;
          let mTotal = 0;

          if (method === 'reducing') {
            mPrincipal = monthlyPrincipal;
            mTotal = mPrincipal + mInterest;
          } else {
            mTotal = monthlyPayment;
            mPrincipal = mTotal - mInterest;
          }

          const endRemaining = Math.max(0, remaining - mPrincipal);
          rowsHtml += '<tr>' +
            '<td class="text-center fw-bold">Tháng ' + m + '</td>' +
            '<td>' + formatVND(remaining) + '</td>' +
            '<td>' + formatVND(mPrincipal) + '</td>' +
            '<td>' + formatVND(mInterest) + '</td>' +
            '<td class="text-danger fw-bold">' + formatVND(mTotal) + '</td>' +
            '<td>' + formatVND(endRemaining) + '</td>' +
          '</tr>';
          remaining = endRemaining;
        }
        scheduleBody.innerHTML = rowsHtml;
      }
    }

    priceInput.addEventListener('input', calculate);
    ratioSlider.addEventListener('input', calculate);
    termSlider.addEventListener('input', calculate);
    interestInput.addEventListener('input', calculate);
    document.querySelectorAll('input[name="calcMethod"]').forEach(function _callback13   (r                  ){return r.addEventListener('change',calculate);});

    // Nút chọn nhanh tỷ lệ vay
    document.querySelectorAll('.quick-ratio-btn').forEach(function _callback14(btn){
      btn.addEventListener('click', function _callback15(){
        document.querySelectorAll('.quick-ratio-btn').forEach(function _callback16   (b                                      ){return b.classList.remove('active','btn-secondary','text-white');});
        this.classList.add('active');
        ratioSlider.value = this.dataset.ratio;
        calculate();
      });
    });

    // Nút chọn nhanh thời hạn vay
    document.querySelectorAll('.quick-term-btn').forEach(function _callback17(btn){
      btn.addEventListener('click', function _callback18(){
        document.querySelectorAll('.quick-term-btn').forEach(function _callback19   (b                                      ){return b.classList.remove('active','btn-secondary','text-white');});
        this.classList.add('active');
        termSlider.value = this.dataset.term;
        calculate();
      });
    });

    // Nút chọn nhanh ngân hàng
    document.querySelectorAll('.quick-bank-btn').forEach(function _callback20(btn){
      btn.addEventListener('click', function _callback21(){
        document.querySelectorAll('.quick-bank-btn').forEach(function _callback22   (b                                      ){return b.classList.remove('active','btn-secondary','text-white');});
        this.classList.add('active');
        interestInput.value = this.dataset.rate;
        calculate();
      });
    });

    calculate();
  }

  document.addEventListener('DOMContentLoaded', function _callback23() {
    initMortgageCalculator();
    // 1. Image gallery
    document.querySelectorAll('[data-gallery-url]').forEach(function _callback24(thumbnail){
      thumbnail.addEventListener('click', function _callback25(){
        switchGalleryImg(thumbnail.dataset.galleryUrl, thumbnail);
      });
    });

    const galleryModal = document.getElementById('galleryModal');
    if (galleryModal) {
      galleryModal.addEventListener('show.bs.modal', function _callback26(){
        const thumbs = Array.from(document.querySelectorAll('.gallery-thumb'));
        const selected = thumbs.indexOf(document.querySelector('.gallery-thumb.active'));
        const carousel = document.getElementById('propertyGalleryCarousel');
        if (carousel && selected >= 0) bootstrap.Carousel.getOrCreateInstance(carousel).to(selected);
      });
    }

    // 2. Khởi tạo bản đồ vị trí & tiện ích khu vực xung quanh
    const lat = Number.isFinite(Number(<?= $propLat ?>)) ? Number(<?= $propLat ?>) : 10.776889;
    const lng = Number.isFinite(Number(<?= $propLng ?>)) ? Number(<?= $propLng ?>) : 106.700806;
    const title = <?= json_for_script($property['title']) ?>;
    const mapContainerEl = document.getElementById('propertyMap');

    if (mapContainerEl) {
      const popupHtml = '<div style="min-width:200px; max-width:280px; font-family:system-ui, -apple-system, sans-serif; line-height:1.45; padding:3px;">' +
        '<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:5px;">' + escapeHtml(title) + '</div>' +
        '<div style="margin-bottom:6px;">' +
          '<span style="color:#dc3545; font-weight:800; font-size:15px;">' + escapeHtml(propPriceFormatted) + '</span>' +
          '<span style="color:#64748b; font-weight:600; font-size:13px;"> • ' + escapeHtml(propAreaFormatted) + '</span>' +
        '</div>' +
        '<div style="font-size:12px; color:#64748b; border-top:1px solid #f1f5f9; padding-top:4px; margin-bottom:8px;">' +
          '📍 ' + escapeHtml(propAddressFormatted) +
        '</div>' +
        '<div class="d-flex gap-1 pt-1 border-top">' +
          '<a href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=%2C%3B' + encodeURIComponent(lat) + '%2C' + encodeURIComponent(lng) + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success py-0 px-2 text-decoration-none" style="font-size:0.75rem;">' +
            '<i class="bi bi-signpost-2 me-1"></i>Chỉ đường trực tuyến' +
          '</a>' +
          '<a href="https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(lat) + ',' + encodeURIComponent(lng) + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary py-0 px-2 text-decoration-none" style="font-size:0.75rem;">' +
            '<i class="bi bi-geo-alt me-1"></i>Google Maps' +
          '</a>' +
        '</div>' +
      '</div>';

      try {
        if (window.BdsMap && typeof window.BdsMap.initViewer === 'function') {
          detailMapInstance = window.BdsMap.initViewer({
            mapId: 'propertyMap',
            lat: lat,
            lng: lng,
            zoom: 15,
            popupText: popupHtml
          });
        }
      } catch (viewerErr) {
        console.warn('Khởi tạo bản đồ qua BdsMap thất bại, chuyển sang chế độ dự phòng:', viewerErr);
        detailMapInstance = null;
      }

      // Fallback an toàn nếu BdsMap gặp lỗi hoặc chưa sẵn sàng nhưng L đã nạp
      if ((!detailMapInstance || !detailMapInstance.map) && typeof L !== 'undefined') {
        try {
          if (mapContainerEl._leaflet_id) {
            delete mapContainerEl._leaflet_id;
            mapContainerEl.innerHTML = '';
          }
          const fallbackMap = L.map('propertyMap', {
            center: [lat, lng],
            zoom: 15,
            scrollWheelZoom: true
          });
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
          }).addTo(fallbackMap);
          const fallbackMarker = L.marker([lat, lng], { title: 'Vị trí bất động sản' }).addTo(fallbackMap);
          try {
            fallbackMarker.bindPopup(popupHtml).openPopup();
          } catch (popupErr) {}
          detailMapInstance = {
            map: fallbackMap,
            marker: fallbackMarker,
            poiLayer: null,
            loadPoi: async function () { return []; },
            clearPoi: function () {
              try { fallbackMap.setView([lat, lng], 15); } catch (e) {}
            },
            POI_CONFIG: {},
            invalidateSize: function () {
              try { return fallbackMap.invalidateSize(); } catch (e) { return null; }
            }
          };
        } catch (fallbackErr) {
          console.error('Khởi tạo bản đồ dự phòng thất bại:', fallbackErr);
          detailMapInstance = null;
        }
      }

      // Fallback hiển thị thân thiện nếu hoàn toàn không tải được thư viện bản đồ
      if (!detailMapInstance || !detailMapInstance.map) {
        mapContainerEl.innerHTML =
          '<div class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center bg-light text-secondary">' +
            '<i class="bi bi-geo-alt-fill text-danger fs-1 mb-2"></i>' +
            '<div class="fw-semibold text-dark mb-1">Không thể hiển thị bản đồ tương tác ngay lúc này</div>' +
            '<div class="small mb-3">' + escapeHtml(propAddressFormatted) + '</div>' +
            '<a href="https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary rounded-pill px-3">' +
              '<i class="bi bi-map me-1"></i>Xem vị trí trên bản đồ trực tuyến' +
            '</a>' +
          '</div>';
      } else {
        const safeInvalidateMap = function _safeInvalidateDetailMap() {
          try {
            if (detailMapInstance && detailMapInstance.map && typeof detailMapInstance.map.invalidateSize === 'function') {
              detailMapInstance.map.invalidateSize();
            }
          } catch (e) {}
        };

        // Đảm bảo bản đồ vẽ lại đầy đủ gạch (chống khung xám/trắng khi render hoặc sau load)
        safeInvalidateMap();
        if (typeof detailMapInstance.map.on === 'function') {
          detailMapInstance.map.on('load', safeInvalidateMap);
        }
        if (document.readyState === 'complete') {
          setTimeout(safeInvalidateMap, 50);
        } else {
          window.addEventListener('load', safeInvalidateMap);
        }
        setTimeout(safeInvalidateMap, 150);
        setTimeout(safeInvalidateMap, 500);
        setTimeout(safeInvalidateMap, 1000);

        // Xử lý khi cuộn đến vùng bản đồ bằng IntersectionObserver
        if (typeof IntersectionObserver !== 'undefined') {
          const mapObserver = new IntersectionObserver(function _callback30(entries){
            entries.forEach(function _callback31(entry){
              if (entry.isIntersecting) {
                setTimeout(safeInvalidateMap, 50);
                setTimeout(safeInvalidateMap, 200);
              }
            });
          }, { threshold: 0.1 });
          mapObserver.observe(mapContainerEl);
        }
      }

      // Lắng nghe sự kiện click các nút lọc POI
      document.querySelectorAll('.poi-btn').forEach(function _callback34(btn){
        btn.addEventListener('click', function _callback35(){
          const cat = this.dataset.poi;
          handlePoiCategoryClick(cat, this);
        });
      });
    }

    // 4. Đồng bộ nút So sánh tin này
    updateDetailCompareBtn();
    window.addEventListener('storage', updateDetailCompareBtn);

    // 5. Đồng bộ Modal Chia sẻ đa kênh & QR Code
    syncShareModalLinks();
    const shareModalEl = document.getElementById('shareModal');
    if (shareModalEl) {
      shareModalEl.addEventListener('show.bs.modal', syncShareModalLinks);
    }

    // 6. Ghi nhận & Hiển thị Lịch sử Bất động sản vừa xem
    initRecentlyViewedDetail();
  });</script>

<?php if (!empty($_SESSION['user']) && ((int)($_SESSION['user']['role_id'] ?? 0) === 1 || ($_SESSION['user']['role'] ?? '') === 'admin')): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1. Xử lý Toggle Huy hiệu Đã xác thực
  const btnVerify = document.getElementById('btnAdminVerify');
  if (btnVerify) {
    btnVerify.addEventListener('click', async function () {
      const propId = this.dataset.id;
      const url = this.dataset.url || ('<?= e(base_url('admin/properties/verify/')) ?>' + propId);
      this.disabled = true;
      const originalHtml = this.innerHTML;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang xử lý...';

      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });
        const data = await res.json();
        if (data && data.success) {
          const isVerified = Number(data.is_verified) === 1;
          if (isVerified) {
            this.className = 'btn btn-sm btn-outline-secondary';
            this.innerHTML = '<i class="bi bi-patch-minus"></i> Hủy xác thực';
          } else {
            this.className = 'btn btn-sm btn-outline-success';
            this.innerHTML = '<i class="bi bi-patch-check"></i> Cấp xác thực';
          }

          const badgeWrap = document.getElementById('verifiedBadgeWrapper');
          if (badgeWrap) {
            badgeWrap.innerHTML = isVerified
              ? '<span class="badge badge-verified text-bg-success text-white fs-6">✓ Đã xác thực</span>'
              : '';
          }

          const verifyStatusText = document.getElementById('adminCurrentVerify');
          if (verifyStatusText) {
            if (isVerified) {
              verifyStatusText.className = 'badge bg-success';
              verifyStatusText.textContent = 'Đã xác thực';
            } else {
              verifyStatusText.className = 'badge bg-secondary';
              verifyStatusText.textContent = 'Chưa xác thực';
            }
          }
        } else {
          alert((data && data.message) ? data.message : 'Có lỗi xảy ra khi cập nhật.');
          this.innerHTML = originalHtml;
        }
      } catch (err) {
        console.error(err);
        alert('Lỗi kết nối máy chủ.');
        this.innerHTML = originalHtml;
      } finally {
        this.disabled = false;
      }
    });
  }

  // 2. Xử lý Thay đổi Trạng thái Tin đăng (Duyệt tin, Tạm ẩn tin, Xóa tin)
  document.querySelectorAll('.btn-admin-status').forEach(btn => {
    btn.addEventListener('click', async function () {
      const status = this.dataset.status;
      if (status === 'deleted' && !confirm('Bạn có chắc chắn muốn xóa tin đăng này? Thao tác này không thể hoàn tác!')) {
        return;
      }
      const propId = this.dataset.id;
      const url = this.dataset.url || ('<?= e(base_url('admin/properties/changeStatus/')) ?>' + propId);
      const originalHtml = this.innerHTML;
      this.disabled = true;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang xử lý...';

      try {
        const formData = new FormData();
        formData.append('status', status);

        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: formData
        });
        const data = await res.json();
        if (data && data.success) {
          if (status === 'deleted') {
            window.location.href = '<?= e(base_url('admin/properties')) ?>';
            return;
          }
          window.location.reload();
        } else {
          alert((data && data.message) ? data.message : 'Không thể cập nhật trạng thái.');
          this.innerHTML = originalHtml;
          this.disabled = false;
        }
      } catch (err) {
        console.error(err);
        alert('Lỗi kết nối máy chủ.');
        this.innerHTML = originalHtml;
        this.disabled = false;
      }
    });
  });
});
</script>
<?php endif; ?>
