<?php
$values = $values ?? [];
$selectedAmenities = is_array($values['amenities'] ?? null) ? $values['amenities'] : [];
?>
<h1 class="h3 mb-4">Đăng tin bất động sản</h1>
<form method="post" action="<?= e(base_url('agent/create')) ?>" enctype="multipart/form-data" class="row g-3">
  <?= csrf_field() ?>
  <div class="col-12 col-md-6">
    <label for="title" class="form-label">Tiêu đề</label>
    <input id="title" name="title" class="form-control" maxlength="200" required value="<?= e($values['title'] ?? '') ?>">
  </div>
  <div class="col-12 col-md-3">
    <label for="listing-type" class="form-label">Loại tin</label>
    <select id="listing-type" name="listing_type" class="form-select" required>
      <option value="sale" <?= ($values['listing_type'] ?? '') === 'sale' ? 'selected' : '' ?>>Bán</option>
      <option value="rent" <?= ($values['listing_type'] ?? '') === 'rent' ? 'selected' : '' ?>>Cho thuê</option>
    </select>
  </div>
  <div class="col-12 col-md-3">
    <label for="property-type" class="form-label">Loại bất động sản</label>
    <select id="property-type" name="type_id" class="form-select" required>
      <option value="">Chọn loại bất động sản</option>
      <?php foreach ($propertyTypes as $type): ?>
        <option value="<?= (int)$type['id'] ?>" <?= (string)($values['type_id'] ?? '') === (string)$type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12">
    <label for="description" class="form-label">Mô tả</label>
    <textarea id="description" name="description" rows="4" class="form-control"><?= e($values['description'] ?? '') ?></textarea>
  </div>

  <div class="col-12 col-md-4">
    <label for="price" class="form-label">Giá (VNĐ)</label>
    <input id="price" name="price" type="number" min="1" step="any" class="form-control" required value="<?= e($values['price'] ?? '') ?>">
  </div>
  <div id="rent-fields" class="col-12 col-md-8 row g-3 ms-0">
    <div class="col-12 col-md-4">
      <label for="deposit" class="form-label">Tiền đặt cọc (VNĐ)</label>
      <input id="deposit" name="deposit" type="number" min="0" step="any" class="form-control" value="<?= e($values['deposit'] ?? '') ?>">
    </div>
    <div class="col-12 col-md-4">
      <label for="furniture" class="form-label">Nội thất</label>
      <select id="furniture" name="furniture" class="form-select">
        <option value="">Chọn tình trạng</option>
        <option value="none" <?= ($values['furniture'] ?? '') === 'none' ? 'selected' : '' ?>>Không có</option>
        <option value="basic" <?= ($values['furniture'] ?? '') === 'basic' ? 'selected' : '' ?>>Cơ bản</option>
        <option value="full" <?= ($values['furniture'] ?? '') === 'full' ? 'selected' : '' ?>>Đầy đủ</option>
      </select>
    </div>
    <div class="col-12 col-md-4">
      <label for="pets-allowed" class="form-label">Thú cưng</label>
      <select id="pets-allowed" name="pets_allowed" class="form-select">
        <option value="0">Không cho phép</option>
        <option value="1" <?= ($values['pets_allowed'] ?? '') === '1' ? 'selected' : '' ?>>Cho phép</option>
      </select>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <label for="area" class="form-label">Diện tích (m²)</label>
    <input id="area" name="area" type="number" min="0.1" step="any" class="form-control" required value="<?= e($values['area'] ?? '') ?>">
  </div>
  <div class="col-6 col-md-3">
    <label for="bedrooms" class="form-label">Phòng ngủ</label>
    <input id="bedrooms" name="bedrooms" type="number" min="0" class="form-control" value="<?= e($values['bedrooms'] ?? '0') ?>">
  </div>
  <div class="col-6 col-md-3">
    <label for="bathrooms" class="form-label">Phòng tắm</label>
    <input id="bathrooms" name="bathrooms" type="number" min="0" class="form-control" value="<?= e($values['bathrooms'] ?? '0') ?>">
  </div>
  <div class="col-6 col-md-3">
    <label for="direction" class="form-label">Hướng</label>
    <input id="direction" name="direction" maxlength="30" class="form-control" value="<?= e($values['direction'] ?? '') ?>">
  </div>

  <div class="col-12 col-md-4">
    <label for="province" class="form-label">Tỉnh/Thành phố</label>
    <select id="province" name="province_id" class="form-select" data-selected="<?= e($values['province_id'] ?? '') ?>" required>
      <option value="">Chọn tỉnh/thành phố</option>
      <?php foreach ($provinces as $province): ?>
        <option value="<?= (int)$province['id'] ?>" <?= (string)($values['province_id'] ?? '') === (string)$province['id'] ? 'selected' : '' ?>><?= e($province['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-4">
    <label for="district" class="form-label">Quận/Huyện</label>
    <select id="district" name="district_id" class="form-select" data-selected="<?= e($values['district_id'] ?? '') ?>" required>
      <option value="">Chọn quận/huyện</option>
    </select>
  </div>
  <div class="col-12 col-md-4">
    <label for="ward" class="form-label">Phường/Xã</label>
    <select id="ward" name="ward_id" class="form-select" data-selected="<?= e($values['ward_id'] ?? '') ?>" required>
      <option value="">Chọn phường/xã</option>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label for="address-detail" class="form-label">Địa chỉ chi tiết</label>
    <input id="address-detail" name="address_detail" maxlength="200" class="form-control" value="<?= e($values['address_detail'] ?? '') ?>">
  </div>
  <div class="col-12 col-md-6">
    <label for="legal-status" class="form-label">Pháp lý</label>
    <input id="legal-status" name="legal_status" maxlength="100" class="form-control" value="<?= e($values['legal_status'] ?? '') ?>">
  </div>

  <fieldset class="col-12">
    <legend class="form-label">Tiện ích</legend>
    <div class="row g-2">
      <?php foreach ($amenities as $amenity): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= (int)$amenity['id'] ?>" <?= in_array((string)$amenity['id'], array_map('strval', $selectedAmenities), true) ? 'checked' : '' ?>>
            <span class="form-check-label"><?= e($amenity['name']) ?></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="col-12">
    <label for="images" class="form-label">Ảnh bất động sản (JPG, PNG, WEBP; tối đa 10 ảnh, 5 MB/ảnh)</label>
    <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" class="form-control" multiple required>
    <input type="hidden" name="cover_image" value="0">
    <div id="image-preview" class="row g-3 mt-1"></div>
  </div>
  <div class="col-12">
    <button type="submit" class="btn btn-primary">Gửi tin đăng</button>
  </div>
</form>
