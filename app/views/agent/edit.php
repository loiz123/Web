<div class="mb-4">
  <h1 class="h3">Sửa tin đăng</h1>
  <p class="text-muted mb-0">Sau khi lưu, tin sẽ được chuyển về trạng thái chờ duyệt.</p>
</div>
<form id="agent-edit-form" method="post" action="<?= e(base_url('agent/edit/' . (int)$listing['id'])) ?>" class="row g-3">
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
    <input id="address-detail" name="address_detail" maxlength="200" class="form-control" required value="<?= e($listing['address_detail'] ?? '') ?>">
  </div>

  <!-- Bản đồ chọn vị trí khi sửa tin -->
  <div class="col-12">
    <div class="card bg-light border shadow-sm">
      <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small text-dark">🗺️ Cập nhật vị trí bất động sản trên bản đồ</span>
        <span class="text-muted small">Kéo điểm ghim hoặc bấm trên bản đồ</span>
      </div>
      <div class="card-body p-3">
        <div class="input-group mb-2">
          <input type="text" id="mapSearchAddress" class="form-control" placeholder="Nhập địa chỉ tìm nhanh trên bản đồ (VD: Nguyễn Huệ, Quận 1, TP.HCM)...">
          <button type="button" class="btn btn-outline-primary" id="btnSearchMapAddress">🔍 Tìm vị trí</button>
        </div>
        <div id="mapStatus" class="alert alert-info py-2 small mb-2">
          💡 Bấm trực tiếp lên bản đồ hoặc kéo biểu tượng điểm ghim để cập nhật tọa độ chính xác.
        </div>
        <div id="agentMapPicker" style="height: 320px; width: 100%; border-radius: 6px;" class="border mb-2"></div>
        <div class="row g-2">
          <div class="col-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Vĩ độ (Lat)</span>
              <input type="text" id="latitudeInput" name="latitude" class="form-control bg-white" required value="<?= e($listing['latitude'] ?? '') ?>" placeholder="10.776889">
            </div>
          </div>
          <div class="col-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Kinh độ (Lng)</span>
              <input type="text" id="longitudeInput" name="longitude" class="form-control bg-white" required value="<?= e($listing['longitude'] ?? '') ?>" placeholder="106.700806">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
    <a href="<?= e(base_url('agent')) ?>" class="btn btn-outline-secondary">Hủy</a>
  </div>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= e(base_url('assets/js/map.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('agent-edit-form');
  var latitudeInput = document.getElementById('latitudeInput');
  var longitudeInput = document.getElementById('longitudeInput');
  var mapStatus = document.getElementById('mapStatus');
  var hadCoordinates = <?= !empty($listing['latitude']) && !empty($listing['longitude']) ? 'true' : 'false' ?>;
  if (window.BdsMap) {
    window.BdsMap.initPicker({
      mapId: 'agentMapPicker',
      latInput: '#latitudeInput',
      lngInput: '#longitudeInput',
      addressInput: '#mapSearchAddress',
      searchBtn: '#btnSearchMapAddress',
      statusEl: '#mapStatus',
      defaultLat: <?= !empty($listing['latitude']) ? (float)$listing['latitude'] : '10.776889' ?>,
      defaultLng: <?= !empty($listing['longitude']) ? (float)$listing['longitude'] : '106.700806' ?>,
      defaultZoom: 15
    });
  }
  if (form && !hadCoordinates) {
    form.addEventListener('submit', function (event) {
      if (latitudeInput.value === '10.776889' && longitudeInput.value === '106.700806') {
        event.preventDefault();
        mapStatus.className = 'alert alert-warning py-2 small mb-2';
        mapStatus.textContent = 'Vui lòng bấm lên bản đồ hoặc kéo điểm ghim để chọn tọa độ.';
        mapStatus.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }
});
</script>
