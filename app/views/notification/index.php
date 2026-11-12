<?php /** @var array $notifications @var int $unreadCount */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-bold mb-1"><i class="bi bi-bell-fill text-primary me-2"></i>Thông báo của tôi</h1>
    <p class="text-muted small mb-0">
      Bạn có <strong class="text-danger"><?= (int)$unreadCount ?></strong> thông báo chưa đọc
    </p>
  </div>
  <?php if ($unreadCount > 0): ?>
    <form method="post" action="<?= e(base_url('notification/readAll')) ?>" class="m-0">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
        <i class="bi bi-check2-all me-1"></i>Đánh dấu tất cả đã đọc
      </button>
    </form>
  <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
  <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
    <div class="fs-1 mb-2">🔔</div>
    <h5 class="fw-semibold text-dark">Chưa có thông báo nào</h5>
    <p class="text-muted small mb-0">Khi có lịch hẹn mới, cập nhật trạng thái lịch hẹn hoặc kết quả duyệt tin đăng, hệ thống sẽ thông báo ngay tại đây.</p>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="list-group list-group-flush">
      <?php foreach ($notifications as $n): ?>
        <?php $isUnread = empty($n['is_read']); ?>
        <div class="list-group-item list-group-item-action p-3 <?= $isUnread ? 'bg-primary-subtle bg-opacity-10' : '' ?>">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isUnread ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>" style="width: 40px; height: 40px;">
                <i class="bi <?= $isUnread ? 'bi-bell-fill' : 'bi-bell' ?>"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h6 class="mb-0 <?= $isUnread ? 'fw-bold text-dark' : 'fw-semibold text-secondary' ?>">
                    <?= e($n['title']) ?>
                  </h6>
                  <?php if ($isUnread): ?>
                    <span class="badge rounded-pill bg-danger" style="font-size: 0.65rem;">Mới</span>
                  <?php endif; ?>
                </div>
                <p class="small text-secondary mb-1"><?= e($n['message']) ?></p>
                <span class="text-muted" style="font-size: 0.75rem;">
                  <i class="bi bi-clock me-1"></i><?= date('H:i - d/m/Y', strtotime((string)$n['created_at'])) ?>
                </span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <a href="<?= e(base_url('notification/read/' . (int)$n['id'])) ?>" class="btn btn-sm <?= $isUnread ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <?= !empty($n['link']) ? 'Xem chi tiết' : ($isUnread ? 'Đánh dấu đã đọc' : 'Đã xem') ?>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
