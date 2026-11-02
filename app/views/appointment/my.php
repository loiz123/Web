<?php
$labels = ['pending' => ['Chờ xác nhận', 'warning'], 'confirmed' => ['Đã xác nhận', 'success'], 'cancelled' => ['Đã hủy', 'secondary'], 'done' => ['Đã xem', 'info']];
?>
<h1 class="h4 mb-3">Lịch hẹn xem nhà của tôi</h1>
<?php if (!$items): ?>
  <p class="text-muted">Bạn chưa đặt lịch hẹn nào. <a href="<?= e(base_url('property/search')) ?>">Tìm bất động sản</a>.</p>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead><tr><th>Bất động sản</th><th>Thời gian hẹn</th><th>Ghi chú</th><th>Trạng thái</th><th class="text-end"></th></tr></thead>
    <tbody>
      <?php foreach ($items as $a): $l = $labels[$a['status']] ?? [$a['status'], 'light']; ?>
      <tr>
        <td><a href="<?= e(base_url('property/detail/' . (int)$a['property_id'])) ?>"><?= e($a['property_title']) ?></a></td>
        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($a['scheduled_at']))) ?></td>
        <td><?= e($a['note'] ?? '') ?></td>
        <td><span class="badge text-bg-<?= e($l[1]) ?>"><?= e($l[0]) ?></span></td>
        <td class="text-end">
          <?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?>
          <form method="post" action="<?= e(base_url('appointment/cancel/' . (int)$a['id'])) ?>" onsubmit="return confirm('Hủy lịch hẹn này?');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Hủy lịch</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
