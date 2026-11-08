<?php
/**
 * [C - Thiện] Form tìm kiếm & bộ lọc bất động sản đầy đủ tiêu chí
 * Hỗ trợ: Từ khóa, Loại tin, Loại BĐS, Địa phương 3 cấp (Tỉnh-Quận-Phường),
 *         Khoảng giá, Khoảng diện tích (<30 -> >500m2), Số phòng ngủ (1 -> 5+), Sắp xếp,
 *         Lưu bộ lọc tìm kiếm & Khôi phục nhanh.
 *
 * @var array $filters
 * @var array|null $provinces
 * @var array|null $propertyTypes
 * @var array|null $savedSearches
 */
$filters = $filters ?? [];
$provinces = $provinces ?? (new Location())->provinces();
$propertyTypes = $propertyTypes ?? (new PropertyType())->allSorted();
$savedSearches = $savedSearches ?? (Auth::check() ? (new SavedSearch())->getByUser((int)Auth::id()) : []);

$selectedProvinceId = (int)($filters['province_id'] ?? 0);
$selectedDistrictId = (int)($filters['district_id'] ?? 0);
$selectedWardId     = (int)($filters['ward_id'] ?? 0);
$selectedTypeId     = (int)($filters['type_id'] ?? 0);
$selectedListingType= (string)($filters['listing_type'] ?? '');
$selectedBedrooms   = (string)($filters['bedrooms'] ?? '');
$selectedSortBy     = (string)($filters['sort_by'] ?? 'newest');
$selectedPriceRange = (string)($filters['price_range'] ?? '');
$selectedAreaRange  = (string)($filters['area_range'] ?? '');
?>

