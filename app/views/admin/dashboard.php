<?php
$statusLabels = [
    'pending'  => ['Chờ duyệt', 'warning'],
    'approved' => ['Đã duyệt', 'success'],
    'rejected' => ['Bị từ chối', 'danger'],
    'hidden'   => ['Đã ẩn', 'secondary'],
    'closed'   => ['Đã đóng', 'dark'],
];
?>
<style>
.dashboard-status-shortcut {
  transition: all 0.15s ease-in-out;
  text-decoration: none;
}
.dashboard-status-shortcut:hover {
  background-color: #f1f5f9 !important;
  transform: translateX(3px);
}
</style>

<h1 class="h4 mb-3">Bảng điều khiển quản trị</h1>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $n): ?>
    <div class="col-6 col-lg-3">
      <div class="card text-center h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="display-6 fw-bold"><?= (int)$n ?></div>
          <div class="text-muted small"><?= e($label) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4 align-items-start">
  <div class="col-lg-5">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 fw-semibold border-bottom">
        <i class="bi bi-pie-chart text-primary me-2"></i>Tin đăng theo trạng thái
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($statusLabels as $key => [$text, $color]): ?>
          <a href="<?= e(base_url('admin/properties?tab=' . $key)) ?>"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 dashboard-status-shortcut"
             title="Xem tin <?= e($text) ?>">
            <span class="badge text-bg-<?= e($color) ?>"><?= e($text) ?></span>
            <span class="d-flex align-items-center gap-1">
              <strong class="text-dark"><?= (int)($byStatus[$key] ?? 0) ?></strong>
              <i class="bi bi-chevron-right text-muted small"></i>
            </span>
          </a>
        <?php endforeach; ?>
        <a href="<?= e(base_url('admin/properties?tab=approved')) ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 bg-success-subtle fw-bold dashboard-status-shortcut"
           title="Xem danh sách tin đã duyệt đang hiển thị">
          <span class="text-success-emphasis">Tổng tin đã duyệt (đang hiển thị)</span>
          <span class="d-flex align-items-center gap-1">
            <strong class="text-success"><?= (int)($byStatus['approved'] ?? 0) ?></strong>
            <i class="bi bi-chevron-right text-muted small"></i>
          </span>
        </a>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 fw-semibold border-bottom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-geo-alt text-danger me-2"></i>Tin đăng theo Tỉnh / Thành phố</span>
        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Tin đã duyệt</span>
      </div>
      <?php $provinceList = $byProvince ?? $byDistrict ?? []; ?>
      <?php if (!$provinceList): ?>
        <div class="card-body text-muted py-4 text-center">Chưa có tin đăng nào.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Tỉnh / Thành phố</th>
                <th class="text-end pe-3">Số tin đã duyệt</th>
              </tr>
            </thead>
            <tbody>
              <?php $totalProvinceApproved = 0; ?>
              <?php foreach ($provinceList as $p): ?>
                <?php $totalProvinceApproved += (int)$p['approved']; ?>
                <tr>
                  <td class="ps-3 fw-medium text-dark"><?= e($p['province']) ?></td>
                  <td class="text-end pe-3 text-success fw-semibold"><?= (int)$p['approved'] ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="table-light fw-bold">
                <td class="ps-3 text-dark">Tổng cộng (Đã duyệt)</td>
                <td class="text-end pe-3 text-success"><?= (int)$totalProvinceApproved ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="list-group shadow-sm border-0">
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" href="<?= e(base_url('admin/users')) ?>">
    <span><i class="bi bi-people me-2 text-info"></i>Quản lý người dùng</span>
    <i class="bi bi-chevron-right text-muted small"></i>
  </a>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" href="<?= e(base_url('admin/catalog')) ?>">
    <span><i class="bi bi-tags me-2 text-warning"></i>Quản lý danh mục</span>
    <i class="bi bi-chevron-right text-muted small"></i>
  </a>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" href="<?= e(base_url('admin/properties')) ?>">
    <span><i class="bi bi-building-check me-2 text-success"></i>Quản lý tin đăng</span>
    <i class="bi bi-chevron-right text-muted small"></i>
  </a>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" href="<?= e(base_url('admin/reports')) ?>">
    <span><i class="bi bi-flag me-2 text-danger"></i>Quản lý báo cáo vi phạm</span>
    <div class="d-flex align-items-center gap-2">
      <?php if (!empty($stats['Báo cáo mới'])): ?>
        <span class="badge bg-danger rounded-pill"><?= (int)$stats['Báo cáo mới'] ?></span>
      <?php endif; ?>
      <i class="bi bi-chevron-right text-muted small"></i>
    </div>
  </a>
</div>
