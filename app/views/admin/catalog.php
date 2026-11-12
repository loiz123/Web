<?php
/** @var array $tabs @var string $tab @var array $rows @var array $provinces @var int $fProvince */
$cfg = $tabs[$tab];
$isWard = $tab === 'wards';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0">Quản lý danh mục</h1>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#catalogModal" data-mode="add">+ Thêm <?= e($cfg['noun']) ?></button>
</div>

<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto">
  <?php foreach ($tabs as $key => $t): ?>
    <li class="nav-item"><a class="nav-link text-nowrap <?= $key === $tab ? 'active' : '' ?>" href="<?= e(base_url('admin/catalog?tab=' . $key)) ?>"><?= e($t['label']) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if ($isWard): ?>
  <form class="row g-2 mb-3" method="get" action="<?= e(base_url('admin/catalog')) ?>">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="col-sm-5 col-md-4">
      <select name="province" id="f-province" class="form-select">
        <option value="">Tất cả Tỉnh/Thành phố</option>
        <?php foreach ($provinces as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $fProvince === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-2 d-grid"><button class="btn btn-outline-secondary">Lọc</button></div>
  </form>
<?php endif; ?>

<div class="table-responsive">
  <table class="table table-hover align-middle bg-white border">
    <thead class="table-light">
      <tr>
        <th style="width:70px">ID</th><th>Tên</th>
        <?php if ($isWard): ?><th>Tỉnh/Thành phố</th><?php endif; ?>
        <?php if ($tab === 'provinces'): ?><th class="text-center">Số phường/xã</th><?php endif; ?>
        <th class="text-center">Số tin dùng</th><th class="text-end" style="width:170px">Thao tác</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="<?= $isWard || $tab === 'provinces' ? 5 : 4 ?>" class="text-center text-muted py-4">Chưa có dữ liệu.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td class="fw-semibold"><?= e($r['name']) ?></td>
        <?php if ($isWard): ?><td><?= e($r['parent_name']) ?></td><?php endif; ?>
        <?php if ($tab === 'provinces'): ?><td class="text-center"><?= (int)$r['extra'] ?></td><?php endif; ?>
        <td class="text-center"><?= (int)$r['used'] ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#catalogModal" data-mode="edit"
                  data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['name']) ?>"
                  data-parent="<?= (int)($r['parent_id'] ?? 0) ?>">Sửa</button>
          <form method="post" action="<?= e(base_url('admin/catalog/delete')) ?>" class="d-inline js-confirm-delete"
                data-confirm-name="<?= e($r['name']) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="f_province" value="<?= (int)$fProvince ?>">
            <button class="btn btn-sm btn-outline-danger">Xóa</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if ($isWard && count($rows) >= 300): ?><p class="small text-muted">Chỉ hiển thị 300 dòng đầu, hãy lọc theo Tỉnh/Thành phố để xem thêm.</p><?php endif; ?>

<!-- Modal thêm/sửa dùng chung -->
<div class="modal fade" id="catalogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="catalogForm" method="post" action="<?= e(base_url('admin/catalog/save')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="<?= e($tab) ?>">
      <input type="hidden" name="item_id" value="">
      <input type="hidden" name="f_province" value="<?= (int)$fProvince ?>">
      <div class="modal-header"><h5 class="modal-title" id="catalogModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php if ($isWard): ?>
          <div class="mb-3"><label class="form-label">Tỉnh/Thành phố</label>
            <select name="province_id" id="m-province" class="form-select" required>
              <option value="">Chọn Tỉnh/Thành phố</option>
              <?php foreach ($provinces as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select></div>
        <?php endif; ?>
        <div class="mb-3"><label class="form-label">Tên <?= e($cfg['noun']) ?></label>
          <input name="name" class="form-control" maxlength="<?= (int)$cfg['max'] ?>" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
    </form>
  </div>
</div>

<script>
// Đổ dữ liệu vào modal khi bấm Thêm/Sửa (listener gắn lên modal nên chạy được dù Bootstrap nạp sau)
document.getElementById('catalogModal').addEventListener('show.bs.modal', function _callback(ev){
  const b = ev.relatedTarget, f = document.getElementById('catalogForm');
  const edit = b && b.dataset.mode === 'edit';
  f.elements['item_id'].value = edit ? b.dataset.id : '';
  f.elements['name'].value = edit ? b.dataset.name : '';
  document.getElementById('catalogModalTitle').textContent = (edit ? 'Sửa ' : 'Thêm ') + <?= json_for_script($cfg['noun']) ?>;
  const prov = document.getElementById('m-province');
  if (prov) {
    prov.value = edit ? (b.dataset.parent || '') : (<?= (int)$fProvince ?> ? String(<?= (int)$fProvince ?>) : '');
  }
});

document.querySelectorAll('.js-confirm-delete').forEach(function _callback2(form){
  form.addEventListener('submit', function _callback3(ev){
    if (!window.confirm('Xóa “' + form.dataset.confirmName + '”?')) ev.preventDefault();
  });
});</script>
