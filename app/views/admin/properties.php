<?php
$items = $result['items'] ?? [];
$total = (int)($result['total'] ?? 0);
$page = (int)($result['page'] ?? 1);
$pages = max(1, (int)ceil($total / $perPage));
$propertiesUrl = base_url('admin/properties');

if (!isset($tabCounts) || !is_array($tabCounts)) {
    $modModel = new PropertyModeration();
    $repModel = new Report();
    $stCounts = $modModel->countByStatus();
    $tabCounts = [
        'all'      => (int)array_sum($stCounts),
        'pending'  => (int)($stCounts['pending'] ?? 0),
        'approved' => (int)($stCounts['approved'] ?? 0),
        'hidden'   => (int)($stCounts['hidden'] ?? 0),
        'rejected' => (int)($stCounts['rejected'] ?? 0),
        'closed'   => (int)($stCounts['closed'] ?? 0),
        'reports'  => (int)($repModel->paginate('open', 1, 1)['total'] ?? 0),
    ];
}

$emptyMessages = [
    'all'      => 'Chưa có tin đăng nào trên hệ thống.',
    'pending'  => 'Hiện không có tin đăng nào đang chờ duyệt.',
    'approved' => 'Chưa có tin đăng nào ở trạng thái đã duyệt.',
    'hidden'   => 'Hiện không có tin đăng nào đang bị tạm ẩn.',
    'rejected' => 'Hiện không có tin đăng nào bị từ chối.',
    'closed'   => 'Hiện không có tin đăng nào đã đóng.',
    'reports'  => 'Không có báo cáo vi phạm nào cần xử lý.',
];

$statusBadges = [
    'pending'  => ['Chờ duyệt', 'bg-warning-subtle text-dark border-warning'],
    'approved' => ['Đã duyệt', 'bg-success-subtle text-success-emphasis border-success'],
    'hidden'   => ['Đã ẩn', 'bg-secondary-subtle text-secondary-emphasis border-secondary'],
    'rejected' => ['Bị từ chối', 'bg-danger-subtle text-danger-emphasis border-danger'],
    'closed'   => ['Đã đóng', 'bg-dark-subtle text-dark-emphasis border-dark'],
];
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1"><i class="bi bi-building-check text-primary me-2"></i>Quản lý tin đăng</h1>
    <p class="text-muted small mb-0">Quản lý toàn diện tin đăng, kiểm duyệt tin mới, khôi phục tin đã ẩn và xử lý phản ánh vi phạm</p>
  </div>
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin')) ?>"><i class="bi bi-arrow-left me-1"></i>Bảng điều khiển</a>
</div>

