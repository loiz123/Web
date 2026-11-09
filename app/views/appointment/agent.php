<?php
$labels = ['pending' => ['Chờ xác nhận', 'warning'], 'confirmed' => ['Đã xác nhận', 'success'], 'cancelled' => ['Đã hủy', 'secondary'], 'done' => ['Đã xem', 'info']];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0">Lịch hẹn khách đặt</h1>
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('agent')) ?>">Tin của tôi</a>
</div>
<?php if (!$items): ?>
  <p class="text-muted">Chưa có khách đặt lịch xem các tin của bạn.</p>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead><tr><th>Bất động sản</th><th>Khách hàng</th><th>Thời gian hẹn</th><th>Ghi chú</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
    <tbody>
      <?php foreach ($items as $a): $label = $labels[$a['status']] ?? [$a['status'], 'light']; ?>
      <tr>
        <td><a href="<?= e(base_url('property/detail/' . (int)$a['property_id'])) ?>"><?= e($a['property_title']) ?></a></td>
        <td><?= e($a['customer_name']) ?><?php if (!empty($a['customer_phone'])): ?><br><a href="<?= e('tel:' . $a['customer_phone']) ?>"><?= e($a['customer_phone']) ?></a><?php endif; ?></td>
        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($a['scheduled_at']))) ?></td>
        <td><?= e($a['note'] ?? '') ?></td>
        <td><span class="badge text-bg-<?= e($label[1]) ?>"><?= e($label[0]) ?></span></td>
        <td><div class="d-flex flex-wrap gap-1">
          <?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?>
            <?php $actions = $a['status'] === 'pending' ? ['confirmed' => 'Xác nhận', 'cancelled' => 'Hủy lịch'] : ['done' => 'Đã xem', 'cancelled' => 'Hủy lịch']; ?>
            <?php foreach ($actions as $status => $text): ?>
              <form method="post" action="<?= e(base_url('appointment/status/' . (int)$a['id'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
                <button class="btn btn-sm <?= $status === 'cancelled' ? 'btn-outline-danger' : 'btn-outline-primary' ?>"><?= e($text) ?></button>
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
