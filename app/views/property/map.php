<?php
// [C - Thiện] Bản đồ bất động sản trực quan (Interactive Real Estate Map with Leaflet & Marker Clustering)
/**
 * @var array $properties      Danh sách BĐS đã duyệt (status = 'approved') kèm tọa độ
 * @var int   $total           Tổng số tin BĐS
 * @var array $filters         Bộ lọc hiện tại
 * @var array $provinces       Danh sách Tỉnh/Thành phố
 * @var array $districts       Danh sách Quận/Huyện theo Tỉnh (nếu có)
 * @var array $propertyTypes   Danh mục loại hình BĐS
 */
?>

<!-- Leaflet & MarkerCluster CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
  .map-page-wrapper {
    background-color: #f8fafc;
  }
  .map-filter-bar {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
  }
  .map-canvas-container {
    height: calc(100vh - 210px);
    min-height: 600px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    position: relative;
  }
  #propertyMap {
    width: 100%;
    height: 100%;
    z-index: 1;
  }
  .map-sidebar-list {
    height: calc(100vh - 210px);
    min-height: 600px;
    overflow-y: auto;
    padding-right: 4px;
  }
  .map-sidebar-list::-webkit-scrollbar {
    width: 6px;
  }
  .map-sidebar-list::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
  }
  .map-property-card {
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    background: #ffffff;
  }
  .map-property-card:hover, .map-property-card.active {
    border-color: #0d6efd;
    box-shadow: 0 4px 14px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
  }
  .map-property-card.active {
    background-color: #f0f7ff;
  }
  .map-card-img {
    width: 110px;
    height: 95px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
  }
  .map-popup-card {
    width: 250px;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  }
  .map-popup-img {
    width: 100%;
    height: 125px;
    object-fit: cover;
    border-radius: 8px 8px 4px 4px;
    margin-bottom: 8px;
  }
  .leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
    padding: 0;
    overflow: hidden;
  }
  .leaflet-popup-content {
    margin: 12px;
    line-height: 1.4;
  }
  .custom-pin-badge {
    background: #dc3545;
    color: #ffffff;
    font-weight: 700;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
    border: 2px solid #ffffff;
    white-space: nowrap;
  }
  .custom-pin-badge.rent {
    background: #0d6efd;
  }
  .btn-my-location {
    transition: all 0.2s ease;
  }
  .btn-my-location:hover {
    background-color: #0d6efd;
    color: #ffffff !important;
  }
  .map-floating-ctrl {
    position: absolute;
    top: 14px;
    left: 60px;
    z-index: 1000;
  }
  @media (max-width: 991.98px) {
    .map-canvas-container {
      height: 65vh;
      min-height: 420px;
    }
    .map-sidebar-list {
      height: auto;
      max-height: 50vh;
    }
  }
</style>

