<h1 class="h4 mb-3">Bảng điều khiển quản trị</h1>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $n): ?>
    <div class="col-6 col-lg-3"><div class="card text-center"><div class="card-body"><div class="display-6"><?= (int)$n ?></div><div class="text-muted"><?= e($label) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="list-group">
  <a class="list-group-item list-group-item-action" href="<?= e(base_url('admin/users')) ?>">Quản lý người dùng</a>
  <a class="list-group-item list-group-item-action" href="<?= e(base_url('admin/catalog')) ?>">Quản lý danh mục</a>
  <a class="list-group-item list-group-item-action" href="<?= e(base_url('admin/properties')) ?>">Duyệt tin đăng</a>
</div>
