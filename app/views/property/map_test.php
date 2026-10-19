<!-- Leaflet 1.9 CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
  #map { height: 480px; width: 100%; border-radius: 8px; z-index: 1; }
</style>

<div class="container py-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Trang chủ</a></li>
      <li class="breadcrumb-item active" aria-current="page">Kiểm tra bản đồ</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 fw-bold mb-1">Kiểm tra Bản đồ Leaflet & OpenStreetMap (Nominatim)</h1>
      <p class="text-muted small mb-0">Nhiệm vụ Tuần 1 (Nhánh <code>setup/maps</code>) - Phụ trách: <strong>Hà Ngọc Thiện</strong></p>
    </div>
    <span class="badge text-bg-success fs-6">Tuần 1: Setup Bản đồ</span>
  </div>

  <div id="map-status" class="alert alert-info py-2 small mb-3">
    Bản đồ OpenStreetMap đã sẵn sàng. Bạn có thể nhập địa chỉ bấm "Tìm vị trí" hoặc bấm trực tiếp lên bản đồ.
  </div>

  <!-- Form thử nghiệm chọn vị trí (dành cho Hải tích hợp form đăng tin Tuần 2) -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <h6 class="fw-bold mb-3">1. Thử nghiệm tìm kiếm & định vị tọa độ:</h6>
      <div class="row g-2 mb-3">
        <div class="col-md-8">
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i> Địa chỉ</span>
            <input type="text" id="test-address" class="form-control" placeholder="Nhập địa chỉ (VD: Chợ Bến Thành, Quận 1 hoặc 12 Xuân Thủy, Thảo Điền)" value="Chợ Bến Thành, Quận 1">
            <button class="btn btn-primary" type="button" id="btn-search-map">Tìm trên bản đồ</button>
          </div>
        </div>
        <div class="col-md-2">
          <input type="text" id="latitude" class="form-control" placeholder="Vĩ độ (Lat)" readonly>
        </div>
        <div class="col-md-2">
          <input type="text" id="longitude" class="form-control" placeholder="Kinh độ (Lng)" readonly>
        </div>
      </div>
      <div class="form-text small text-muted">
        * Bấm nút <strong>Tìm trên bản đồ</strong> để gọi <code>GeocodeController</code> (Nominatim). Hoặc <strong>bấm chuột / kéo thả marker</strong> để chỉnh vị trí chính xác.
      </div>
    </div>
  </div>

  <!-- Khung bản đồ -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-2">
      <div id="map"></div>
    </div>
  </div>

  <div class="card bg-light border-0">
    <div class="card-body">
      <h6 class="fw-bold mb-2">Thông tin kỹ thuật & Kiểm thử:</h6>
      <ul class="list-unstyled small mb-0">
        <li><strong>Thư viện bản đồ:</strong> Leaflet 1.9 + OpenStreetMap Tile Server (Hoàn toàn miễn phí, không cần thẻ tín dụng, không cần API Key).</li>
        <li><strong>Dịch vụ Geocoding:</strong> <code>GeocodeController</code> gọi dịch vụ OpenStreetMap Nominatim phía server (tuân thủ giới hạn 1 req/s, đính kèm User-Agent định danh).</li>
        <li><strong>Cơ chế dự phòng:</strong> Tự động fallback sang gọi Nominatim trực tiếp phía client nếu môi trường cURL bị chặn.</li>
        <li><strong>Quy chuẩn tích hợp:</strong> Module sẵn sàng xuất hàm <code>window.BdsMap.initPicker()</code> cho Hồ Duy Hải tích hợp vào Form đăng tin BĐS (Tuần 2).</li>
      </ul>
    </div>
  </div>
</div>

<!-- Leaflet 1.9 JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- Module Map của Thiện -->
<script src="<?= e(base_url('assets/js/map.js')) ?>"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const picker = BdsMap.initPicker({
      mapId: 'map',
      latInput: '#latitude',
      lngInput: '#longitude',
      addressInput: '#test-address',
      searchBtn: '#btn-search-map',
      statusEl: '#map-status',
      defaultLat: 10.776889,
      defaultLng: 106.700806,
      defaultZoom: 15,
      draggable: true
    });
  });
</script>
