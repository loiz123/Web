<?php
$items = $result['items'];
$total = (int)$result['total'];
$page = (int)$result['page'];
$pages = max(1, (int)ceil($total / $perPage));
$url = fn(string $t, int $p) => base_url('admin/properties') . '?tab=' . $t . '&page=' . $p;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Duyệt tin đăng</h1>
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin')) ?>">&larr; Bảng điều khiển</a>
</div>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="<?= e($url('pending', 1)) ?>">Tin chờ duyệt</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab === 'reports' ? 'active' : '' ?>" href="<?= e($url('reports', 1)) ?>">Báo cáo vi phạm</a></li>
</ul>

<div id="mod-alert" class="alert d-none" role="alert"></div>

<?php if (!$items): ?>
  <p class="text-muted">Không có mục nào cần xử lý.</p>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle" id="mod-table" data-tab="<?= e($tab) ?>">
    <?php if ($tab === 'pending'): ?>
    <thead><tr><th>#</th><th>Tiêu đề</th><th>Môi giới</th><th>Loại</th><th class="text-end">Giá</th><th>Ngày đăng</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
      <?php foreach ($items as $p): ?>
      <tr data-id="<?= (int)$p['id'] ?>">
        <td><?= (int)$p['id'] ?></td>
        <td><a href="<?= e(base_url('property/detail/' . (int)$p['id'])) ?>" target="_blank" rel="noopener"><?= e($p['title']) ?></a></td>
        <td><?= e($p['agent_name']) ?></td>
        <td><?= $p['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?></td>
        <td class="text-end"><?= e(number_format((float)$p['price'], 0, ',', '.')) ?> đ</td>
        <td><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-success" data-act="approve">Duyệt</button>
          <button class="btn btn-sm btn-warning" data-act="reject">Từ chối</button>
          <button class="btn btn-sm btn-outline-danger" data-act="delete">Xóa</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <?php else: ?>
    <thead><tr><th>#</th><th>Tin bị báo cáo</th><th>Người báo cáo</th><th>Lý do</th><th>Ngày</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
      <?php foreach ($items as $r): ?>
      <tr data-id="<?= (int)$r['id'] ?>">
        <td><?= (int)$r['id'] ?></td>
        <td><a href="<?= e(base_url('property/detail/' . (int)$r['property_id'])) ?>" target="_blank" rel="noopener"><?= e($r['property_title']) ?></a>
            <span class="badge text-bg-light"><?= e($r['property_status']) ?></span></td>
        <td><?= e($r['reporter_name']) ?></td>
        <td><?= e($r['reason']) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-danger" data-act="resolve-hide">Xử lý và ẩn tin</button>
          <button class="btn btn-sm btn-success" data-act="resolve">Đã xử lý</button>
          <button class="btn btn-sm btn-outline-secondary" data-act="dismiss">Bỏ qua</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <?php endif; ?>
  </table>
</div>

<?php if ($pages > 1): ?>
<nav><ul class="pagination">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($url($tab, $i)) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<p class="text-muted small">Tổng: <?= $total ?> mục.</p>
<?php endif; ?>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Từ chối tin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <label for="reject-reason" class="form-label">Lý do (môi giới sẽ thấy lý do này)</label>
      <textarea id="reject-reason" class="form-control" rows="3" maxlength="255"></textarea>
      <div id="reject-error" class="text-danger small mt-1"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
      <button type="button" class="btn btn-warning" id="reject-confirm">Từ chối tin</button>
    </div>
  </div></div>
</div>

<script src="<?= e(base_url('assets/js/admin.js')) ?>" defer></script>
