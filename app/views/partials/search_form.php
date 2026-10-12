<?php /** @var array $provinces  @var array $filters (tùy chọn) */ $filters = $filters ?? []; ?>
<form class="row g-2" method="get" action="<?= e(base_url('property/search')) ?>">
  <div class="col-md-3"><input type="text" name="keyword" class="form-control" placeholder="Tên đường, dự án..." value="<?= e($filters['keyword'] ?? '') ?>"></div>
  <div class="col-md-2">
    <select name="listing_type" class="form-select">
      <option value="">Mua / Thuê</option>
      <option value="sale" <?= ($filters['listing_type'] ?? '') === 'sale' ? 'selected' : '' ?>>Mua</option>
      <option value="rent" <?= ($filters['listing_type'] ?? '') === 'rent' ? 'selected' : '' ?>>Thuê</option>
    </select>
  </div>
  <div class="col-md-3">
    <select id="province" class="form-select" data-target="#district">
      <option value="">Tỉnh/Thành</option>
      <?php foreach ($provinces as $pr): ?><option value="<?= (int)$pr['id'] ?>"><?= e($pr['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><select id="district" name="district_id" class="form-select"><option value="">Quận/Huyện</option></select></div>
  <div class="col-md-2 d-grid"><button class="btn btn-warning fw-semibold">Tìm kiếm</button></div>
</form>
