<!-- Hero Banner & Bộ lọc tìm kiếm trang chủ -->
<section class="p-4 p-md-5 mb-4 rounded-4 border bg-white shadow-sm position-relative" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
  <div class="row align-items-center mb-4">
    <div class="col-12">
      <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger-subtle text-danger fw-semibold small mb-2">
        <i class="bi bi-stars"></i> Nền tảng Bất động sản Hiện đại & Thông minh
      </div>
      <h1 class="h2 fw-extrabold text-dark mb-2">Tìm kiếm & Khám phá ngôi nhà lý tưởng</h1>
      <p class="text-secondary mb-0">Hàng ngàn tin đăng nhà đất, căn hộ được xác thực thông tin, tích hợp bản đồ bất động sản trực quan.</p>
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
  <div class="row g-4 mb-4">
    <?php foreach ($properties as $p) require APP . '/views/partials/property_card.php'; ?>
  </div>
  <?php if (($totalPages ?? 1) > 1): ?>
    <nav aria-label="Phân trang trang chủ" class="mb-5">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= ($totalPages ?? 1); $i++): ?>
          <li class="page-item <?= ($i === ($page ?? 1)) ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $i])) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<script src="<?= e(base_url('assets/js/search-ajax.js?v=20261111')) ?>"></script>