<div class="map-page-wrapper py-3 py-lg-4">
  <div class="container-fluid px-lg-4">
    <!-- Breadcrumb & Tiêu đề trang -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= e(base_url()) ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= e(base_url('property/search')) ?>" class="text-decoration-none text-muted">Bất động sản</a></li>
            <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Bản đồ trực quan</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
          <i class="bi bi-map-fill text-primary"></i>
          Bản đồ bất động sản trực quan
        </h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small fw-semibold">
          <i class="bi bi-geo-alt-fill me-1"></i>
          <span id="statVisibleCount"><?= (int)$total ?></span> bất động sản trên bản đồ
        </span>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill btn-my-location px-3" id="btnMyLocation" title="Xác định vị trí hiện tại của tôi">
          <i class="bi bi-crosshair me-1"></i>
          <span>Vị trí của tôi</span>
        </button>
      </div>
    </div>

    <!-- Thanh bộ lọc nhanh phía trên bản đồ -->
    <div class="map-filter-bar p-3 mb-3">
      <form id="mapFilterForm" class="row g-2 align-items-center" onsubmit="return false;">
        <!-- Tỉnh / Thành phố -->
        <div class="col-12 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Tỉnh / Thành phố</label>
          <select class="form-select form-select-sm rounded-3" id="filterProvince" name="province_id">
            <option value="">-- Toàn quốc (Tất cả) --</option>
            <?php foreach ($provinces as $prov): ?>
              <option value="<?= (int)$prov['id'] ?>" <?= ((int)($filters['province_id'] ?? 0) === (int)$prov['id']) ? 'selected' : '' ?>>
                <?= e($prov['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Hình thức (Bán / Cho thuê) -->
        <div class="col-12 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Hình thức</label>
          <select class="form-select form-select-sm rounded-3" id="filterListingType" name="listing_type">
            <option value="">Tất cả hình thức</option>
            <option value="sale" <?= (($filters['listing_type'] ?? '') === 'sale') ? 'selected' : '' ?>>Mua bán nhà đất</option>
            <option value="rent" <?= (($filters['listing_type'] ?? '') === 'rent') ? 'selected' : '' ?>>Nhà đất cho thuê</option>
          </select>
        </div>

        <!-- Loại hình BĐS -->
        <div class="col-12 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Loại bất động sản</label>
          <select class="form-select form-select-sm rounded-3" id="filterTypeId" name="type_id">
            <option value="">Tất cả loại BĐS</option>
            <?php foreach ($propertyTypes as $type): ?>
              <option value="<?= (int)$type['id'] ?>" <?= ((int)($filters['type_id'] ?? 0) === (int)$type['id']) ? 'selected' : '' ?>>
                <?= e($type['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Mức giá -->
        <div class="col-12 col-sm-6 col-md-3 col-xl-2">
          <label class="form-label small text-muted mb-1 fw-semibold">Khoảng giá</label>
          <select class="form-select form-select-sm rounded-3" id="filterPriceRange" name="price_range">
            <option value="">Tất cả mức giá</option>
            <optgroup label="Mua bán nhà đất">
              <option value="<1ty" <?= (($filters['price_range'] ?? '') === '<1ty') ? 'selected' : '' ?>>Dưới 1 tỷ</option>
              <option value="1-2ty" <?= (($filters['price_range'] ?? '') === '1-2ty') ? 'selected' : '' ?>>1 - 2 tỷ</option>
              <option value="2-3ty" <?= (($filters['price_range'] ?? '') === '2-3ty') ? 'selected' : '' ?>>2 - 3 tỷ</option>
              <option value="3-5ty" <?= (($filters['price_range'] ?? '') === '3-5ty') ? 'selected' : '' ?>>3 - 5 tỷ</option>
              <option value="5-7ty" <?= (($filters['price_range'] ?? '') === '5-7ty') ? 'selected' : '' ?>>5 - 7 tỷ</option>
              <option value="7-10ty" <?= (($filters['price_range'] ?? '') === '7-10ty') ? 'selected' : '' ?>>7 - 10 tỷ</option>
              <option value=">20ty" <?= (($filters['price_range'] ?? '') === '>20ty') ? 'selected' : '' ?>>Trên 20 tỷ</option>
            </optgroup>
            <optgroup label="Cho thuê">
              <option value="<3tr" <?= (($filters['price_range'] ?? '') === '<3tr') ? 'selected' : '' ?>>Dưới 3 triệu/tháng</option>
              <option value="3-5tr" <?= (($filters['price_range'] ?? '') === '3-5tr') ? 'selected' : '' ?>>3 - 5 triệu/tháng</option>
              <option value="5-10tr" <?= (($filters['price_range'] ?? '') === '5-10tr') ? 'selected' : '' ?>>5 - 10 triệu/tháng</option>
              <option value=">40tr" <?= (($filters['price_range'] ?? '') === '>40tr') ? 'selected' : '' ?>>Trên 40 triệu/tháng</option>
            </optgroup>
          </select>
        </div>

        <!-- Tìm kiếm từ khóa -->
        <div class="col-12 col-sm-8 col-md-4 col-xl-3">
          <label class="form-label small text-muted mb-1 fw-semibold">Từ khóa / Khu vực</label>
          <div class="input-group input-group-sm">
            <input type="text" class="form-control rounded-start-3" id="filterKeyword" name="keyword"
                   placeholder="Nhập tên dự án, đường, quận..." value="<?= e($filters['keyword'] ?? '') ?>">
            <button class="btn btn-primary" type="button" id="btnApplyFilter" title="Tìm kiếm">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </div>

        <!-- Nút đặt lại -->
        <div class="col-12 col-sm-4 col-md-2 col-xl-1 d-flex align-items-end">
          <button type="button" class="btn btn-outline-secondary btn-sm w-100 rounded-3" id="btnResetFilters" title="Đặt lại bộ lọc">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại
          </button>
        </div>
      </form>
    </div>

    <!-- Khu vực hiển thị Bản đồ tương tác & Danh sách bên cạnh -->
    <div class="row g-3">
      <!-- Cột danh sách BĐS hiển thị bên cạnh (Thu gọn/Mở rộng mượt mà) -->
      <div class="col-lg-4 col-xl-3 order-2 order-lg-1">
        <div class="card border-0 shadow-sm rounded-3 h-100">
          <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold small text-dark">
              <i class="bi bi-list-stars text-primary me-1"></i>
              Danh sách hiển thị (<span id="sideListCount"><?= (int)$total ?></span>)
            </span>
            <span class="text-muted small" style="font-size: 0.75rem;">Bấm để xem trên bản đồ</span>
          </div>
          <div class="card-body p-2 map-sidebar-list" id="mapSidebarList">
            <!-- Render danh sách tin qua JS để đồng bộ thời gian thực -->
          </div>
        </div>
      </div>

      <!-- Cột Bản đồ không gian lớn -->
      <div class="col-lg-8 col-xl-9 order-1 order-lg-2">
        <div class="map-canvas-container">
          <div id="propertyMap"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Dữ liệu ban đầu truyền vào JS -->
<script>
  window.MAP_PROPERTIES = <?= json_for_script($properties) ?>;
  window.MAP_BASE_URL = <?= json_for_script(rtrim(base_url(), '/')) ?>;
</script>

<!-- Thư viện bản đồ Leaflet & MarkerCluster -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const allProperties = Array.isArray(window.MAP_PROPERTIES) ? window.MAP_PROPERTIES : [];
  const mapEl = document.getElementById('propertyMap');
  if (!mapEl || typeof L === 'undefined') return;

  // Cấu hình tâm bản đồ mặc định (Toàn quốc hoặc TP.HCM)
  const VIETNAM_CENTER = [16.054407, 107.5];
  const VIETNAM_ZOOM = 6;
  const HCM_CENTER = [10.776889, 106.700806];

  // Khởi tạo Leaflet Map
  const map = L.map('propertyMap', {
    center: allProperties.length > 0 ? HCM_CENTER : VIETNAM_CENTER,
    zoom: allProperties.length > 0 ? 11 : VIETNAM_ZOOM,
    scrollWheelZoom: true,
  });

  // Tile layers
  const osmStandard = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; Bản đồ số BĐS Việt'
  }).addTo(map);

  const osmHot = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; Bản đồ giao thông & tiện ích'
  });

  const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: '&copy; Ảnh vệ tinh độ phân giải cao'
  });

  // Layer control (Chuyển đổi kiểu bản đồ)
  L.control.layers({
    '<span style="font-size:12px;font-weight:600;">🗺️ Bản đồ tiêu chuẩn</span>': osmStandard,
    '<span style="font-size:12px;">🧭 Giao thông & Tiện ích</span>': osmHot,
    '<span style="font-size:12px;">🛰️ Ảnh vệ tinh</span>': esriSatellite
  }, null, { position: 'topright' }).addTo(map);

  // Nhóm MarkerCluster gom cụm các điểm ghim số lượng
  const clusterGroup = L.markerClusterGroup({
    chunkedLoading: true,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    maxClusterRadius: 45,
    polygonOptions: {
      fillColor: '#0d6efd',
      color: '#0d6efd',
      weight: 2,
      opacity: 0.8,
      fillOpacity: 0.2
    }
  }).addTo(map);

  let currentMarkersMap = {};
  let userLocationMarker = null;

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Tạo popup HTML phong phú cho từng tin BĐS
  function buildPopupContent(p) {
    const isRent = p.listing_type === 'rent';
    const badgeHtml = isRent
      ? '<span class="badge bg-primary" style="font-size:0.7rem;">Cho thuê</span>'
      : '<span class="badge bg-danger" style="font-size:0.7rem;">Bán</span>';

    const locationText = [p.ward_name, p.district_name, p.province_name].filter(Boolean).join(', ');

    return `
      <div class="map-popup-card">
        <a href="${escapeHtml(p.detail_url)}" target="_blank">
          <img src="${escapeHtml(p.cover_url)}" alt="${escapeHtml(p.title)}" class="map-popup-img" onerror="this.src='${escapeHtml(window.MAP_BASE_URL)}/uploads/sample-property-01.jpg'">
        </a>
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="fw-bold text-danger fs-6">${escapeHtml(p.formatted_price)}</span>
          ${badgeHtml}
        </div>
        <div class="fw-bold text-truncate mb-1" title="${escapeHtml(p.title)}" style="font-size:0.85rem;">
          <a href="${escapeHtml(p.detail_url)}" target="_blank" class="text-decoration-none text-dark hover-primary">${escapeHtml(p.title)}</a>
        </div>
        <div class="text-muted small mb-2 d-flex flex-wrap gap-2" style="font-size:0.75rem;">
          <span>📐 ${escapeHtml(p.area)} m²</span>
          ${p.bedrooms ? `<span>• 🛏️ ${escapeHtml(p.bedrooms)} PN</span>` : ''}
          ${p.bathrooms ? `<span>• 🛁 ${escapeHtml(p.bathrooms)} WC</span>` : ''}
        </div>
        ${locationText ? `<div class="text-secondary small mb-2 text-truncate" title="${escapeHtml(locationText)}" style="font-size:0.75rem;"><i class="bi bi-geo-alt me-1 text-danger"></i>${escapeHtml(locationText)}</div>` : ''}
        <a href="${escapeHtml(p.detail_url)}" class="btn btn-primary btn-sm w-100 py-1 fw-semibold text-white" style="font-size:0.78rem;">
          Xem chi tiết tin đăng &rarr;
        </a>
      </div>
    `;
  }

  // Render danh sách thẻ BĐS ở thanh bên cạnh
  function renderSidebarList(properties) {
    const listEl = document.getElementById('mapSidebarList');
    const sideCountEl = document.getElementById('sideListCount');
    const statVisibleEl = document.getElementById('statVisibleCount');

    if (sideCountEl) sideCountEl.textContent = properties.length;
    if (statVisibleEl) statVisibleEl.textContent = properties.length;

    if (!listEl) return;

    if (properties.length === 0) {
      listEl.innerHTML = `
        <div class="text-center py-5 text-muted">
          <div class="fs-1 mb-2">📍</div>
          <h6 class="fw-bold text-dark">Không có bất động sản nào</h6>
          <p class="small text-muted mb-0">Thử nới lỏng bộ lọc khu vực, mức giá để tìm thấy thêm tin đăng.</p>
        </div>
      `;
      return;
    }

    let html = '';
    properties.forEach(p => {
      const isRent = p.listing_type === 'rent';
      const badgeClass = isRent ? 'bg-primary' : 'bg-danger';
      const badgeText = isRent ? 'Cho thuê' : 'Bán';
      const locationText = [p.district_name, p.province_name].filter(Boolean).join(', ');

      html += `
        <div class="map-property-card p-2 mb-2 d-flex gap-2" data-id="${p.id}" id="card-prop-${p.id}">
          <img src="${escapeHtml(p.cover_url)}" alt="${escapeHtml(p.title)}" class="map-card-img" onerror="this.src='${escapeHtml(window.MAP_BASE_URL)}/uploads/sample-property-01.jpg'">
          <div class="d-flex flex-column justify-content-between flex-grow-1 overflow-hidden">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge ${badgeClass}" style="font-size:0.65rem;">${badgeText}</span>
                <span class="text-danger fw-bold small">${escapeHtml(p.formatted_price)}</span>
              </div>
              <div class="fw-semibold text-truncate small text-dark mb-1" title="${escapeHtml(p.title)}">
                ${escapeHtml(p.title)}
              </div>
            </div>
            <div class="text-muted small d-flex justify-content-between align-items-center" style="font-size:0.72rem;">
              <span>📐 ${escapeHtml(p.area)} m²</span>
              <span class="text-truncate ms-1"><i class="bi bi-geo-alt text-danger"></i> ${escapeHtml(locationText)}</span>
            </div>
          </div>
        </div>
      `;
    });

    listEl.innerHTML = html;

    // Bắt sự kiện click vào thẻ danh sách để nhảy đến điểm ghim trên bản đồ
    listEl.querySelectorAll('.map-property-card').forEach(card => {
      card.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        focusPropertyOnMap(id);
      });
    });
  }

  // Tiêu điểm đến điểm ghim trên bản đồ
  function focusPropertyOnMap(id) {
    const marker = currentMarkersMap[id];
    if (!marker) return;

    // Highlight card
    document.querySelectorAll('.map-property-card').forEach(c => c.classList.remove('active'));
    const activeCard = document.getElementById(`card-prop-${id}`);
    if (activeCard) {
      activeCard.classList.add('active');
      activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Zoom và mở popup trên bản đồ
    clusterGroup.zoomToShowLayer(marker, function () {
      marker.openPopup();
    });
  }

  // Vẽ các điểm ghim lên bản đồ và quản lý cluster
  function renderMarkers(properties) {
    clusterGroup.clearLayers();
    currentMarkersMap = {};
    const validBounds = [];

    properties.forEach(p => {
      if (!p.latitude || !p.longitude) return;
      const lat = parseFloat(p.latitude);
      const lng = parseFloat(p.longitude);
      if (isNaN(lat) || isNaN(lng)) return;

      const marker = L.marker([lat, lng], {
        title: p.title,
        riseOnHover: true
      });

      marker.bindPopup(buildPopupContent(p), {
        maxWidth: 260,
        className: 'bds-map-popup'
      });

      marker.on('click', function () {
        document.querySelectorAll('.map-property-card').forEach(c => c.classList.remove('active'));
        const activeCard = document.getElementById(`card-prop-${p.id}`);
        if (activeCard) {
          activeCard.classList.add('active');
          activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });

      clusterGroup.addLayer(marker);
      currentMarkersMap[p.id] = marker;
      validBounds.push([lat, lng]);
    });

    // Tự động căn chỉnh tầm nhìn bao trùm tất cả các điểm ghim
    if (validBounds.length === 1) {
      map.setView(validBounds[0], 15);
    } else if (validBounds.length > 1) {
      map.fitBounds(L.latLngBounds(validBounds), { padding: [50, 50], maxZoom: 15 });
    } else {
      map.setView(VIETNAM_CENTER, VIETNAM_ZOOM);
    }

    renderSidebarList(properties);
  }

  // Khớp bộ lọc dữ liệu phía client (phản hồi tức thì 0ms)
  function getFilteredProperties() {
    const provinceId = document.getElementById('filterProvince').value;
    const listingType = document.getElementById('filterListingType').value;
    const typeId = document.getElementById('filterTypeId').value;
    const priceRange = document.getElementById('filterPriceRange').value;
    const keyword = (document.getElementById('filterKeyword').value || '').trim().toLowerCase();

    return allProperties.filter(p => {
      // Lọc Tỉnh/Thành phố
      if (provinceId && String(p.province_id) !== String(provinceId)) {
        return false;
      }
      // Lọc Hình thức (sale / rent)
      if (listingType && p.listing_type !== listingType) {
        return false;
      }
      // Lọc Loại hình BĐS
      if (typeId && String(p.type_id) !== String(typeId)) {
        return false;
      }
      // Lọc từ khóa
      if (keyword) {
        const fullSearchStr = `${p.title} ${p.address_detail || ''} ${p.ward_name || ''} ${p.district_name || ''} ${p.province_name || ''}`.toLowerCase();
        if (!fullSearchStr.includes(keyword)) {
          return false;
        }
      }
      // Lọc khoảng giá
      if (priceRange) {
        const price = parseFloat(p.price);
        if (isNaN(price)) return false;

        switch (priceRange) {
          case '<1ty':
            if (price >= 1e9) return false;
            break;
          case '1-2ty':
            if (price < 1e9 || price > 2e9) return false;
            break;
          case '2-3ty':
            if (price < 2e9 || price > 3e9) return false;
            break;
          case '3-5ty':
            if (price < 3e9 || price > 5e9) return false;
            break;
          case '5-7ty':
            if (price < 5e9 || price > 7e9) return false;
            break;
          case '7-10ty':
            if (price < 7e9 || price > 10e9) return false;
            break;
          case '>20ty':
            if (price < 20e9) return false;
            break;
          case '<3tr':
            if (price >= 3e6) return false;
            break;
          case '3-5tr':
            if (price < 3e6 || price > 5e6) return false;
            break;
          case '5-10tr':
            if (price < 5e6 || price > 10e6) return false;
            break;
          case '>40tr':
            if (price < 40e6) return false;
            break;
        }
      }
      return true;
    });
  }

  function applyFilter() {
    const filtered = getFilteredProperties();
    renderMarkers(filtered);
  }

  // Sự kiện thay đổi bộ lọc
  document.getElementById('filterProvince').addEventListener('change', applyFilter);
  document.getElementById('filterListingType').addEventListener('change', applyFilter);
  document.getElementById('filterTypeId').addEventListener('change', applyFilter);
  document.getElementById('filterPriceRange').addEventListener('change', applyFilter);
  document.getElementById('filterKeyword').addEventListener('input', applyFilter);
  document.getElementById('btnApplyFilter').addEventListener('click', applyFilter);

  // Đặt lại bộ lọc
  document.getElementById('btnResetFilters').addEventListener('click', function () {
    document.getElementById('mapFilterForm').reset();
    applyFilter();
  });

  // Nút định vị vị trí hiện tại của tôi (My Location)
  const btnMyLocation = document.getElementById('btnMyLocation');
  if (btnMyLocation) {
    btnMyLocation.addEventListener('click', function () {
      if (!navigator.geolocation) {
        alert('Trình duyệt của bạn không hỗ trợ tính năng định vị.');
        return;
      }

      const originalHtml = btnMyLocation.innerHTML;
      btnMyLocation.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang định vị...';
      btnMyLocation.disabled = true;

      navigator.geolocation.getCurrentPosition(
        function (position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          map.setView([lat, lng], 15);

          if (userLocationMarker) {
            map.removeLayer(userLocationMarker);
          }

          userLocationMarker = L.circleMarker([lat, lng], {
            radius: 9,
            fillColor: '#0d6efd',
            color: '#ffffff',
            weight: 3,
            opacity: 1,
            fillOpacity: 0.95
          }).addTo(map);

          userLocationMarker.bindPopup('<strong>📍 Vị trí hiện tại của bạn</strong>').openPopup();

          btnMyLocation.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Đã định vị';
          setTimeout(() => {
            btnMyLocation.innerHTML = originalHtml;
            btnMyLocation.disabled = false;
          }, 3000);
        },
        function (err) {
          console.warn('Lỗi định vị:', err);
          alert('Không thể xác định vị trí hiện tại. Vui lòng cho phép quyền truy cập vị trí trên trình duyệt.');
          btnMyLocation.innerHTML = originalHtml;
          btnMyLocation.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
      );
    });
  }

  // Khởi tạo ban đầu
  renderMarkers(allProperties);

  // Xử lý chống vỡ bản đồ (Anti Grey Screen)
  setTimeout(() => map.invalidateSize(), 150);
  setTimeout(() => map.invalidateSize(), 500);
  setTimeout(() => map.invalidateSize(), 1000);
  window.addEventListener('resize', () => map.invalidateSize());
});
</script>
