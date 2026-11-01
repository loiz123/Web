<?php
/** @var array $tabs @var string $tab @var array $rows @var array $provinces @var int $fProvince @var int $fDistrict */
$cfg = $tabs[$tab];
$isDistrict = $tab === 'districts';
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

<?php if ($isDistrict || $isWard): ?>
  <form class="row g-2 mb-3" method="get" action="<?= e(base_url('admin/catalog')) ?>">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="col-sm-4">
      <select name="province" id="f-province" class="form-select" <?= $isWard ? 'data-target="#f-district" data-level="districts"' : '' ?>>
        <option value="">Tất cả Tỉnh/Thành</option>
        <?php foreach ($provinces as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $fProvince === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if ($isWard): ?>
      <div class="col-sm-4">
        <select name="district" id="f-district" class="form-select" data-placeholder="Tất cả Quận/Huyện" data-selected="<?= (int)$fDistrict ?>"><option value="">Tất cả Quận/Huyện</option></select>
      </div>
    <?php endif; ?>
    <div class="col-sm-2 d-grid"><button class="btn btn-outline-secondary">Lọc</button></div>
  </form>
<?php endif; ?>

<div class="table-responsive">
  <table class="table table-hover align-middle bg-white border">
    <thead class="table-light">
      <tr>
        <th style="width:70px">ID</th><th>Tên</th>
        <?php if ($isDistrict): ?><th>Tỉnh/Thành</th><th class="text-center">Số phường</th><?php endif; ?>
        <?php if ($isWard): ?><th>Quận/Huyện</th><?php endif; ?>
        <?php if ($tab === 'provinces'): ?><th class="text-center">Số quận</th><?php endif; ?>
        <th class="text-center">Số tin dùng</th><th class="text-end" style="width:170px">Thao tác</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">Chưa có dữ liệu.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td class="fw-semibold"><?= e($r['name']) ?></td>
        <?php if ($isDistrict): ?><td><?= e($r['parent_name']) ?></td><td class="text-center"><?= (int)$r['extra'] ?></td><?php endif; ?>
        <?php if ($isWard): ?><td><?= e($r['parent_name']) ?> <span class="text-muted small">(<?= e($r['grand_name']) ?>)</span></td><?php endif; ?>
        <?php if ($tab === 'provinces'): ?><td class="text-center"><?= (int)$r['extra'] ?></td><?php endif; ?>
        <td class="text-center"><?= (int)$r['used'] ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#catalogModal" data-mode="edit"
                  data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['name']) ?>"
                  data-parent="<?= (int)($r['parent_id'] ?? 0) ?>" data-grand="<?= (int)($r['grand_id'] ?? 0) ?>">Sửa</button>
          <form method="post" action="<?= e(base_url('admin/catalog/delete')) ?>" class="d-inline"
                onsubmit="return confirm('Xóa “<?= e(addslashes($r['name'])) ?>”?');">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="f_province" value="<?= (int)$fProvince ?>">
            <input type="hidden" name="f_district" value="<?= (int)$fDistrict ?>">
            <button class="btn btn-sm btn-outline-danger">Xóa</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if ($isWard && count($rows) >= 300): ?><p class="small text-muted">Chỉ hiển thị 300 dòng đầu, hãy lọc theo Tỉnh/Quận để xem thêm.</p><?php endif; ?>

<!-- Modal thêm/sửa dùng chung -->
<div class="modal fade" id="catalogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="catalogForm" method="post" action="<?= e(base_url('admin/catalog/save')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="<?= e($tab) ?>">
      <input type="hidden" name="item_id" value="">
      <input type="hidden" name="f_province" value="<?= (int)$fProvince ?>">
      <input type="hidden" name="f_district" value="<?= (int)$fDistrict ?>">
      <div class="modal-header"><h5 class="modal-title" id="catalogModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php if ($isDistrict): ?>
          <div class="mb-3"><label class="form-label">Tỉnh/Thành</label>
            <select name="province_id" id="m-province" class="form-select" required>
              <option value="">Chọn Tỉnh/Thành</option>
              <?php foreach ($provinces as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select></div>
        <?php elseif ($isWard): ?>
          <div class="mb-3"><label class="form-label">Tỉnh/Thành</label>
            <select id="m-province" class="form-select" data-target="#m-district" data-level="districts" required>
              <option value="">Chọn Tỉnh/Thành</option>
              <?php foreach ($provinces as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="mb-3"><label class="form-label">Quận/Huyện</label>
            <select name="district_id" id="m-district" class="form-select" required><option value="">Chọn Quận/Huyện</option></select></div>
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
document.getElementById('catalogModal').addEventListener('show.bs.modal', async ev => {
  const b = ev.relatedTarget, f = document.getElementById('catalogForm');
  const edit = b && b.dataset.mode === 'edit';
  f.elements['item_id'].value = edit ? b.dataset.id : '';
  f.elements['name'].value = edit ? b.dataset.name : '';
  document.getElementById('catalogModalTitle').textContent = (edit ? 'Sửa ' : 'Thêm ') + <?= json_encode($cfg['noun'], JSON_UNESCAPED_UNICODE) ?>;
  const prov = document.getElementById('m-province'), dist = document.getElementById('m-district');
  <?php if ($isDistrict): ?>
  prov.value = edit ? b.dataset.parent : '';
  <?php elseif ($isWard): ?>
  prov.value = edit ? b.dataset.grand : '';
  await BdsLocation.load(dist, 'districts', prov.value, edit ? b.dataset.parent : '');
  <?php endif; ?>
});
</script>
