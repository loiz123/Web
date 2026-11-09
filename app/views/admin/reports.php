<?php
/**
 * View Quản lý báo cáo vi phạm dành cho Quản trị viên
 * @var array $reports
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var int $perPage
 * @var string $status
 * @var string $search
 * @var array $counts
 */
$url = fn(string $st, int $p, string $q = '') => base_url('admin/reports') . '?status=' . urlencode($st) . '&page=' . $p . ($q !== '' ? '&q=' . urlencode($q) : '');

$statusMap = [
    'open'      => ['Chờ xử lý', 'warning', 'bi-hourglass-split'],
    'pending'   => ['Chờ xử lý', 'warning', 'bi-hourglass-split'],
    'resolved'  => ['Đã xử lý (Đã ẩn tin)', 'success', 'bi-check-circle-fill'],
    'dismissed' => ['Đã bác bỏ', 'secondary', 'bi-x-circle-fill'],
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h1 class="h4 fw-bold mb-1"><i class="bi bi-shield-exclamation text-danger me-2"></i>Quản lý báo cáo vi phạm</h1>
    <p class="text-muted small mb-0">Tiếp nhận, kiểm tra và xử lý các phản ánh vi phạm quy định đăng tin từ người dùng</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('admin/properties')) ?>">
      <i class="bi bi-card-checklist me-1"></i>Duyệt tin đăng
    </a>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin')) ?>">
      <i class="bi bi-speedometer2 me-1"></i>Bảng điều khiển
    </a>
  </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body d-flex align-items-center">
        <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3">
          <i class="bi bi-flag-fill fs-4"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total'] ?? 0) ?></div>
          <div class="text-muted small">Tổng số báo cáo</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body d-flex align-items-center">
        <div class="bg-warning-subtle text-warning p-3 rounded-circle me-3">
          <i class="bi bi-clock-history fs-4"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold text-dark"><?= (int)($counts['pending'] ?? 0) ?></div>
          <div class="text-muted small">Chờ xử lý</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body d-flex align-items-center">
        <div class="bg-success-subtle text-success p-3 rounded-circle me-3">
          <i class="bi bi-eye-slash-fill fs-4"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold text-dark"><?= (int)($counts['resolved'] ?? 0) ?></div>
          <div class="text-muted small">Đã duyệt vi phạm</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body d-flex align-items-center">
        <div class="bg-secondary-subtle text-secondary p-3 rounded-circle me-3">
          <i class="bi bi-dash-circle fs-4"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold text-dark"><?= (int)($counts['dismissed'] ?? 0) ?></div>
          <div class="text-muted small">Đã bác bỏ</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bộ lọc và Tìm kiếm -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-lg-7">
        <ul class="nav nav-pills flex-wrap gap-1">
          <li class="nav-item">
            <a class="nav-link btn-sm py-1 px-3 <?= $status === 'all' ? 'active' : '' ?>" href="<?= e($url('all', 1, $search)) ?>">
              Tất cả <span class="badge bg-light text-dark ms-1"><?= (int)($counts['total'] ?? 0) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm py-1 px-3 <?= $status === 'pending' ? 'active' : '' ?>" href="<?= e($url('pending', 1, $search)) ?>">
              Chờ xử lý <span class="badge bg-warning-subtle text-dark ms-1"><?= (int)($counts['pending'] ?? 0) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm py-1 px-3 <?= $status === 'resolved' ? 'active' : '' ?>" href="<?= e($url('resolved', 1, $search)) ?>">
              Đã xử lý <span class="badge bg-success-subtle text-dark ms-1"><?= (int)($counts['resolved'] ?? 0) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm py-1 px-3 <?= $status === 'dismissed' ? 'active' : '' ?>" href="<?= e($url('dismissed', 1, $search)) ?>">
              Đã bác bỏ <span class="badge bg-secondary-subtle text-dark ms-1"><?= (int)($counts['dismissed'] ?? 0) ?></span>
            </a>
          </li>
        </ul>
      </div>
      <div class="col-12 col-lg-5">
        <form method="get" action="<?= e(base_url('admin/reports')) ?>" class="d-flex gap-2">
          <input type="hidden" name="status" value="<?= e($status) ?>">
          <div class="input-group input-group-sm">
            <input type="search" name="q" class="form-control" placeholder="Tìm theo tiêu đề, lý do, người gửi..." value="<?= e($search) ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
          </div>
          <?php if ($search !== ''): ?>
            <a href="<?= e($url($status, 1)) ?>" class="btn btn-sm btn-outline-secondary text-nowrap">Xóa lọc</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Danh sách báo cáo vi phạm -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-secondary small">
        <tr>
          <th style="width: 50px;" class="text-center">#</th>
          <th style="min-width: 260px;">Tin đăng bị báo cáo</th>
          <th style="min-width: 220px;">Lý do & Nội dung</th>
          <th style="min-width: 180px;">Người gửi</th>
          <th style="width: 140px;">Trạng thái</th>
          <th style="width: 180px;" class="text-end">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reports)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
              Không tìm thấy báo cáo vi phạm nào phù hợp với bộ lọc.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($reports as $r): ?>
            <?php
              $rStatus = $r['status'] ?? 'pending';
              $badgeInfo = $statusMap[$rStatus] ?? ['Không xác định', 'light', 'bi-question-circle'];
              $isPending = in_array($rStatus, ['open', 'pending'], true);
              $propStatus = $r['property_status'] ?? '';
            ?>
            <tr id="report-row-<?= (int)$r['id'] ?>">
              <td class="text-center text-muted fw-semibold"><?= (int)$r['id'] ?></td>
              <td>
                <div class="fw-bold text-dark mb-1">
                  <a href="<?= e(base_url('property/detail/' . (int)$r['property_id'])) ?>" target="_blank" rel="noopener" class="text-decoration-none text-dark hover-primary" title="Xem chi tiết tin đăng">
                    <?= e($r['property_title'] ?? ('Tin đăng #' . $r['property_id'])) ?>
                    <i class="bi bi-box-arrow-up-right small text-muted ms-1"></i>
                  </a>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                  <?php if ($propStatus === 'approved'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Đang hiển thị</span>
                  <?php elseif ($propStatus === 'hidden'): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Đã ẩn</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary"><?= e($propStatus) ?></span>
                  <?php endif; ?>
                  <span>•</span>
                  <span>Môi giới: <strong><?= e($r['agent_name'] ?? 'Môi giới') ?></strong></span>
                </div>
              </td>
              <td>
                <div class="fw-semibold text-danger mb-1">
                  <i class="bi bi-exclamation-triangle-fill me-1 small"></i><?= e($r['reason']) ?>
                </div>
                <?php if (!empty($r['description'])): ?>
                  <div class="small text-muted fst-italic bg-light p-2 rounded border">
                    <?= nl2br(e($r['description'])) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold small text-dark"><?= e($r['reporter_name'] ?? 'Khách hàng') ?></div>
                <div class="small text-muted"><?= e($r['reporter_email'] ?? '') ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">
                  <i class="bi bi-clock me-1"></i><?= date('H:i d/m/Y', strtotime((string)$r['created_at'])) ?>
                </div>
              </td>
              <td>
                <span class="badge bg-<?= e($badgeInfo[1]) ?>-subtle text-<?= e($badgeInfo[1]) ?> border border-<?= e($badgeInfo[1]) ?>-subtle px-2 py-1 rounded-pill small">
                  <i class="bi <?= e($badgeInfo[2]) ?> me-1"></i><?= e($badgeInfo[0]) ?>
                </span>
                <?php if (!empty($r['admin_notes'])): ?>
                  <div class="small text-muted mt-1" style="font-size: 0.75rem;" title="Ghi chú quản trị viên">
                    <i class="bi bi-pencil-square me-1"></i><?= e($r['admin_notes']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <a href="<?= e(base_url('property/detail/' . (int)$r['property_id'])) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light border" title="Xem tin đăng">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($isPending): ?>
                    <button type="button" class="btn btn-sm btn-danger text-nowrap" 
                            onclick="openResolveModal(<?= (int)$r['id'] ?>, 'resolve', '<?= e(addslashes($r['property_title'] ?? '')) ?>', '<?= e(addslashes($r['reason'] ?? '')) ?>')"
                            title="Xác nhận vi phạm và ẩn tin đăng">
                      <i class="bi bi-eye-slash me-1"></i>Ẩn tin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap"
                            onclick="openResolveModal(<?= (int)$r['id'] ?>, 'dismiss', '<?= e(addslashes($r['property_title'] ?? '')) ?>', '<?= e(addslashes($r['reason'] ?? '')) ?>')"
                            title="Bác bỏ báo cáo nếu không vi phạm">
                      <i class="bi bi-x-lg me-1"></i>Bác bỏ
                    </button>
                  <?php else: ?>
                    <span class="text-muted small fst-italic px-2 py-1">Đã xử lý</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Phân trang -->
  <?php if ($pages > 1): ?>
    <div class="card-footer bg-white border-top p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="small text-muted">
        Hiển thị <?= count($reports) ?> trên tổng số <?= (int)$total ?> báo cáo vi phạm
      </div>
      <nav aria-label="Phân trang báo cáo">
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($url($status, $page - 1, $search)) ?>">&laquo;</a>
          </li>
          <?php
            $pStart = max(1, $page - 2);
            $pEnd = min($pages, $page + 2);
            for ($i = $pStart; $i <= $pEnd; $i++):
          ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="<?= e($url($status, $i, $search)) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($url($status, $page + 1, $search)) ?>">&raquo;</a>
          </li>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<!-- Modal xác nhận Xử lý báo cáo -->
<div class="modal fade" id="resolveReportModal" tabindex="-1" aria-labelledby="resolveReportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="post" action="<?= e(base_url('admin/resolveReport')) ?>" id="resolveReportForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="modalReportId" value="0">
        <input type="hidden" name="action" id="modalReportAction" value="resolve">

        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold" id="resolveReportModalLabel">Xác nhận xử lý báo cáo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body py-3">
          <div class="alert d-flex align-items-center mb-3" id="modalAlertBox" role="alert">
            <i class="bi fs-4 me-2" id="modalAlertIcon"></i>
            <div id="modalAlertText"></div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Tin đăng bị phản ánh:</label>
            <div class="fw-bold text-dark" id="modalPropertyTitle"></div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Lý do vi phạm:</label>
            <div class="text-danger small fw-semibold" id="modalReportReason"></div>
          </div>

          <div class="mb-3">
            <label for="modalAdminNotes" class="form-label small fw-semibold text-secondary">Ghi chú của quản trị viên (tùy chọn):</label>
            <textarea name="admin_notes" id="modalAdminNotes" class="form-control" rows="3" placeholder="Nhập ghi chú hoặc lý do gửi kèm thông báo..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="submit" class="btn btn-sm px-4 fw-semibold" id="modalSubmitBtn">Xác nhận</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openResolveModal(reportId, action, title, reason) {
  const modalEl = document.getElementById('resolveReportModal');
  const modal = new bootstrap.Modal(modalEl);

  document.getElementById('modalReportId').value = reportId;
  document.getElementById('modalReportAction').value = action;
  document.getElementById('modalPropertyTitle').textContent = title;
  document.getElementById('modalReportReason').textContent = reason;
  document.getElementById('modalAdminNotes').value = '';

  const labelEl = document.getElementById('resolveReportModalLabel');
  const alertBox = document.getElementById('modalAlertBox');
  const alertIcon = document.getElementById('modalAlertIcon');
  const alertText = document.getElementById('modalAlertText');
  const submitBtn = document.getElementById('modalSubmitBtn');

  if (action === 'resolve') {
    labelEl.innerHTML = '<span class="text-danger"><i class="bi bi-eye-slash-fill me-2"></i>Duyệt vi phạm & Ẩn tin đăng</span>';
    alertBox.className = 'alert alert-danger d-flex align-items-center mb-3';
    alertIcon.className = 'bi bi-exclamation-octagon-fill fs-4 me-2 text-danger';
    alertText.innerHTML = 'Thao tác này sẽ <strong>tạm ẩn tin đăng</strong> khỏi hệ thống và tự động gửi thông báo đến <strong>Môi giới đăng tin</strong> và <strong>Người gửi báo cáo</strong>.';
    submitBtn.className = 'btn btn-sm btn-danger px-4 fw-semibold';
    submitBtn.innerHTML = '<i class="bi bi-eye-slash me-1"></i>Xác nhận Ẩn tin';
  } else {
    labelEl.innerHTML = '<span class="text-secondary"><i class="bi bi-x-circle-fill me-2"></i>Bác bỏ báo cáo vi phạm</span>';
    alertBox.className = 'alert alert-secondary d-flex align-items-center mb-3';
    alertIcon.className = 'bi bi-info-circle-fill fs-4 me-2 text-secondary';
    alertText.innerHTML = 'Báo cáo sẽ được chuyển sang trạng thái <strong>Đã bác bỏ</strong>. Tin đăng vẫn được giữ nguyên và thông báo kết quả sẽ được gửi tới người báo cáo.';
    submitBtn.className = 'btn btn-sm btn-secondary px-4 fw-semibold';
    submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Xác nhận Bác bỏ';
  }

  modal.show();
}
</script>
