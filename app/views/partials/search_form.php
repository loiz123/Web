<?php
/**
 * Partial: Bộ lọc tìm kiếm bất động sản (Mô hình địa giới hành chính 2 cấp: Tỉnh/Thành phố -> Phường/Xã)
 * Bố cục 2 hàng chuẩn Bootstrap 5 (col-lg: 3 - 2 - 2 - 3 - 2 = 12 cột mỗi hàng, thẳng tắp 2 hàng)
 *
 * Biến truyền vào từ Controller/View:
 * @var array $filters        Mảng tham số lọc hiện tại ($_GET)
 * @var array $provinces      Danh sách Tỉnh/Thành phố
 * @var array $wards          Danh sách Phường/Xã (nếu đã chọn Tỉnh/Thành phố)
 * @var array $propertyTypes  Danh sách loại hình BĐS
 */
$filters       = $filters ?? $_GET ?? [];
$provinces     = $provinces ?? [];
$wards         = $wards ?? [];
$propertyTypes = $propertyTypes ?? [];

// Nếu view chưa truyền sẵn danh mục thì tự nạp từ Model để đảm bảo partial luôn hoạt động độc lập
if (empty($provinces) && class_exists('Location')) {
    $locModel  = new Location();
    $provinces = $locModel->provinces();
    if (!empty($filters['province_id']) && empty($wards)) {
        $wards = $locModel->wardsByProvince((int)$filters['province_id']);
    }
}
if (empty($propertyTypes) && class_exists('PropertyType')) {
    $propertyTypes = (new PropertyType())->allSorted();
}

// Khử trùng lặp danh sách Phường/Xã theo tên ngay tại tầng hiển thị (nếu có sẵn)
if (!empty($wards)) {
    $uniqueWardsInit = [];
    $seenWardNames   = [];
    foreach ($wards as $w) {
        $wName = trim((string)($w['name'] ?? ''));
        if ($wName !== '' && !isset($seenWardNames[$wName])) {
            $seenWardNames[$wName] = true;
            $uniqueWardsInit[]     = $w;
        }
    }
    $wards = $uniqueWardsInit;
}

$selectedListingType = $filters['listing_type'] ?? '';
$selectedTypeId      = (string)($filters['type_id'] ?? '');
$selectedProvinceId  = (string)($filters['province_id'] ?? '');
$selectedWardId      = (string)($filters['ward_id'] ?? '');
$selectedPriceRange  = (string)($filters['price_range'] ?? '');
$selectedAreaRange   = (string)($filters['area_range'] ?? '');
$selectedBedrooms    = (string)($filters['bedrooms'] ?? '');
$selectedSortBy      = (string)($filters['sort'] ?? $filters['sort_by'] ?? 'newest');

