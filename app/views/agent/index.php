<?php
$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Bị từ chối',
    'hidden' => 'Đã ẩn',
    'closed' => 'Đã đóng',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <h1 class="h3 mb-0">Tin của tôi</h1>
  <div class="d-flex gap-2">
    <?php if (Auth::hasRole('agent')): ?><a class="btn btn-outline-primary" href="<?= e(base_url('appointment/agent')) ?>">Lịch hẹn khách đặt</a><?php endif; ?>
    <a class="btn btn-primary" href="<?= e(base_url('agent/create')) ?>">Đăng tin mới</a>
  </div>
</div>

<form method="get" action="<?= e(base_url('agent')) ?>" class="row g-2 align-items-end mb-4">
  <div class="col-12 col-sm-6 col-md-4">
    <label for="status-filter" class="form-label">Lọc theo trạng thái</label>
    <select id="status-filter" name="status" class="form-select">
      <option value="">Tất cả trạng thái</option>
      <?php foreach ($statusLabels as $status => $label): ?>
        <option value="<?= e($status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button type="submit" class="btn btn-outline-primary">Lọc</button></div>
</form>

<?php if (!$listings): ?>
  <div class="alert alert-info mb-0">Chưa có tin đăng nào phù hợp.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($listings as $listing): ?>
      <div class="col-12">
        <article class="card">
          <div class="row g-0">
            <?php if ($listing['cover_url']): ?>
              <div class="col-12 col-md-3">
                <img src="<?= e(image_url($listing['cover_url'])) ?>" class="img-fluid rounded-start h-100 w-100 object-fit-cover" alt="<?= e($listing['title']) ?>">
              </div>
            <?php endif; ?>
            <div class="col">
              <div class="card-body" data-listing-card>
                <div class="d-flex flex-wrap justify-content-between gap-2">
                  <div>
                    <h2 class="h5 card-title mb-1"><?= e($listing['title']) ?></h2>
                    <p class="text-muted small mb-2">
                      <?= e($listing['type_name']) ?> · <?= $listing['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?> · <?= e(number_format((float)$listing['area'], 1, ',', '.')) ?> m²
                      <?php if (!empty($listing['expired_at'])): ?>
                        <?php
                          $expTs = strtotime((string)$listing['expired_at']);
                          $isExpired = $expTs !== false && $expTs < time();
                        ?>
                        · <span class="<?= $isExpired ? 'text-danger fw-semibold' : 'text-success' ?>">
                            <i class="bi bi-clock-history me-1"></i><?= $isExpired ? 'Đã hết hạn: ' : 'Hết hạn: ' ?><?= date('d/m/Y', $expTs) ?>
                          </span>
                      <?php endif; ?>
                    </p>
                  </div>
                  <span class="badge text-bg-secondary align-self-start" data-status-badge><?= e($statusLabels[$listing['status']] ?? $listing['status']) ?></span>
                </div>
                <p class="fw-semibold text-primary mb-3"><?= e(format_price($listing['price'])) ?></p>
                <?php if ($listing['status'] === 'rejected' && !empty($listing['reject_reason'])): ?>
                  <div class="alert alert-warning py-2 mb-3" data-reject-reason>
                    <strong>Lý do từ chối:</strong> <?= e($listing['reject_reason']) ?>
                  </div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('agent/edit/' . (int)$listing['id'])) ?>">Sửa tin</a>
                  <form method="post" action="<?= e(base_url('agent/renew/' . (int)$listing['id'])) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-info" title="Gia hạn thời gian hiển thị tin đăng thêm 30 ngày">
                      <i class="bi bi-arrow-clockwise me-1"></i>Gia hạn tin (+30 ngày)
                    </button>
                  </form>
                  <div class="d-flex flex-wrap gap-2" data-status-actions>
                  <?php if ($listing['status'] === 'approved'): ?>
                    <form method="post" action="<?= e(base_url('agent/status/' . (int)$listing['id'])) ?>" data-status-path="<?= e('agent/status/' . (int)$listing['id']) ?>" data-status-form>
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="hidden">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Ẩn tin</button>
                    </form>
                  <?php elseif ($listing['status'] === 'hidden'): ?>
                    <form method="post" action="<?= e(base_url('agent/status/' . (int)$listing['id'])) ?>" data-status-path="<?= e('agent/status/' . (int)$listing['id']) ?>" data-status-form>
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="pending">
                      <button type="submit" class="btn btn-sm btn-outline-success">Gửi duyệt lại</button>
                    </form>
                  <?php elseif ($listing['status'] === 'closed'): ?>
                    <form method="post" action="<?= e(base_url('agent/status/' . (int)$listing['id'])) ?>" data-status-path="<?= e('agent/status/' . (int)$listing['id']) ?>" data-status-form>
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="pending">
                      <button type="submit" class="btn btn-sm btn-outline-success">Mở lại tin</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($listing['status'] === 'approved'): ?>
                    <form method="post" action="<?= e(base_url('agent/status/' . (int)$listing['id'])) ?>" data-status-path="<?= e('agent/status/' . (int)$listing['id']) ?>" data-status-form>
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="closed">
                      <button type="submit" class="btn btn-sm btn-outline-warning">Đóng tin</button>
                    </form>
                  <?php endif; ?>
                  </div>
                  <form method="post" action="<?= e(base_url('agent/delete/' . (int)$listing['id'])) ?>" onsubmit="return confirm('Bạn có chắc muốn xóa tin đăng này?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa tin</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
