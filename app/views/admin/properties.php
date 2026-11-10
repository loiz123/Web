<?php
$items = $result['items'];
$total = (int)$result['total'];
$page = (int)$result['page'];
$pages = max(1, (int)ceil($total / $perPage));
$propertiesUrl = base_url('admin/properties');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Duyệt tin đăng</h1>
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin')) ?>">&larr; Bảng điều khiển</a>
</div>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="<?= e($propertiesUrl . '?tab=pending&page=1') ?>">Tin chờ duyệt</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab === 'approved' ? 'active' : '' ?>" href="<?= e($propertiesUrl . '?tab=approved&page=1') ?>">Tin đã duyệt</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab === 'reports' ? 'active' : '' ?>" href="<?= e($propertiesUrl . '?tab=reports&page=1') ?>">Báo cáo vi phạm</a></li>
</ul>

<div id="mod-alert" class="alert d-none" role="alert"></div>

<?php if (!$items): ?>
  <p class="text-muted">Không có mục nào cần xử lý.</p>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle" id="mod-table" data-tab="<?= e($tab) ?>">
    <?php if ($tab !== 'reports'): ?>
    <thead><tr><th>#</th><th>Tiêu đề</th><th>Môi giới</th><th>Loại</th><th class="text-end">Giá</th><th class="text-center">Xác thực</th><th>Ngày đăng</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
      <?php foreach ($items as $p): ?>
      <tr data-id="<?= (int)$p['id'] ?>">
        <td><?= (int)$p['id'] ?></td>
        <td><a href="<?= e(base_url('property/detail/' . (int)$p['id'])) ?>" target="_blank" rel="noopener"><?= e($p['title']) ?></a></td>
        <td><?= e($p['agent_name']) ?></td>
        <td><?= $p['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?></td>
        <td class="text-end"><?= e(number_format((float)$p['price'], 0, ',', '.')) ?> đ</td>
        <td class="text-center text-nowrap">
          <button type="button" class="btn btn-sm btn-verify-toggle <?= !empty($p['is_verified']) ? 'btn-outline-warning' : 'btn-outline-success' ?>" data-id="<?= (int)$p['id'] ?>" title="Bật/Tắt huy hiệu xác thực">
            <?= !empty($p['is_verified']) ? '🛡️ Đã xác thực' : '🛡️ Cấp xác thực' ?>
          </button>
        </td>
        <td><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
        <td class="text-end text-nowrap">
          <?php if ($tab === 'pending'): ?>
            <button class="btn btn-sm btn-success" data-act="approve">Duyệt</button>
            <button class="btn btn-sm btn-warning" data-act="reject">Từ chối</button>
            <button class="btn btn-sm btn-outline-danger" data-act="delete">Xóa</button>
          <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="hidden">Ẩn tin</button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" onclick="return confirm('Bạn có chắc muốn xóa tin này?')">Xóa</button>
          <?php endif; ?>
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
    <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($propertiesUrl . '?tab=' . $tab . '&page=' . $i) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<p class="text-muted small" id="mod-total" data-total="<?= $total ?>">Tổng: <?= $total ?> mục.</p>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
  const alertBox = document.getElementById('mod-alert');
  function notify(msg, ok) {
    if (!alertBox) return;
    alertBox.className = 'alert alert-' + (ok ? 'success' : 'danger');
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  // Toggle xác thực
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-verify-toggle');
    if (!btn) return;
    const id = btn.dataset.id;
    btn.disabled = true;
    const oldText = btn.innerHTML;
    btn.innerHTML = '⏳...';
    try {
      const res = await fetch('<?= e(base_url('admin/properties/verify/')) ?>' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data && data.success) {
        const isVerified = Number(data.is_verified) === 1;
        if (isVerified) {
          btn.className = 'btn btn-sm btn-verify-toggle btn-outline-warning';
          btn.innerHTML = '🛡️ Đã xác thực';
        } else {
          btn.className = 'btn btn-sm btn-verify-toggle btn-outline-success';
          btn.innerHTML = '🛡️ Cấp xác thực';
        }
        notify(data.message || 'Cập nhật thành công.', true);
      } else {
        notify((data && data.message) || 'Có lỗi xảy ra.', false);
        btn.innerHTML = oldText;
      }
    } catch (err) {
      notify('Lỗi kết nối máy chủ.', false);
      btn.innerHTML = oldText;
    } finally {
      btn.disabled = false;
    }
  });

  // Change status
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-admin-change-status');
    if (!btn) return;
    const id = btn.dataset.id;
    const status = btn.dataset.status;
    btn.disabled = true;
    try {
      const formData = new FormData();
      formData.append('status', status);
      const res = await fetch('<?= e(base_url('admin/properties/changeStatus/')) ?>' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: formData
      });
      const data = await res.json();
      if (data && data.success) {
        const row = btn.closest('tr');
        if (row) row.remove();
        notify(data.message || 'Cập nhật trạng thái thành công.', true);
      } else {
        notify((data && data.message) || 'Có lỗi xảy ra.', false);
        btn.disabled = false;
      }
    } catch (err) {
      notify('Lỗi kết nối máy chủ.', false);
      btn.disabled = false;
    }
  });
});
</script>
