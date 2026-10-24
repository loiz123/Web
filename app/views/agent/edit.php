<div class="mb-4">
  <h1 class="h3">Sửa tin đăng</h1>
  <p class="text-muted mb-0">Sau khi lưu, tin sẽ được chuyển về trạng thái chờ duyệt.</p>
</div>
<form method="post" action="<?= e(base_url('agent/edit/' . (int)$listing['id'])) ?>" class="row g-3">
  <?= csrf_field() ?>
  <div class="col-12 col-md-6">
    <label for="title" class="form-label">Tiêu đề</label>
    <input id="title" name="title" class="form-control" maxlength="200" required value="<?= e($listing['title'] ?? '') ?>">
  </div>
  <div class="col-12 col-md-3">
    <label for="listing-type" class="form-label">Loại tin</label>
    <select id="listing-type" name="listing_type" class="form-select" required>
      <option value="sale" <?= ($listing['listing_type'] ?? '') === 'sale' ? 'selected' : '' ?>>Bán</option>
      <option value="rent" <?= ($listing['listing_type'] ?? '') === 'rent' ? 'selected' : '' ?>>Cho thuê</option>
    </select>
  </div>
  <div class="col-12 col-md-3">
    <label for="property-type" class="form-label">Loại bất động sản</label>
    <select id="property-type" name="type_id" class="form-select" required>
      <?php foreach ($propertyTypes as $type): ?>
        <option value="<?= (int)$type['id'] ?>" <?= (string)($listing['type_id'] ?? '') === (string)$type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
    <label for="description" class="form-label">Mô tả</label>
    <textarea id="description" name="description" rows="4" class="form-control"><?= e($listing['description'] ?? '') ?></textarea>
  </div>
  <div class="col-12 col-md-4">
    <label for="price" class="form-label">Giá (VNĐ)</label>
    <input id="price" name="price" type="number" min="1" step="any" class="form-control" required value="<?= e($listing['price'] ?? '') ?>">
  </div>
  <div class="col-12 col-md-4">
    <label for="area" class="form-label">Diện tích (m²)</label>
    <input id="area" name="area" type="number" min="0.1" step="any" class="form-control" required value="<?= e($listing['area'] ?? '') ?>">
  </div>
  <div class="col-6 col-md-2">
    <label for="bedrooms" class="form-label">Phòng ngủ</label>
    <input id="bedrooms" name="bedrooms" type="number" min="0" max="255" class="form-control" required value="<?= e($listing['bedrooms'] ?? '0') ?>">
  </div>
  <div class="col-6 col-md-2">
    <label for="bathrooms" class="form-label">Phòng tắm</label>
    <input id="bathrooms" name="bathrooms" type="number" min="0" max="255" class="form-control" required value="<?= e($listing['bathrooms'] ?? '0') ?>">
  </div>
  <div class="col-12">
    <label for="address-detail" class="form-label">Địa chỉ chi tiết</label>
    <input id="address-detail" name="address_detail" maxlength="200" class="form-control" value="<?= e($listing['address_detail'] ?? '') ?>">
  </div>
  <div class="col-12 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
    <a href="<?= e(base_url('agent')) ?>" class="btn btn-outline-secondary">Hủy</a>
  </div>
</form>
