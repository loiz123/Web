<?php
$statusLabels = [
    'pending'  => ['Chờ duyệt', 'warning'],
    'approved' => ['Đã duyệt', 'success'],
    'rejected' => ['Bị từ chối', 'danger'],
    'hidden'   => ['Đã ẩn', 'secondary'],
    'closed'   => ['Đã đóng', 'dark'],
];
?>
<h1 class="h4 mb-3">Bảng điều khiển quản trị</h1>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $n): ?>
    <div class="col-6 col-lg-3"><div class="card text-center"><div class="card-body"><div class="display-6"><?= (int)$n ?></div><div class="text-muted"><?= e($label) ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header">Tin đăng theo trạng thái</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($statusLabels as $key => [$text, $color]): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="badge text-bg-<?= e($color) ?>"><?= e($text) ?></span>
            <strong><?= (int)($byStatus[$key] ?? 0) ?></strong>
          </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between bg-light">
          <span>Tổng</span><strong><?= (int)array_sum($byStatus) ?></strong>
        </li>
      </ul>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">Tin đăng theo quận/huyện</div>
      <?php if (!$byDistrict): ?>
        <div class="card-body text-muted">Chưa có tin đăng nào.</div>
      <?php else: ?>
        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Quận/huyện</th><th>Tỉnh/thành</th><th class="text-end">Tổng tin</th><th class="text-end">Đã duyệt</th></tr></thead>
            <tbody>
              <?php foreach ($byDistrict as $d): ?>
                <tr>
                  <td><?= e($d['district']) ?></td>
                  <td><?= e($d['province']) ?></td>
                  <td class="text-end"><?= (int)$d['total'] ?></td>
                  <td class="text-end"><?= (int)$d['approved'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="list-group">
  <a class="list-group-item list-group-item-action" href="<?= e(base_url('admin/users')) ?>">Quản lý người dùng</a>
  <a class="list-group-item list-group-item-action" href="<?= e(base_url('admin/catalog')) ?>">Quản lý danh mục</a>
  <a class="list-group-item list-group-item-action" href="<?= e(base_url('admin/properties')) ?>">Duyệt tin đăng</a>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= e(base_url('admin/reports')) ?>">
    <span>Quản lý báo cáo vi phạm</span>
    <?php if (!empty($stats['Báo cáo mới'])): ?>
      <span class="badge bg-danger rounded-pill"><?= (int)$stats['Báo cáo mới'] ?></span>
    <?php endif; ?>
  </a>
</div>
