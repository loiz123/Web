<?php
/**
 * Giao diện thông báo gián đoạn dịch vụ tạm thời (HTTP 500)
 * Thiết kế theo Bootstrap 5, thân thiện với người dùng cuối.
 */
?>
<div class="row justify-content-center py-5">
  <div class="col-md-8 col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center">
      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width: 76px; height: 76px;">
        <i class="bi bi-exclamation-octagon-fill fs-1"></i>
      </div>
      <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 mx-auto mb-2 fw-semibold">Mã thông báo: 500</span>
      <h1 class="h4 fw-bold text-dark mb-2">Dịch vụ đang tạm thời gián đoạn</h1>
      <p class="text-muted small mb-4 lh-base">
        Rất tiếc, yêu cầu của bạn chưa thể thực hiện ngay lúc này do hệ thống đang bận xử lý. Quý khách vui lòng tải lại trang sau ít phút hoặc quay về trang chủ để tiếp tục tìm kiếm bất động sản.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="<?= e(function_exists('base_url') ? base_url() : '/') ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
          <i class="bi bi-house-door me-1"></i>Về trang chủ
        </a>
        <a href="<?= e(function_exists('base_url') ? base_url('property/search') : '/property/search') ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
          <i class="bi bi-search me-1"></i>Tìm kiếm nhà đất
        </a>
      </div>
    </div>
  </div>
</div>
