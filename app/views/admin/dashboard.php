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
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 fw-semibold border-bottom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-geo-alt text-danger me-2"></i>Tin đăng theo Tỉnh / Thành phố</span>
      </div>
      <?php $provinceList = $byProvince ?? $byDistrict ?? []; ?>
      <?php if (!$provinceList): ?>
        <div class="card-body text-muted py-4 text-center">Chưa có tin đăng nào.</div>
      <?php else: ?>
        <div class="px-3 py-2 bg-light border-bottom">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input type="search"
                   id="provinceSearchInput"
                   class="form-control border-start-0 ps-0"
                   placeholder="Tìm nhanh tỉnh / thành phố..."
                   aria-label="Tìm nhanh tỉnh / thành phố">
          </div>
        </div>
        <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
          <table class="table table-hover align-middle mb-0" id="provinceStatsTable">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
              <tr>
                <th class="ps-3">Tỉnh / Thành phố</th>
                <th class="text-end pe-3">Số lượng</th>
              </tr>
            </thead>
            <tbody id="provinceTableBody">
              <?php $totalProvinceApproved = 0; ?>
              <?php foreach ($provinceList as $p): ?>
                <?php $totalProvinceApproved += (int)$p['approved']; ?>
                <tr class="province-row" data-province="<?= e(mb_strtolower((string)$p['province'], 'UTF-8')) ?>">
                  <td class="ps-3 fw-medium text-dark province-name"><?= e($p['province']) ?></td>
                  <td class="text-end pe-3 text-success fw-semibold"><?= (int)$p['approved'] ?></td>
                </tr>
              <?php endforeach; ?>
              <tr id="provinceEmptyRow" class="d-none">
                <td colspan="2" class="text-center text-muted py-3">Không tìm thấy tỉnh/thành phố nào</td>
              </tr>
            </tbody>
            <tfoot class="table-light fw-bold" style="position: sticky; bottom: 0; z-index: 1;">
              <tr>
                <td class="ps-3 text-dark">Tổng cộng</td>
                <td class="text-end pe-3 text-success"><?= (int)$totalProvinceApproved ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var searchInput = document.getElementById('provinceSearchInput');
  var tableBody = document.getElementById('provinceTableBody');
  var emptyRow = document.getElementById('provinceEmptyRow');
  if (!searchInput || !tableBody) return;

  function normalizeText(str) {
    return (str || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd')
      .trim();
  }

  var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.province-row'));

  searchInput.addEventListener('input', function () {
    var rawQuery = searchInput.value.trim().toLowerCase();
    var normQuery = normalizeText(rawQuery);
    var visibleCount = 0;

    rows.forEach(function (row) {
      var nameCell = row.querySelector('.province-name');
      var text = nameCell ? nameCell.textContent : '';
      var lowerText = text.toLowerCase();
      var normText = normalizeText(text);

      var isMatch = !rawQuery || lowerText.indexOf(rawQuery) !== -1 || normText.indexOf(normQuery) !== -1;
      if (isMatch) {
        row.classList.remove('d-none');
        visibleCount++;
      } else {
        row.classList.add('d-none');
      }
    });

    if (emptyRow) {
      if (visibleCount === 0) {
        emptyRow.classList.remove('d-none');
      } else {
        emptyRow.classList.add('d-none');
      }
    }
  });
});
</script>

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