<form id="searchFilterForm" class="search-filter-card bg-white p-3 p-md-4 rounded-3 shadow-sm border" method="get" action="<?= e(base_url('property/search')) ?>">
  <!-- Dòng 1: Tìm kiếm chính (Từ khóa, Loại tin, Loại BĐS, Tỉnh/Thành, Nút Tìm) -->
  <div class="row g-2 align-items-center mb-2">
    <div class="col-12 col-md-4 position-relative" id="keywordAutocompleteWrap">
      <div class="input-group">
        <span class="input-group-text bg-light text-muted border-end-0">🔍</span>
        <input type="text" id="filterKeyword" name="keyword" data-input-alias="keywordInput searchKeyword" class="form-control border-start-0"
               placeholder="Từ khóa: tên đường, dự án, tòa nhà..."
               value="<?= e($filters['keyword'] ?? '') ?>" autocomplete="off"
               aria-autocomplete="list" aria-controls="searchAddressSuggestions" aria-expanded="false">
      </div>
      <!-- Dropdown gợi ý địa điểm tự động -->
      <div id="searchAddressSuggestions"
           class="list-group position-absolute w-100 shadow-lg border-0 d-none"
           role="listbox"
           aria-label="Gợi ý địa điểm"
           style="top: calc(100% + 6px); left: 0; z-index: 1050; border-radius: 12px; overflow: hidden; max-height: 340px; overflow-y: auto; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);"></div>
    </div>
    <div class="col-6 col-md-2">
      <select id="filterListingType" name="listing_type" class="form-select">
        <option value="">Hình thức: Tất cả</option>
        <option value="sale" <?= $selectedListingType === 'sale' ? 'selected' : '' ?>>Mua bán</option>
        <option value="rent" <?= $selectedListingType === 'rent' ? 'selected' : '' ?>>Cho thuê</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select id="filterTypeId" name="type_id" class="form-select">
        <option value="">Loại BĐS: Tất cả</option>
        <?php foreach ($propertyTypes as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $selectedTypeId === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select id="searchProvince" name="province_id" class="form-select location-select" data-target="#searchDistrict">
        <option value="">Tỉnh/Thành phố</option>
        <?php foreach ($provinces as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>" <?= $selectedProvinceId === (int)$pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2 d-grid">
      <button type="submit" id="btnSubmitSearch" class="btn btn-warning fw-semibold shadow-sm">
        <span class="spinner-border spinner-border-sm me-1 d-none" id="searchSpinner" role="status"></span>
        <span>Tìm kiếm</span>
      </button>
    </div>
  </div>

  <!-- Dòng 2: Bộ lọc chi tiết (Quận/Huyện, Phường/Xã, Khoảng giá, Diện tích, Số phòng ngủ) -->
  <div class="row g-2 align-items-center mb-2">
    <div class="col-6 col-md-2">
      <select id="searchDistrict" name="district_id" class="form-select location-select" data-target="#searchWard" data-level="wards" data-selected="<?= $selectedDistrictId ?: '' ?>">
        <option value="">Quận/Huyện</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select id="searchWard" name="ward_id" class="form-select location-select" data-selected="<?= $selectedWardId ?: '' ?>">
        <option value="">Phường/Xã</option>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select id="filterPriceRange" name="price_range" class="form-select">
        <option value="">Khoảng giá: Tất cả</option>
        <option value="0-500m" <?= $selectedPriceRange === '0-500m' ? 'selected' : '' ?>>&lt; 500 triệu</option>
        <option value="500m-800m" <?= $selectedPriceRange === '500m-800m' ? 'selected' : '' ?>>500 - 800 triệu</option>
        <option value="800m-1b" <?= $selectedPriceRange === '800m-1b' ? 'selected' : '' ?>>800 triệu - 1 tỷ</option>
        <option value="1b-2b" <?= $selectedPriceRange === '1b-2b' ? 'selected' : '' ?>>1 - 2 tỷ</option>
        <option value="2b-3b" <?= $selectedPriceRange === '2b-3b' ? 'selected' : '' ?>>2 - 3 tỷ</option>
        <option value="3b-5b" <?= $selectedPriceRange === '3b-5b' ? 'selected' : '' ?>>3 - 5 tỷ</option>
        <option value="5b-7b" <?= $selectedPriceRange === '5b-7b' ? 'selected' : '' ?>>5 - 7 tỷ</option>
        <option value="7b-10b" <?= $selectedPriceRange === '7b-10b' ? 'selected' : '' ?>>7 - 10 tỷ</option>
        <option value="10b-20b" <?= $selectedPriceRange === '10b-20b' ? 'selected' : '' ?>>10 - 20 tỷ</option>
        <option value="20b+" <?= $selectedPriceRange === '20b+' ? 'selected' : '' ?>>&gt; 20 tỷ</option>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select id="filterAreaRange" name="area_range" class="form-select">
        <option value="">Diện tích: Tất cả</option>
        <option value="0-30" <?= $selectedAreaRange === '0-30' ? 'selected' : '' ?>>&lt; 30 m²</option>
        <option value="30-50" <?= $selectedAreaRange === '30-50' ? 'selected' : '' ?>>30 - 50 m²</option>
        <option value="50-80" <?= $selectedAreaRange === '50-80' ? 'selected' : '' ?>>50 - 80 m²</option>
        <option value="80-100" <?= $selectedAreaRange === '80-100' ? 'selected' : '' ?>>80 - 100 m²</option>
        <option value="100-150" <?= $selectedAreaRange === '100-150' ? 'selected' : '' ?>>100 - 150 m²</option>
        <option value="150-200" <?= $selectedAreaRange === '150-200' ? 'selected' : '' ?>>150 - 200 m²</option>
        <option value="200-250" <?= $selectedAreaRange === '200-250' ? 'selected' : '' ?>>200 - 250 m²</option>
        <option value="250-300" <?= $selectedAreaRange === '250-300' ? 'selected' : '' ?>>250 - 300 m²</option>
        <option value="300-500" <?= $selectedAreaRange === '300-500' ? 'selected' : '' ?>>300 - 500 m²</option>
        <option value="500+" <?= $selectedAreaRange === '500+' ? 'selected' : '' ?>>&gt; 500 m²</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select id="filterBedrooms" name="bedrooms" class="form-select">
        <option value="">Số phòng ngủ</option>
        <option value="1" <?= $selectedBedrooms === '1' ? 'selected' : '' ?>>1 phòng ngủ</option>
        <option value="2" <?= $selectedBedrooms === '2' ? 'selected' : '' ?>>2 phòng ngủ</option>
        <option value="3" <?= $selectedBedrooms === '3' ? 'selected' : '' ?>>3 phòng ngủ</option>
        <option value="4" <?= $selectedBedrooms === '4' ? 'selected' : '' ?>>4 phòng ngủ</option>
        <option value="5" <?= $selectedBedrooms === '5' ? 'selected' : '' ?>>5+ phòng ngủ</option>
      </select>
    </div>
  </div>

  <!-- Dòng 3: Tùy chỉnh nâng cao (Min/Max Giá, Min/Max Diện tích) - Có thể mở rộng -->
  <div id="customRangesCollapse" class="row g-2 align-items-center mb-2 <?= (!empty($filters['min_price']) || !empty($filters['max_price']) || !empty($filters['min_area']) || !empty($filters['max_area'])) ? '' : 'd-none' ?>">
    <div class="col-6 col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text">Giá từ</span>
        <input type="number" name="min_price" id="filterMinPrice" class="form-control" placeholder="VNĐ" value="<?= e($filters['min_price'] ?? '') ?>" min="0">
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text">Đến</span>
        <input type="number" name="max_price" id="filterMaxPrice" class="form-control" placeholder="VNĐ" value="<?= e($filters['max_price'] ?? '') ?>" min="0">
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text">DT từ</span>
        <input type="number" name="min_area" id="filterMinArea" class="form-control" placeholder="m²" value="<?= e($filters['min_area'] ?? '') ?>" min="0" step="any">
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text">Đến</span>
        <input type="number" name="max_area" id="filterMaxArea" class="form-control" placeholder="m²" value="<?= e($filters['max_area'] ?? '') ?>" min="0" step="any">
      </div>
    </div>
  </div>

  <!-- Dòng 4: Sắp xếp, Tùy chỉnh chi tiết, Lưu bộ lọc, Đặt lại -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <label for="filterSortBy" class="small text-muted fw-semibold mb-0">Sắp xếp:</label>
      <select id="filterSortBy" name="sort_by" class="form-select form-select-sm" style="width: auto;">
        <option value="newest" <?= $selectedSortBy === 'newest' ? 'selected' : '' ?>>Tin mới nhất</option>
        <option value="oldest" <?= $selectedSortBy === 'oldest' ? 'selected' : '' ?>>Tin cũ nhất</option>
        <option value="price_asc" <?= $selectedSortBy === 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
        <option value="price_desc" <?= $selectedSortBy === 'price_desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
        <option value="area_asc" <?= $selectedSortBy === 'area_asc' ? 'selected' : '' ?>>Diện tích bé đến lớn</option>
        <option value="area_desc" <?= $selectedSortBy === 'area_desc' ? 'selected' : '' ?>>Diện tích lớn đến bé</option>
      </select>

      <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted p-0 ms-2" id="toggleCustomRangesBtn">
        ⚙️ Nhập min/max chi tiết
      </button>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
      <!-- Nút lưu bộ lọc -->
      <button type="button" class="btn btn-sm btn-outline-success" id="btnSaveFilterModal" title="Lưu lại bộ lọc hiện tại">
        💾 Lưu bộ lọc
      </button>

      <!-- Dropdown danh sách bộ lọc đã lưu -->
      <div class="dropdown" id="savedFiltersDropdownWrapper">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="savedFiltersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          ⭐ Bộ lọc đã lưu
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="savedFiltersDropdown" id="savedFiltersList" style="min-width: 280px; max-height: 360px; overflow-y: auto;">
          <?php if (empty($savedSearches)): ?>
            <li class="px-3 py-2 text-muted small text-center empty-saved-text">Chưa có bộ lọc nào được lưu.</li>
          <?php else: ?>
            <?php foreach ($savedSearches as $sf): ?>
              <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom saved-filter-item" data-id="<?= (int)$sf['id'] ?>" data-filters='<?= htmlspecialchars(json_encode($sf['filter_data']), ENT_QUOTES, 'UTF-8') ?>'>
                <a href="#" class="text-decoration-none text-dark flex-grow-1 text-truncate me-2 btn-apply-saved-filter" title="Áp dụng bộ lọc này">
                  <strong><?= e($sf['name']) ?></strong>
                  <span class="d-block text-muted small" style="font-size: 0.75rem;"><?= date('d/m/Y H:i', strtotime($sf['created_at'])) ?></span>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1 btn-delete-saved-filter" title="Xóa" data-id="<?= (int)$sf['id'] ?>">&times;</button>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Nút xóa bộ lọc / đặt lại -->
      <button type="button" class="btn btn-sm btn-outline-danger" id="btnResetFilter" title="Xóa tất cả điều kiện lọc">
        ↺ Đặt lại
      </button>
    </div>
  </div>
</form>

<!-- Modal Đặt tên lưu bộ lọc -->
<div class="modal fade" id="modalSaveFilter" tabindex="-1" aria-labelledby="modalSaveFilterLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold" id="modalSaveFilterLabel">Lưu bộ lọc tìm kiếm</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="saveFilterNameInput" class="form-label small fw-semibold">Tên bộ lọc gợi nhớ:</label>
          <input type="text" class="form-control" id="saveFilterNameInput" placeholder="VD: Căn hộ Q1 giá dưới 3 tỷ..." maxlength="100">
        </div>
        <div class="form-check form-check-inline small">
          <input class="form-check-input" type="checkbox" id="saveToAccountCheck" <?= Auth::check() ? 'checked' : 'disabled' ?>>
          <label class="form-check-label" for="saveToAccountCheck">
            Lưu vào tài khoản <?= Auth::check() ? '' : '(cần đăng nhập)' ?>
          </label>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-sm btn-success" id="btnConfirmSaveFilter">Lưu ngay</button>
      </div>
    </div>
  </div>
</div>