<ul class="nav nav-tabs mb-3 flex-wrap">
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'all' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=all&page=1') ?>">
      <i class="bi bi-collection text-primary"></i>
      <span>Tất cả</span>
      <span class="badge rounded-pill <?= $tab === 'all' ? 'text-bg-primary' : 'bg-primary-subtle text-primary-emphasis' ?>" data-tab-badge="all" data-count="<?= (int)($tabCounts['all'] ?? 0) ?>">(<?= (int)($tabCounts['all'] ?? 0) ?>)</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'pending' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=pending&page=1') ?>">
      <i class="bi bi-hourglass-split text-warning"></i>
      <span>Chờ duyệt</span>
      <span class="badge rounded-pill <?= $tab === 'pending' ? 'text-bg-warning text-dark' : 'bg-warning-subtle text-dark' ?>" data-tab-badge="pending" data-count="<?= (int)($tabCounts['pending'] ?? 0) ?>">(<?= (int)($tabCounts['pending'] ?? 0) ?>)</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'approved' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=approved&page=1') ?>">
      <i class="bi bi-check2-circle text-success"></i>
      <span>Đã duyệt</span>
      <span class="badge rounded-pill <?= $tab === 'approved' ? 'text-bg-success' : 'bg-success-subtle text-success-emphasis' ?>" data-tab-badge="approved" data-count="<?= (int)($tabCounts['approved'] ?? 0) ?>">(<?= (int)($tabCounts['approved'] ?? 0) ?>)</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'hidden' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=hidden&page=1') ?>">
      <i class="bi bi-eye-slash text-secondary"></i>
      <span>Đã ẩn</span>
      <span class="badge rounded-pill <?= $tab === 'hidden' ? 'text-bg-secondary' : 'bg-secondary-subtle text-secondary-emphasis' ?>" data-tab-badge="hidden" data-count="<?= (int)($tabCounts['hidden'] ?? 0) ?>">(<?= (int)($tabCounts['hidden'] ?? 0) ?>)</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'rejected' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=rejected&page=1') ?>">
      <i class="bi bi-x-circle text-danger"></i>
      <span>Bị từ chối</span>
      <span class="badge rounded-pill <?= $tab === 'rejected' ? 'text-bg-danger' : 'bg-danger-subtle text-danger-emphasis' ?>" data-tab-badge="rejected" data-count="<?= (int)($tabCounts['rejected'] ?? 0) ?>">(<?= (int)($tabCounts['rejected'] ?? 0) ?>)</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'closed' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=closed&page=1') ?>">
      <i class="bi bi-archive text-dark"></i>
      <span>Đã đóng</span>
      <span class="badge rounded-pill <?= $tab === 'closed' ? 'text-bg-dark' : 'bg-dark-subtle text-dark-emphasis' ?>" data-tab-badge="closed" data-count="<?= (int)($tabCounts['closed'] ?? 0) ?>">(<?= (int)($tabCounts['closed'] ?? 0) ?>)</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-1 <?= $tab === 'reports' ? 'active fw-semibold' : '' ?>" href="<?= e($propertiesUrl . '?tab=reports&page=1') ?>">
      <i class="bi bi-flag text-danger"></i>
      <span>Báo cáo vi phạm</span>
      <span class="badge rounded-pill <?= $tab === 'reports' ? 'text-bg-danger' : 'bg-danger-subtle text-danger-emphasis' ?>" data-tab-badge="reports" data-count="<?= (int)($tabCounts['reports'] ?? 0) ?>">(<?= (int)($tabCounts['reports'] ?? 0) ?>)</span>
    </a>
  </li>
</ul>

<div id="mod-alert" class="alert d-none" role="alert"></div>

<?php if (!$items): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body py-5 text-center text-muted">
      <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
      <p class="mb-0"><?= e($emptyMessages[$tab] ?? 'Không có mục nào cần xử lý.') ?></p>
    </div>
  </div>
