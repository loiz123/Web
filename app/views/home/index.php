<!-- Hero Banner & Bộ lọc tìm kiếm trang chủ -->
<section class="p-4 p-md-5 mb-4 rounded-4 border bg-white shadow-sm position-relative" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
  <div class="row align-items-center mb-4">
    <div class="col-lg-8">
      <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger-subtle text-danger fw-semibold small mb-2">
        <i class="bi bi-stars"></i> Nền tảng Bất động sản Hiện đại & Thông minh
      </div>
      <h1 class="h2 fw-extrabold text-dark mb-2">Tìm kiếm & Khám phá ngôi nhà lý tưởng</h1>
      <p class="text-secondary mb-0">Hàng ngàn tin đăng nhà đất, căn hộ được xác thực thông tin, tích hợp bản đồ bất động sản trực quan và công cụ tính khoản vay ưu đãi.</p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-none d-md-block">
      <div class="d-inline-flex flex-wrap gap-2 justify-content-lg-end">
        <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-shield-check text-success me-1"></i> Tin xác thực</span>
        <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Bản đồ GPS</span>
        <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calculator text-primary me-1"></i> Tính lãi vay</span>
        <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-arrow-left-right text-warning me-1"></i> So sánh tin</span>
      </div>
    </div>
  </div>

  <!-- Search Form Partial -->
  <div class="search-form-wrap">
    <?php require APP . '/views/partials/search_form.php'; ?>
  </div>
</section>

<!-- Section Tin BĐS Mới Nhất -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 fw-bold mb-1 text-dark">
      <i class="bi bi-fire text-danger me-1"></i> Bất động sản mới nhất
    </h2>
    <p class="text-muted small mb-0">Các tin đăng mua bán và cho thuê vừa được cập nhật hôm nay</p>
  </div>
  <a href="<?= e(base_url('property/search')) ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-semibold">
    Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
  </a>
</div>

<?php if (!$properties): ?>
  <div class="py-5 text-center text-muted empty-state-box">
    <div class="fs-1 mb-2">🏠</div>
    <h5 class="fw-semibold text-dark">Chưa có tin đăng nào được duyệt</h5>
    <p class="small text-muted mb-0">Vui lòng quay lại sau hoặc liên hệ ban quản trị để đăng tin đầu tiên.</p>
  </div>
<?php else: ?>
  <div class="row g-4 mb-5">
    <?php foreach ($properties as $p) require APP . '/views/partials/property_card.php'; ?>
  </div>
<?php endif; ?>
<script src="<?= e(base_url('assets/js/search-ajax.js')) ?>"></script>
