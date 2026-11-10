<?php
$provinces = $provinces ?? (new Location())->provinces();
$districts = $districts ?? [];
$wards = $wards ?? [];
$amenities = $amenities ?? (new Amenity())->allSorted();
$selectedAmenities = $selectedAmenities ?? [];
$existingImages = $existingImages ?? [];
?>
<div class="mb-4">
  <h1 class="h3">Sửa tin đăng</h1>
  <p class="text-muted mb-0">Sau khi lưu thay đổi, tin đăng sẽ được chuyển về trạng thái chờ duyệt.</p>
</div>
<form id="agent-edit-form" method="post" action="<?= e(base_url('agent/edit/' . (int)$listing['id'])) ?>" enctype="multipart/form-data" class="row g-3">
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
  <div id="rent-fields" class="col-12 col-md-8 row g-3 ms-0">
    <div class="col-12 col-md-4">
      <label for="deposit" class="form-label">Tiền đặt cọc (VNĐ)</label>
      <input id="deposit" name="deposit" type="number" min="0" step="any" class="form-control" value="<?= e($listing['deposit'] ?? '') ?>">
    </div>
    <div class="col-12 col-md-4">
      <label for="furniture" class="form-label">Nội thất</label>
      <select id="furniture" name="furniture" class="form-select">
        <option value="">Chọn tình trạng</option>
        <option value="none" <?= ($listing['furniture'] ?? '') === 'none' ? 'selected' : '' ?>>Không nội thất</option>
        <option value="basic" <?= ($listing['furniture'] ?? '') === 'basic' ? 'selected' : '' ?>>Nội thất cơ bản</option>
        <option value="full" <?= ($listing['furniture'] ?? '') === 'full' ? 'selected' : '' ?>>Đầy đủ nội thất</option>
      </select>
    </div>
    <div class="col-12 col-md-4">
      <label for="pets-allowed" class="form-label">Thú cưng</label>
      <select id="pets-allowed" name="pets_allowed" class="form-select">
        <option value="0" <?= (string)($listing['pets_allowed'] ?? '0') === '0' ? 'selected' : '' ?>>Không cho phép</option>
        <option value="1" <?= (string)($listing['pets_allowed'] ?? '0') === '1' ? 'selected' : '' ?>>Cho phép</option>
      </select>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <label for="area" class="form-label">Diện tích (m²)</label>
    <input id="area" name="area" type="number" min="0.1" step="any" class="form-control" required value="<?= e($listing['area'] ?? '') ?>">
  </div>
  <div class="col-6 col-md-3">
    <label for="bedrooms" class="form-label">Phòng ngủ</label>
    <input id="bedrooms" name="bedrooms" type="number" min="0" max="255" class="form-control" required value="<?= e($listing['bedrooms'] ?? '0') ?>">
  </div>
  <div class="col-6 col-md-3">
    <label for="bathrooms" class="form-label">Phòng tắm</label>
    <input id="bathrooms" name="bathrooms" type="number" min="0" max="255" class="form-control" required value="<?= e($listing['bathrooms'] ?? '0') ?>">
  </div>
  <div class="col-6 col-md-3">
    <label for="direction" class="form-label">Hướng nhà</label>
    <input id="direction" name="direction" maxlength="30" class="form-control" placeholder="Đông Nam" value="<?= e($listing['direction'] ?? '') ?>">
  </div>

  <div class="col-12 col-md-6">
    <label for="province" class="form-label">Tỉnh/Thành phố</label>
    <select id="province" name="province_id" class="form-select" data-target="#ward" data-level="wards" data-selected="<?= e($listing['province_id'] ?? '') ?>" required>
      <option value="">Chọn tỉnh/thành phố</option>
      <?php foreach ($provinces as $province): ?>
        <option value="<?= (int)$province['id'] ?>" <?= (string)($listing['province_id'] ?? '') === (string)$province['id'] ? 'selected' : '' ?>><?= e($province['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label for="ward" class="form-label">Phường/Xã</label>
    <select id="ward" name="ward_id" class="form-select" data-selected="<?= e($listing['ward_id'] ?? '') ?>" required>
      <option value="">Chọn phường/xã</option>
      <?php foreach ($wards as $ward): ?>
        <option value="<?= (int)$ward['id'] ?>" <?= (string)($listing['ward_id'] ?? '') === (string)$ward['id'] ? 'selected' : '' ?>><?= e($ward['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md-6">
    <label for="address-detail" class="form-label">Địa chỉ chi tiết</label>
    <input id="address-detail" name="address_detail" maxlength="200" class="form-control" placeholder="Số nhà, tên đường..." required value="<?= e($listing['address_detail'] ?? '') ?>">
    <small class="text-success d-flex align-items-center gap-1 mt-1 d-none" id="addressParseFeedback"><i class="bi bi-geo-alt-fill"></i> Đã tự động nhận diện: Phường ..., Tỉnh/Thành ...</small>
  </div>
  <div class="col-12 col-md-6">
    <label for="legal-status" class="form-label">Pháp lý</label>
    <input id="legal-status" name="legal_status" maxlength="100" class="form-control" placeholder="Sổ hồng riêng, hợp đồng mua bán..." value="<?= e($listing['legal_status'] ?? '') ?>">
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
          <input type="text" id="mapSearchAddress" class="form-control" placeholder="Nhập địa chỉ tìm nhanh trên bản đồ...">
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

  <fieldset class="col-12">
    <legend class="form-label">Tiện ích đi kèm</legend>
    <div class="row g-2">
      <?php foreach ($amenities as $amenity): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= (int)$amenity['id'] ?>" <?= in_array((int)$amenity['id'], array_map('intval', $selectedAmenities), true) ? 'checked' : '' ?>>
            <span class="form-check-label"><?= e($amenity['name']) ?></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <?php if (!empty($existingImages)): ?>
    <div class="col-12">
      <label class="form-label">Hình ảnh hiện tại (chọn ảnh bìa hoặc đánh dấu ảnh muốn xóa)</label>
      <div class="row g-3">
        <?php foreach ($existingImages as $img): ?>
          <div class="col-6 col-md-3">
            <div class="card h-100 border shadow-sm">
              <img src="<?= e(property_avatar_url($img['image_url'])) ?>" alt="Ảnh BĐS" class="card-img-top" style="height: 120px; object-fit: cover;">
              <div class="card-body p-2 small">
                <div class="form-check mb-1">
                  <input class="form-check-input" type="radio" name="cover_image_id" id="cover-img-<?= (int)$img['id'] ?>" value="<?= (int)$img['id'] ?>" <?= !empty($img['is_cover']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="cover-img-<?= (int)$img['id'] ?>">Đặt làm ảnh bìa</label>
                </div>
                <div class="form-check text-danger">
                  <input class="form-check-input" type="checkbox" name="delete_images[]" id="del-img-<?= (int)$img['id'] ?>" value="<?= (int)$img['id'] ?>">
                  <label class="form-check-label" for="del-img-<?= (int)$img['id'] ?>">Xóa ảnh này</label>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12">
    <label for="images" class="form-label">Tải thêm ảnh mới (tùy chọn - JPG, PNG, WEBP; tối đa 10 ảnh, 5 MB/ảnh)</label>
    <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" class="form-control" multiple>
    <div id="image-preview" class="row g-3 mt-1"></div>
  </div>

  <div class="col-12 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
    <a href="<?= e(base_url('agent')) ?>" class="btn btn-outline-secondary">Hủy</a>
  </div>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= e(base_url('assets/js/map.js?v=20261110b')) ?>"></script>
<script src="<?= e(base_url('assets/js/address-parser.js?v=20261110b')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function _callback(){
  var form = document.getElementById('agent-edit-form');
  var latitudeInput = document.getElementById('latitudeInput');
  var longitudeInput = document.getElementById('longitudeInput');
  var mapStatus = document.getElementById('mapStatus');
  var hadCoordinates = <?= !empty($listing['latitude']) && !empty($listing['longitude']) ? 'true' : 'false' ?>;
  var defaultLat = <?= !empty($listing['latitude']) ? (float)$listing['latitude'] : '10.776889' ?>;
  var defaultLng = <?= !empty($listing['longitude']) ? (float)$listing['longitude'] : '106.700806' ?>;
  var mapInstance = null;

  try {
    if (window.BdsMap && typeof window.BdsMap.initPicker === 'function') {
      var picker = window.BdsMap.initPicker({
        mapId: 'agentMapPicker',
        latInput: '#latitudeInput',
        lngInput: '#longitudeInput',
        addressInput: '#mapSearchAddress',
        searchBtn: '#btnSearchMapAddress',
        statusEl: '#mapStatus',
        defaultLat: defaultLat,
        defaultLng: defaultLng,
        defaultZoom: 15
      });
      if (picker && picker.map) {
        mapInstance = picker.map;
      }
    }
  } catch (err) {
    console.warn('Lỗi khi khởi tạo BdsMap.initPicker, kích hoạt chế độ dự phòng:', err);
  }

  // Fallback an toàn: Khởi tạo trực tiếp nếu BdsMap chưa sẵn sàng nhưng Leaflet (L) đã tải
  if (!mapInstance && typeof L !== 'undefined' && document.getElementById('agentMapPicker')) {
    try {
      var mapContainer = document.getElementById('agentMapPicker');
      if (mapContainer._leaflet_id) {
        delete mapContainer._leaflet_id;
        mapContainer.innerHTML = '';
      }

      if (L.Icon && L.Icon.Default) {
        try {
          delete L.Icon.Default.prototype._getIconUrl;
          L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png'
          });
        } catch (iconErr) {}
      }

      var curLat = parseFloat(latitudeInput ? latitudeInput.value : '') || defaultLat;
      var curLng = parseFloat(longitudeInput ? longitudeInput.value : '') || defaultLng;

      mapInstance = L.map('agentMapPicker', {
        center: [curLat, curLng],
        zoom: 15,
        scrollWheelZoom: true
      });

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; Dữ liệu bản đồ nền'
      }).addTo(mapInstance);

      var marker = L.marker([curLat, curLng], {
        draggable: true,
        title: 'Kéo điểm ghim hoặc bấm lên bản đồ để chọn tọa độ'
      }).addTo(mapInstance);

      function updateCoordsFallback(lat, lng) {
        var fLat = parseFloat(Number(lat).toFixed(7));
        var fLng = parseFloat(Number(lng).toFixed(7));
        if (latitudeInput) latitudeInput.value = fLat;
        if (longitudeInput) longitudeInput.value = fLng;
        marker.setLatLng([fLat, fLng]);
        marker.bindPopup('<strong>Vị trí đã chọn</strong><br>Tọa độ: ' + fLat + ', ' + fLng).openPopup();
        if (mapStatus) {
          mapStatus.className = 'alert alert-success py-2 small mb-2';
          mapStatus.textContent = 'Đã chọn tọa độ: ' + fLat + ', ' + fLng;
        }
      }

      updateCoordsFallback(curLat, curLng);

      marker.on('dragend', function(e) {
        var pos = e.target.getLatLng();
        updateCoordsFallback(pos.lat, pos.lng);
      });

      mapInstance.on('click', function(e) {
        updateCoordsFallback(e.latlng.lat, e.latlng.lng);
      });
    } catch (fallbackErr) {
      console.error('Không thể khởi tạo bản đồ dự phòng:', fallbackErr);
    }
  }

  // Gọi map.invalidateSize() sau khi load và các mốc thời gian (150ms, 500ms, 1000ms) để chống màn hình xám/trắng
  if (mapInstance) {
    var triggerInvalidate = function() {
      try {
        if (mapInstance && typeof mapInstance.invalidateSize === 'function') {
          mapInstance.invalidateSize();
        }
      } catch (e) {}
    };
    triggerInvalidate();
    setTimeout(triggerInvalidate, 150);
    setTimeout(triggerInvalidate, 500);
    setTimeout(triggerInvalidate, 1000);
    window.addEventListener('resize', triggerInvalidate);
    window.addEventListener('load', triggerInvalidate);
  }

  if (form && !hadCoordinates) {
    form.addEventListener('submit', function _callback2(event){
      if (latitudeInput.value === '10.776889' && longitudeInput.value === '106.700806') {
        event.preventDefault();
        mapStatus.className = 'alert alert-warning py-2 small mb-2';
        mapStatus.textContent = 'Vui lòng bấm lên bản đồ hoặc kéo điểm ghim để chọn tọa độ.';
        mapStatus.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }
});</script>
