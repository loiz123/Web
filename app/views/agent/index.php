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
  <a class="btn btn-primary" href="<?= e(base_url('agent/create')) ?>">Đăng tin mới</a>
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
                <img src="<?= e(base_url($listing['cover_url'])) ?>" class="img-fluid rounded-start h-100 w-100 object-fit-cover" alt="<?= e($listing['title']) ?>">
              </div>
            <?php endif; ?>
            <div class="col">
              <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                  <div>
                    <h2 class="h5 card-title mb-1"><?= e($listing['title']) ?></h2>
                    <p class="text-muted small mb-2"><?= e($listing['type_name']) ?> · <?= $listing['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?> · <?= e(number_format((float)$listing['area'], 1, ',', '.')) ?> m²</p>
                  </div>
                  <span class="badge text-bg-secondary align-self-start"><?= e($statusLabels[$listing['status']] ?? $listing['status']) ?></span>
                </div>
                <p class="fw-semibold text-primary mb-3"><?= e(format_price($listing['price'])) ?></p>
                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('agent/edit/' . (int)$listing['id'])) ?>">Sửa tin</a>
                  <?php if ($listing['status'] === 'approved'): ?>
                    <form method="post" action="<?= e(base_url('agent/hide/' . (int)$listing['id'])) ?>">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Ẩn tin</button>
                    </form>
                  <?php elseif ($listing['status'] === 'hidden'): ?>
                    <form method="post" action="<?= e(base_url('agent/restore/' . (int)$listing['id'])) ?>">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-outline-success">Gửi duyệt lại</button>
                    </form>
                  <?php endif; ?>
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