$hasCustomRange = (!empty($filters['min_price']) || !empty($filters['max_price']) || !empty($filters['min_area']) || !empty($filters['max_area']) || !empty($filters['listing_type']));
?>
<form id="propertySearchForm" class="card card-body shadow-sm mb-4 border-0 rounded-4 p-3 p-md-4 bg-white" method="get" action="<?= base_url('property/search') ?>">
  <!-- HÀNG 1: Từ khóa (3) | Tỉnh/Thành phố (2) | Phường/Xã (2) | Loại BĐS (3) | Nút Tìm kiếm (2) = 12 cột -->
  <div class="row g-2 align-items-center">
    <!-- 1. Từ khóa (col-lg-3) -->
    <div class="col-12 col-md-6 col-lg-3 position-relative" id="keywordAutocompleteWrap">
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
        <input id="searchKeyword"
               name="keyword"
               type="text"
               class="form-control border-start-0 ps-0"
               autocomplete="off"
               placeholder="Từ khóa, tên đường, phường, dự án..."
               value="<?= e($filters['keyword'] ?? '') ?>">
      </div>
      <!-- Dropdown gợi ý địa chỉ thông minh (Address Parser Autocomplete) -->
      <div id="searchAddressSuggestions"
           class="list-group position-absolute w-100 shadow-lg d-none"
           style="top: calc(100% + 4px); left: 0; z-index: 9999; border-radius: 12px; max-height: 320px; overflow-y: auto;">
      </div>
    </div>

    <!-- 2. Tỉnh/Thành phố (col-lg-2) -->
    <div class="col-6 col-md-3 col-lg-2">
      <select id="searchProvince" name="province_id" class="form-select" data-target="#searchWard" data-level="wards">
        <option value="">Tỉnh/Thành phố</option>
        <?php foreach ($provinces as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= $selectedProvinceId === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- 3. Phường/Xã (col-lg-2) -->
    <div class="col-6 col-md-3 col-lg-2">
      <select id="searchWard" name="ward_id" class="form-select" data-placeholder="Phường/Xã" data-selected="<?= e($selectedWardId) ?>">
        <option value="">Phường/Xã</option>
        <?php foreach ($wards as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= $selectedWardId === (string)$w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- 4. Loại BĐS (col-lg-3) -->
    <div class="col-12 col-md-8 col-lg-3">
      <select id="filterTypeId" name="type_id" class="form-select">
        <option value="">Loại nhà đất</option>
        <?php foreach ($propertyTypes as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $selectedTypeId === (string)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- 5. Nút Tìm kiếm (col-lg-2) -->
    <div class="col-12 col-md-4 col-lg-2 d-grid">
      <button type="submit" id="btnSubmitSearch" class="btn btn-warning w-100 fw-semibold text-dark">
        <i class="bi bi-search me-1"></i>Tìm kiếm
      </button>
    </div>
  </div>

  <!-- HÀNG 2 (thẳng tắp với Hàng 1): Khoảng giá (3) | Diện tích (2) | Số phòng ngủ (2) | Sắp xếp (3) | Nút Đặt lại (2) = 12 cột -->
  <div class="row g-2 align-items-center mt-1 pt-2 border-top">
    <!-- 1. Khoảng giá (col-lg-3) -->
    <div class="col-12 col-md-6 col-lg-3">
      <select id="filterPriceRange" name="price_range" class="form-select">
        <option value="">Mức giá</option>
        <option value="under_1b" <?= $selectedPriceRange === 'under_1b' ? 'selected' : '' ?>>Dưới 1 tỷ</option>
        <option value="1b_2b" <?= in_array($selectedPriceRange, ['1b_2b', '1b-2b'], true) ? 'selected' : '' ?>>1 - 2 tỷ</option>
        <option value="2b_3b" <?= in_array($selectedPriceRange, ['2b_3b', '2b-3b'], true) ? 'selected' : '' ?>>2 - 3 tỷ</option>
        <option value="3b_5b" <?= in_array($selectedPriceRange, ['3b_5b', '3b-5b'], true) ? 'selected' : '' ?>>3 - 5 tỷ</option>
        <option value="5b_7b" <?= in_array($selectedPriceRange, ['5b_7b', '5b-7b'], true) ? 'selected' : '' ?>>5 - 7 tỷ</option>
        <option value="7b_10b" <?= in_array($selectedPriceRange, ['7b_10b', '7b-10b'], true) ? 'selected' : '' ?>>7 - 10 tỷ</option>
        <option value="10b_20b" <?= in_array($selectedPriceRange, ['10b_20b', '10b-20b'], true) ? 'selected' : '' ?>>10 - 20 tỷ</option>
        <option value="over_20b" <?= in_array($selectedPriceRange, ['over_20b', '20b+'], true) ? 'selected' : '' ?>>Trên 20 tỷ</option>
        <option value="rent_0_5m" <?= in_array($selectedPriceRange, ['rent_0_5m', '0-5m'], true) ? 'selected' : '' ?>>Thuê &lt; 5 triệu</option>
        <option value="rent_5m_10m" <?= in_array($selectedPriceRange, ['rent_5m_10m', '5m-10m'], true) ? 'selected' : '' ?>>Thuê 5 - 10 triệu</option>
        <option value="rent_10m_20m" <?= in_array($selectedPriceRange, ['rent_10m_20m', '10m-20m'], true) ? 'selected' : '' ?>>Thuê 10 - 20 triệu</option>
        <option value="rent_20m_plus" <?= in_array($selectedPriceRange, ['rent_20m_plus', '20m+'], true) ? 'selected' : '' ?>>Thuê &gt; 20 triệu</option>
      </select>
    </div>

    <!-- 2. Diện tích (col-lg-2) -->
    <div class="col-6 col-md-3 col-lg-2">
      <select id="filterAreaRange" name="area_range" class="form-select">
        <option value="">Diện tích</option>
        <option value="under_30" <?= in_array($selectedAreaRange, ['under_30', '0-30'], true) ? 'selected' : '' ?>>&lt; 30 m²</option>
        <option value="30_50" <?= in_array($selectedAreaRange, ['30_50', '30-50'], true) ? 'selected' : '' ?>>30 - 50 m²</option>
        <option value="50_80" <?= in_array($selectedAreaRange, ['50_80', '50-80'], true) ? 'selected' : '' ?>>50 - 80 m²</option>
        <option value="80_100" <?= in_array($selectedAreaRange, ['80_100', '80-100'], true) ? 'selected' : '' ?>>80 - 100 m²</option>
        <option value="100_150" <?= in_array($selectedAreaRange, ['100_150', '100-150'], true) ? 'selected' : '' ?>>100 - 150 m²</option>
        <option value="150_250" <?= in_array($selectedAreaRange, ['150_250', '150-250'], true) ? 'selected' : '' ?>>150 - 250 m²</option>
        <option value="250_500" <?= in_array($selectedAreaRange, ['250_500', '250-500'], true) ? 'selected' : '' ?>>250 - 500 m²</option>
        <option value="over_500" <?= in_array($selectedAreaRange, ['over_500', '500+'], true) ? 'selected' : '' ?>>&gt; 500 m²</option>
      </select>
    </div>

    <!-- 3. Số phòng ngủ (col-lg-2) -->
    <div class="col-6 col-md-3 col-lg-2">
      <select id="filterBedrooms" name="bedrooms" class="form-select">
        <option value="">Số phòng ngủ</option>
        <?php foreach ([1, 2, 3, 4] as $b): ?>
          <option value="<?= $b ?>" <?= $selectedBedrooms === (string)$b ? 'selected' : '' ?>><?= $b ?> phòng ngủ</option>
        <?php endforeach; ?>
        <option value="5+" <?= ($selectedBedrooms === '5+' || $selectedBedrooms === '5') ? 'selected' : '' ?>>5+ phòng ngủ</option>
      </select>
    </div>

    <!-- 4. Sắp xếp (col-lg-3) -->
    <div class="col-12 col-md-8 col-lg-3">
      <select id="filterSortBy" name="sort" class="form-select">
        <option value="newest" <?= $selectedSortBy === 'newest' ? 'selected' : '' ?>>Sắp xếp: Tin mới nhất</option>
        <option value="oldest" <?= $selectedSortBy === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
        <option value="price_asc" <?= $selectedSortBy === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
        <option value="price_desc" <?= $selectedSortBy === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
        <option value="area_asc" <?= $selectedSortBy === 'area_asc' ? 'selected' : '' ?>>Diện tích tăng dần</option>
        <option value="area_desc" <?= $selectedSortBy === 'area_desc' ? 'selected' : '' ?>>Diện tích giảm dần</option>
      </select>
    </div>

    <!-- 5. Nút Đặt lại (col-lg-2) -->
    <div class="col-12 col-md-4 col-lg-2 d-grid">
      <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100 fw-medium" title="Đặt lại bộ lọc">
        <i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại
      </button>
    </div>
  </div>

  <!-- Liên kết mở rộng bộ lọc khoảng giá / diện tích chi tiết (Collapse gọn bên dưới) -->
  <div class="d-flex justify-content-end mt-2">
    <button class="btn btn-link btn-sm text-decoration-none text-secondary p-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#customRangesCollapse"
            aria-expanded="<?= $hasCustomRange ? 'true' : 'false' ?>">
      <i class="bi bi-sliders me-1"></i>Tùy chỉnh khoảng giá / diện tích chi tiết
    </button>
  </div>

  <div class="collapse <?= $hasCustomRange ? 'show' : '' ?> mt-2" id="customRangesCollapse">
    <div class="row g-2 pt-2 border-top bg-light rounded-3 p-2">
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted mb-1">Hình thức</label>
        <select id="filterListingType" name="listing_type" class="form-select form-select-sm">
          <option value="">Mua bán & Cho thuê</option>
          <option value="sale" <?= $selectedListingType === 'sale' ? 'selected' : '' ?>>Nhà đất bán</option>
          <option value="rent" <?= $selectedListingType === 'rent' ? 'selected' : '' ?>>Nhà đất cho thuê</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted mb-1">Giá tối thiểu (VNĐ)</label>
        <input id="filterMinPrice" name="min_price" type="number" min="0" step="1000000" class="form-control form-control-sm" placeholder="VD: 1000000000" value="<?= e($filters['min_price'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted mb-1">Giá tối đa (VNĐ)</label>
        <input id="filterMaxPrice" name="max_price" type="number" min="0" step="1000000" class="form-control form-control-sm" placeholder="VD: 5000000000" value="<?= e($filters['max_price'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted mb-1">Diện tích từ (m²)</label>
        <input id="filterMinArea" name="min_area" type="number" min="0" step="1" class="form-control form-control-sm" placeholder="VD: 50" value="<?= e($filters['min_area'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted mb-1">Diện tích đến (m²)</label>
        <input id="filterMaxArea" name="max_area" type="number" min="0" step="1" class="form-control form-control-sm" placeholder="VD: 150" value="<?= e($filters['max_area'] ?? '') ?>">
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  var provSelect = document.getElementById('searchProvince');
  var wardSelect = document.getElementById('searchWard');
  if (!provSelect || !wardSelect || provSelect.dataset.wardDirectBound === '1') return;
  provSelect.dataset.wardDirectBound = '1';

  function getBaseUrl() {
    var base = (typeof window.APP_BASE !== 'undefined') ? String(window.APP_BASE) : '';
    return base.replace(/\/+$/, '');
  }

  function renderWards(items, selectedId) {
    var placeholder = wardSelect.dataset.placeholder || 'Phường/Xã';
    wardSelect.innerHTML = '';
    var defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = placeholder;
    wardSelect.appendChild(defaultOpt);

    var seenNames = Object.create(null);
    (items || []).forEach(function (item) {
      var cleanName = String(item.name || '').trim();
      if (!cleanName || seenNames[cleanName]) return;
      seenNames[cleanName] = true;

      var opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = cleanName;
      if (selectedId && String(item.id) === String(selectedId)) {
        opt.selected = true;
      }
      wardSelect.appendChild(opt);
    });
  }

  async function loadWardsByProvince(provinceId, selectedId) {
    if (!provinceId) {
      renderWards([], '');
      return;
    }
    try {
      var res = await fetch(getBaseUrl() + '/api/wards/' + encodeURIComponent(provinceId) + '?province_id=' + encodeURIComponent(provinceId), {
        headers: { 'Accept': 'application/json' }
      });
      var json = await res.json();
      var list = Array.isArray(json) ? json : (json.data || []);
      renderWards(list, selectedId);
    } catch (e) {
      console.error('Lỗi tải danh sách Phường/Xã:', e);
    }
  }

  provSelect.addEventListener('change', function () {
    wardSelect.dataset.selected = '';
    loadWardsByProvince(provSelect.value, '');
  });

  if (provSelect.value && wardSelect.options.length <= 1) {
    loadWardsByProvince(provSelect.value, wardSelect.dataset.selected || '');
  }
})();
</script>