<?php else: ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="mod-table" data-tab="<?= e($tab) ?>">
      <?php if ($tab !== 'reports'): ?>
      <thead class="table-light">
        <tr>
          <th class="ps-3 text-nowrap" style="width: 56px;">STT</th>
          <th style="min-width: 220px; max-width: 360px;">Tiêu đề</th>
          <th class="text-nowrap">Môi giới</th>
          <th class="text-nowrap">Loại</th>
          <th class="text-end text-nowrap admin-price-col col-price-nowrap" style="white-space: nowrap !important; min-width: 170px; width: 170px;">Giá</th>
          <?php if ($tab === 'all'): ?>
            <th class="text-center text-nowrap">Trạng thái</th>
          <?php endif; ?>
          <th class="text-center text-nowrap">Xác thực</th>
          <th class="text-nowrap">Ngày đăng</th>
          <th class="text-end text-nowrap pe-3">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $p): ?>
        <?php
          $rowStatus = $p['status'] ?? ($tab !== 'all' ? $tab : 'pending');
          $stt = ($page - 1) * $perPage + (int)$idx + 1;
        ?>
        <tr data-id="<?= (int)$p['id'] ?>" data-status="<?= e($rowStatus) ?>">
          <td class="ps-3 text-muted text-nowrap fw-medium"><?= $stt ?></td>
          <td style="min-width: 220px; max-width: 360px;">
            <a href="<?= e(base_url('property/detail/' . (int)$p['id'])) ?>" target="_blank" rel="noopener" class="fw-semibold text-decoration-none">
              <?= e($p['title']) ?>
            </a>
            <span class="text-muted small ms-1 text-nowrap">(#<?= (int)$p['id'] ?>)</span>
            <?php if ($tab !== 'all'): ?>
              <?php if ($rowStatus === 'hidden'): ?>
                <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Đang ẩn</span>
              <?php elseif ($rowStatus === 'rejected' && !empty($p['reject_reason'])): ?>
                <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>Lý do: <?= e($p['reject_reason']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <?php if ($rowStatus === 'rejected' && !empty($p['reject_reason'])): ?>
                <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>Lý do: <?= e($p['reject_reason']) ?></div>
              <?php endif; ?>
            <?php endif; ?>
          </td>
          <td class="text-nowrap"><?= e($p['agent_name']) ?></td>
          <td class="text-nowrap">
            <span class="badge <?= $p['listing_type'] === 'rent' ? 'bg-info-subtle text-info-emphasis' : 'bg-primary-subtle text-primary-emphasis' ?>">
              <?= $p['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?>
            </span>
          </td>
          <td class="text-end fw-semibold text-nowrap admin-price-col col-price-nowrap" style="white-space: nowrap !important; min-width: 170px; width: 170px; font-variant-numeric: tabular-nums;">
            <span class="d-inline-block text-nowrap" style="white-space: nowrap !important; font-variant-numeric: tabular-nums;"><?= e(number_format((float)$p['price'], 0, ',', '.')) ?>&nbsp;đ</span>
          </td>
          <?php if ($tab === 'all'): ?>
            <td class="text-center text-nowrap">
              <?php if (isset($statusBadges[$rowStatus])): ?>
                <span class="badge border <?= $statusBadges[$rowStatus][1] ?>"><?= $statusBadges[$rowStatus][0] ?></span>
              <?php else: ?>
                <span class="badge bg-light text-dark border"><?= e($rowStatus) ?></span>
              <?php endif; ?>
            </td>
          <?php endif; ?>
          <td class="text-center text-nowrap">
            <button type="button" class="btn btn-sm btn-verify-toggle <?= !empty($p['is_verified']) ? 'btn-outline-warning text-dark border-warning' : 'btn-outline-success' ?>" data-id="<?= (int)$p['id'] ?>" title="Bật/Tắt huy hiệu xác thực">
              <?= !empty($p['is_verified']) ? '🛡️ Đã xác thực' : '🛡️ Cấp xác thực' ?>
            </button>
          </td>
          <td class="text-nowrap small text-muted"><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
          <td class="text-end text-nowrap pe-3">
            <?php if ($rowStatus === 'pending'): ?>
              <button type="button" class="btn btn-sm btn-success" data-act="approve" title="Phê duyệt tin"><i class="bi bi-check2 me-1"></i>Duyệt</button>
              <button type="button" class="btn btn-sm btn-warning text-dark" data-act="reject" title="Từ chối tin"><i class="bi bi-x-circle me-1"></i>Từ chối</button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" title="Xóa vĩnh viễn tin đăng"><i class="bi bi-trash me-1"></i>Xóa</button>
            <?php elseif ($rowStatus === 'hidden'): ?>
              <button type="button" class="btn btn-sm btn-outline-success btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="approved" title="Khôi phục và hiển thị lại tin đăng trên hệ thống">
                <i class="bi bi-eye me-1"></i>Hiện lại tin
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" title="Xóa vĩnh viễn tin đăng">
                <i class="bi bi-trash me-1"></i>Xóa
              </button>
            <?php elseif ($rowStatus === 'rejected'): ?>
              <button type="button" class="btn btn-sm btn-outline-success btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="approved" title="Duyệt lại và hiển thị tin đăng trên hệ thống">
                <i class="bi bi-check2-circle me-1"></i>Duyệt lại
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" title="Xóa vĩnh viễn tin đăng">
                <i class="bi bi-trash me-1"></i>Xóa
              </button>
            <?php elseif ($rowStatus === 'closed'): ?>
              <button type="button" class="btn btn-sm btn-outline-success btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="approved" title="Mở lại và hiển thị tin đăng trên hệ thống">
                <i class="bi bi-unlock me-1"></i>Mở lại tin
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" title="Xóa vĩnh viễn tin đăng">
                <i class="bi bi-trash me-1"></i>Xóa
              </button>
            <?php elseif ($rowStatus === 'approved'): ?>
              <button type="button" class="btn btn-sm btn-outline-secondary text-dark fw-semibold btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="hidden" style="border-color: #d97706; background-color: #fffbeb;" title="Tạm ẩn tin đăng khỏi trang tìm kiếm">
                <i class="bi bi-eye-slash text-warning me-1"></i>Ẩn tin
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" title="Xóa vĩnh viễn tin đăng">
                <i class="bi bi-trash me-1"></i>Xóa
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-danger btn-admin-change-status" data-id="<?= (int)$p['id'] ?>" data-status="deleted" title="Xóa vĩnh viễn tin đăng">
                <i class="bi bi-trash me-1"></i>Xóa
              </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php else: ?>
      <thead class="table-light">
        <tr>
          <th class="ps-3">#</th>
          <th>Tin bị báo cáo</th>
          <th>Người báo cáo</th>
          <th>Lý do</th>
          <th>Ngày</th>
          <th class="text-end pe-3">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $r): ?>
        <tr data-id="<?= (int)$r['id'] ?>">
          <td class="ps-3 text-muted">#<?= (int)$r['id'] ?></td>
          <td>
            <a href="<?= e(base_url('property/detail/' . (int)$r['property_id'])) ?>" target="_blank" rel="noopener" class="fw-semibold text-decoration-none"><?= e($r['property_title']) ?></a>
            <span class="badge text-bg-light ms-1"><?= e($r['property_status']) ?></span>
          </td>
          <td><?= e($r['reporter_name']) ?></td>
          <td><?= e($r['reason']) ?></td>
          <td class="text-nowrap small text-muted"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
          <td class="text-end text-nowrap pe-3">
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
  const modTable = document.getElementById('mod-table');
  const currentTab = modTable ? modTable.dataset.tab : '<?= e($tab) ?>';

  function notify(msg, ok) {
    if (!alertBox) return;
    alertBox.className = 'alert alert-' + (ok ? 'success' : 'danger');
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  function adjustTabBadge(tabKey, delta) {
    const badge = document.querySelector('[data-tab-badge="' + tabKey + '"]');
    if (!badge) return;
    const current = parseInt(badge.dataset.count || badge.textContent.replace(/[^\d]/g, ''), 10) || 0;
    const next = Math.max(0, current + delta);
    badge.dataset.count = String(next);
    badge.textContent = '(' + next + ')';
  }

  function updateTotalAfterRemove() {
    const totalEl = document.getElementById('mod-total');
    if (totalEl) {
      const nextTotal = Math.max(0, (parseInt(totalEl.dataset.total, 10) || 0) - 1);
      totalEl.dataset.total = String(nextTotal);
      totalEl.textContent = 'Tổng: ' + nextTotal + ' mục.';
    }
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
          btn.className = 'btn btn-sm btn-verify-toggle btn-outline-warning text-dark border-warning';
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

  // Thay đổi trạng thái: Ẩn tin / Hiện lại tin / Duyệt lại / Mở lại tin / Xóa vĩnh viễn
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-admin-change-status');
    if (!btn) return;
    const id = btn.dataset.id;
    const status = btn.dataset.status;

    if (status === 'deleted') {
      if (!confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tin đăng #' + id + '? Thao tác này không thể hoàn tác.')) {
        return;
      }
    }

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
        if (currentTab === 'all' && status !== 'deleted') {
          window.location.reload();
          return;
        }
        const row = btn.closest('tr');
        const rowOldStatus = row ? (row.dataset.status || currentTab) : currentTab;
        if (row) row.remove();
        updateTotalAfterRemove();
        adjustTabBadge(currentTab, -1);
        if (status === 'deleted') {
          if (currentTab !== 'all') {
            adjustTabBadge('all', -1);
          } else if (rowOldStatus && rowOldStatus !== 'all') {
            adjustTabBadge(rowOldStatus, -1);
          }
        }
        if (status === 'hidden') {
          adjustTabBadge('hidden', 1);
        } else if (status === 'approved') {
          adjustTabBadge('approved', 1);
        }
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
