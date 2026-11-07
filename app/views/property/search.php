<?php
/**
 * [C - Thiện] Giao diện tìm kiếm bất động sản nâng cao
 * - Bộ lọc đa tiêu chí (Từ khóa, Giá, Diện tích, PN, Loại BĐS, Địa phương 3 cấp, Sắp xếp)
 * - Lọc và phân trang mượt mà qua AJAX, không reload trang
 * - Đồng bộ URL trình duyệt (history.pushState)
 * - Tích hợp bản đồ Leaflet OpenStreetMap xem vị trí bất động sản
 * - Lưu và quản lý bộ lọc tìm kiếm (Database & localStorage)
 *
 * @var array $filters
 * @var array $properties
 * @var int $total
 * @var int $page
 * @var int $totalPages
 * @var int $limit
 * @var array $provinces
 * @var array $districts
 * @var array $wards
 * @var array $propertyTypes
 * @var array $savedSearches
 */

// Chuẩn bị dữ liệu ban đầu cho bản đồ Leaflet
$initialMapProperties = array_map(function ($p) {
    return [
        'id'              => (int)$p['id'],
        'title'           => $p['title'],
        'price'           => (float)$p['price'],
        'formatted_price' => format_price($p['price']),
        'area'            => (float)$p['area'],
        'bedrooms'        => (int)$p['bedrooms'],
        'listing_type'    => $p['listing_type'],
        'type_name'       => $p['type_name'] ?? '',
        'latitude'        => !empty($p['latitude']) ? (float)$p['latitude'] : null,
        'longitude'       => !empty($p['longitude']) ? (float)$p['longitude'] : null,
        'district_name'   => $p['district_name'] ?? '',
        'ward_name'       => $p['ward_name'] ?? '',
        'cover_url'       => !empty($p['cover_url']) ? base_url($p['cover_url']) : '',
        'detail_url'      => base_url('property/detail/' . $p['id']),
    ];
}, $properties);
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="search-page-container">
  <!-- Tiêu đề & Thống kê nhanh -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h1 class="h4 fw-bold mb-1">Tìm kiếm bất động sản</h1>
      <p class="text-muted small mb-0">
        Tìm thấy <strong id="totalCountBadge" class="text-primary fs-6"><?= (int)$total ?></strong> bất động sản phù hợp tiêu chí
      </p>
    </div>

    <!-- Nút chuyển đổi chế độ xem: Dạng Lưới vs Bản đồ -->
    <div class="btn-group btn-group-sm" role="group" aria-label="Chế độ xem">
      <button type="button" class="btn btn-outline-primary active" id="btnViewGrid">
        <span class="me-1">📋</span> Danh sách
      </button>
      <button type="button" class="btn btn-outline-primary" id="btnViewMap">
        <span class="me-1">🗺️</span> Bản đồ Leaflet
      </button>
      <button type="button" class="btn btn-outline-primary d-none d-lg-inline-block" id="btnViewSplit">
        <span class="me-1">🗂️</span> Song song
      </button>
    </div>
  </div>

  <!-- Form bộ lọc nâng cao -->
  <div class="mb-4">
    <?php require APP . '/views/partials/search_form.php'; ?>
  </div>

  <!-- Vùng hiển thị kết quả (Hỗ trợ Grid View, Map View và Split View) -->
  <div class="row g-4" id="searchMainContent">
    <!-- Cột danh sách tin đăng -->
    <div class="col-12" id="listColumn">
      <!-- Loading indicator overlay -->
      <div id="resultsLoadingOverlay" class="position-relative">
        <div id="resultsSpinner" class="d-none text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Đang tải...</span>
          </div>
          <p class="text-muted small mt-2">Đang tìm kiếm bất động sản phù hợp...</p>
        </div>

        <div class="row g-3" id="propertiesGrid">
          <?php if (empty($properties)): ?>
            <div class="col-12 py-5 text-center text-muted empty-state-box">
              <div class="fs-1 mb-2">🔍</div>
              <h5 class="fw-semibold text-dark">Không tìm thấy bất động sản phù hợp</h5>
              <p class="small text-muted mb-3">Hãy thử thay đổi khoảng giá, diện tích hoặc bỏ bớt các tiêu chí lọc để xem thêm kết quả.</p>
              <button type="button" class="btn btn-outline-primary btn-sm btn-reset-filters">Xóa bộ lọc & Tìm lại</button>
            </div>
          <?php else: ?>
            <?php foreach ($properties as $p): ?>
              <?php require APP . '/views/partials/property_card.php'; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Khung phân trang động qua AJAX -->
      <div id="paginationContainer">
        <?php (new PropertyController())->renderPagination($page, $totalPages, $total, $limit); ?>
      </div>
    </div>

    <!-- Cột bản đồ Leaflet OpenStreetMap (Ẩn mặc định trên mobile, hiển thị khi chọn Map View hoặc Split View) -->
    <div class="col-12 d-none" id="mapColumn">
      <div class="card shadow-sm border sticky-top" style="top: 1rem; z-index: 10;">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
          <span class="small fw-bold text-dark">🗺️ Bản đồ vị trí bất động sản</span>
          <span class="badge text-bg-info small" id="mapMarkerCountBadge">Hiển thị tọa độ</span>
        </div>
        <div class="card-body p-0">
          <div id="searchMap" style="height: 580px; width: 100%; border-radius: 0 0 6px 6px;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Dữ liệu ban đầu truyền vào JS -->
<script>
  window.INITIAL_SEARCH_DATA = {
    properties: <?= json_encode($initialMapProperties, JSON_UNESCAPED_UNICODE) ?>,
    total: <?= (int)$total ?>,
    page: <?= (int)$page ?>,
    totalPages: <?= (int)$totalPages ?>,
    limit: <?= (int)$limit ?>
  };
</script>

<!-- Leaflet JS & Map Manager -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= e(base_url('assets/js/map.js')) ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('searchFilterForm');
  const propertiesGrid = document.getElementById('propertiesGrid');
  const paginationContainer = document.getElementById('paginationContainer');
  const totalCountBadge = document.getElementById('totalCountBadge');
  const searchSpinner = document.getElementById('searchSpinner');
  const btnSubmitSearch = document.getElementById('btnSubmitSearch');
  const submitSpinner = document.getElementById('searchSpinner');

  const listColumn = document.getElementById('listColumn');
  const mapColumn = document.getElementById('mapColumn');
  const btnViewGrid = document.getElementById('btnViewGrid');
  const btnViewMap = document.getElementById('btnViewMap');
  const btnViewSplit = document.getElementById('btnViewSplit');
  const mapMarkerCountBadge = document.getElementById('mapMarkerCountBadge');

  let currentViewMode = 'grid'; // 'grid' | 'map' | 'split'
  let searchMapInstance = null;

  /**
   * Khởi tạo bản đồ tìm kiếm khi cần
   */
  function getOrInitMap() {
    if (!searchMapInstance && window.BdsMap && document.getElementById('searchMap')) {
      searchMapInstance = window.BdsMap.initSearchMap({
        mapId: 'searchMap',
        properties: window.INITIAL_SEARCH_DATA?.properties || []
      });
    }
    return searchMapInstance;
  }

  /**
   * Cập nhật giao diện chuyển đổi View (Grid, Map, Split)
   */
  function setViewMode(mode) {
    currentViewMode = mode;
    [btnViewGrid, btnViewMap, btnViewSplit].forEach(btn => btn?.classList.remove('active'));

    if (mode === 'grid') {
      btnViewGrid?.classList.add('active');
      listColumn.className = 'col-12';
      listColumn.classList.remove('d-none');
      mapColumn.classList.add('d-none');
    } else if (mode === 'map') {
      btnViewMap?.classList.add('active');
      listColumn.classList.add('d-none');
      mapColumn.className = 'col-12';
      mapColumn.classList.remove('d-none');
      const mapObj = getOrInitMap();
      setTimeout(() => mapObj?.invalidateSize(), 150);
    } else if (mode === 'split') {
      btnViewSplit?.classList.add('active');
      listColumn.className = 'col-12 col-lg-7';
      listColumn.classList.remove('d-none');
      mapColumn.className = 'col-12 col-lg-5';
      mapColumn.classList.remove('d-none');
      const mapObj = getOrInitMap();
      setTimeout(() => mapObj?.invalidateSize(), 150);
    }
  }

  btnViewGrid?.addEventListener('click', () => setViewMode('grid'));
  btnViewMap?.addEventListener('click', () => setViewMode('map'));
  btnViewSplit?.addEventListener('click', () => setViewMode('split'));

  /**
   * Thực hiện tìm kiếm và phân trang bằng AJAX
   */
  async function performSearch(page = 1, updateUrl = true) {
    if (!form) return;

    // Lấy query string từ form
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
      if (value !== '' && key !== '_csrf') {
        params.append(key, value);
      }
    }
    params.set('page', page);

    // Hiển thị trạng thái đang tải
    propertiesGrid.style.opacity = '0.3';
    submitSpinner?.classList.remove('d-none');
    if (btnSubmitSearch) btnSubmitSearch.disabled = true;

    try {
      const searchUrl = `${BASE_URL}/property/search?${params.toString()}`;
      const res = await fetch(searchUrl, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!res.ok) throw new Error('Không thể tải dữ liệu tìm kiếm.');
      const data = await res.json();

      if (data.success && data.data) {
        const payload = data.data;

        // 1. Cập nhật HTML danh sách cards
        propertiesGrid.innerHTML = payload.html;

        // 2. Cập nhật phân trang
        paginationContainer.innerHTML = payload.paginationHtml || '';

        // 3. Cập nhật tổng số kết quả
        if (totalCountBadge) {
          totalCountBadge.textContent = payload.total;
        }

        // 4. Cập nhật bản đồ Leaflet
        if (searchMapInstance) {
          searchMapInstance.updateMarkers(payload.properties || []);
        } else if (payload.properties && payload.properties.length > 0) {
          const mapObj = getOrInitMap();
          mapObj?.updateMarkers(payload.properties);
        }

        if (mapMarkerCountBadge) {
          const markerCount = (payload.properties || []).filter(p => p.latitude && p.longitude).length;
          mapMarkerCountBadge.textContent = `${markerCount} vị trí có tọa độ`;
        }

        // 5. Cập nhật URL trình duyệt (history.pushState)
        if (updateUrl) {
          const newUrl = `${BASE_URL}/property/search?${params.toString()}`;
          window.history.pushState({ page, params: params.toString() }, '', newUrl);
        }

        // Cuộn mượt lên đầu danh sách nếu đang ở trang 2 trở đi
        if (page > 1) {
          window.scrollTo({ top: form.offsetTop - 20, behavior: 'smooth' });
        }
      }
    } catch (err) {
      console.error('Lỗi tìm kiếm AJAX:', err);
      window.alert('Đã xảy ra lỗi khi tìm kiếm bất động sản. Vui lòng thử lại.');
    } finally {
      propertiesGrid.style.opacity = '1';
      submitSpinner?.classList.add('d-none');
      if (btnSubmitSearch) btnSubmitSearch.disabled = false;
    }
  }

  // Bắt sự kiện submit form tìm kiếm
  form?.addEventListener('submit', function (e) {
    e.preventDefault();
    performSearch(1, true);
  });

  // Tự động tìm kiếm khi đổi các bộ lọc nhanh (Sắp xếp, Số phòng ngủ, Loại tin, Khoảng giá, Diện tích)
  ['#filterSortBy', '#filterBedrooms', '#filterListingType', '#filterTypeId', '#filterPriceRange', '#filterAreaRange'].forEach(sel => {
    document.querySelector(sel)?.addEventListener('change', () => {
      performSearch(1, true);
    });
  });

  // Bắt sự kiện click chuyển trang AJAX
  paginationContainer?.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-page]');
    if (!link) return;
    e.preventDefault();
    const targetPage = parseInt(link.dataset.page, 10);
    if (!isNaN(targetPage) && targetPage > 0) {
      performSearch(targetPage, true);
    }
  });

  // Xử lý nút Back/Forward trên trình duyệt (popstate)
  window.addEventListener('popstate', function (e) {
    const currentParams = new URLSearchParams(window.location.search);
    // Điền lại các giá trị vào form
    for (const [k, v] of currentParams.entries()) {
      const el = form.elements[k];
      if (el) el.value = v;
    }
    const page = parseInt(currentParams.get('page') || '1', 10);
    performSearch(page, false);
  });

  // Toggle mục nhập khoảng giá/diện tích min max tùy chỉnh
  const toggleBtn = document.getElementById('toggleCustomRangesBtn');
  const customCollapse = document.getElementById('customRangesCollapse');
  toggleBtn?.addEventListener('click', () => {
    customCollapse?.classList.toggle('d-none');
  });

  // Đặt lại tất cả bộ lọc
  function resetAllFilters() {
    form.reset();
    document.querySelectorAll('#searchFilterForm select').forEach(sel => sel.value = '');
    document.querySelectorAll('#searchFilterForm input[type="text"], #searchFilterForm input[type="number"]').forEach(inp => inp.value = '');
    performSearch(1, true);
  }

  document.getElementById('btnResetFilter')?.addEventListener('click', resetAllFilters);
  document.addEventListener('click', function (e) {
    if (e.target.matches('.btn-reset-filters')) {
      resetAllFilters();
    }
  });

  // ==========================================
  // QUẢN LÝ LƯU & PHỤC HỒI BỘ LỌC TÌM KIẾM (TASK 5)
  // ==========================================
  const modalSaveFilter = new bootstrap.Modal(document.getElementById('modalSaveFilter'));
  const btnSaveFilterModal = document.getElementById('btnSaveFilterModal');
  const btnConfirmSaveFilter = document.getElementById('btnConfirmSaveFilter');
  const saveFilterNameInput = document.getElementById('saveFilterNameInput');
  const saveToAccountCheck = document.getElementById('saveToAccountCheck');
  const savedFiltersList = document.getElementById('savedFiltersList');

  // Lấy danh sách bộ lọc từ localStorage
  function getLocalSavedFilters() {
    try {
      return JSON.parse(localStorage.getItem('bds_saved_filters') || '[]');
    } catch {
      return [];
    }
  }

  function setLocalSavedFilters(list) {
    localStorage.setItem('bds_saved_filters', JSON.stringify(list));
  }

  // Mở modal lưu bộ lọc
  btnSaveFilterModal?.addEventListener('click', () => {
    const kw = document.getElementById('filterKeyword')?.value || '';
    const defName = kw ? `Bộ lọc: "${kw}"` : `Bộ lọc ${new Date().toLocaleDateString('vi-VN')} ${new Date().toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'})}`;
    if (saveFilterNameInput) saveFilterNameInput.value = defName;
    modalSaveFilter.show();
  });

  // Xác nhận lưu bộ lọc
  btnConfirmSaveFilter?.addEventListener('click', async () => {
    const name = (saveFilterNameInput?.value || '').trim();
    if (!name) {
      alert('Vui lòng nhập tên cho bộ lọc.');
      return;
    }

    const currentFilters = {};
    const formData = new FormData(form);
    for (const [k, v] of formData.entries()) {
      if (v !== '' && k !== '_csrf') {
        currentFilters[k] = v;
      }
    }

    // Nếu chọn lưu vào tài khoản
    if (saveToAccountCheck && saveToAccountCheck.checked) {
      try {
        const res = await api('property/saveSearch', {
          method: 'POST',
          body: {
            name: name,
            filters: JSON.stringify(currentFilters),
          }
        });

        if (res.success) {
          modalSaveFilter.hide();
          alert('Đã lưu bộ lọc vào tài khoản thành công!');
          renderSavedFilterItem({
            id: res.data.id,
            name: res.data.name,
            filters: currentFilters,
            is_db: true,
            created_at: new Date().toISOString()
          });
        } else {
          alert(res.message || 'Không thể lưu bộ lọc.');
        }
      } catch (err) {
        alert('Lỗi kết nối máy chủ.');
      }
    } else {
      // Lưu vào localStorage
      const localFilters = getLocalSavedFilters();
      const newFilter = {
        id: 'local_' + Date.now(),
        name: name,
        filters: currentFilters,
        is_db: false,
        created_at: new Date().toISOString()
      };
      localFilters.unshift(newFilter);
      setLocalSavedFilters(localFilters);

      modalSaveFilter.hide();
      alert('Đã lưu bộ lọc vào trình duyệt thành công!');
      renderSavedFilterItem(newFilter);
    }
  });

  function renderSavedFilterItem(item) {
    const emptyNotice = savedFiltersList?.querySelector('.empty-saved-text');
    if (emptyNotice) emptyNotice.remove();

    const li = document.createElement('li');
    li.className = 'd-flex justify-content-between align-items-center px-3 py-2 border-bottom saved-filter-item';
    li.dataset.id = item.id;
    li.dataset.filters = JSON.stringify(item.filters);

    const sourceTag = item.is_db ? '<span class="badge text-bg-primary" style="font-size:0.65rem">Tài khoản</span>' : '<span class="badge text-bg-secondary" style="font-size:0.65rem">Máy này</span>';

    li.innerHTML = `
      <a href="#" class="text-decoration-none text-dark flex-grow-1 text-truncate me-2 btn-apply-saved-filter" title="Áp dụng bộ lọc này">
        <strong>${item.name}</strong> ${sourceTag}
        <span class="d-block text-muted small" style="font-size: 0.75rem;">${new Date(item.created_at).toLocaleString('vi-VN')}</span>
      </a>
      <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1 btn-delete-saved-filter" title="Xóa" data-id="${item.id}">&times;</button>
    `;
    savedFiltersList?.prepend(li);
  }

  // Tải thêm các bộ lọc từ localStorage vào dropdown
  const localList = getLocalSavedFilters();
  localList.forEach(item => renderSavedFilterItem(item));

  // Áp dụng bộ lọc đã lưu khi click
  savedFiltersList?.addEventListener('click', function (e) {
    const applyLink = e.target.closest('.btn-apply-saved-filter');
    const deleteBtn = e.target.closest('.btn-delete-saved-filter');

    if (applyLink) {
      e.preventDefault();
      const li = applyLink.closest('.saved-filter-item');
      if (!li || !li.dataset.filters) return;

      try {
        const filterObj = JSON.parse(li.dataset.filters);
        form.reset();
        for (const [k, v] of Object.entries(filterObj)) {
          const el = form.elements[k];
          if (el) el.value = v;
        }
        // Gọi tìm kiếm AJAX với bộ lọc mới
        performSearch(1, true);
      } catch (err) {
        console.error('Lỗi khôi phục bộ lọc:', err);
      }
    } else if (deleteBtn) {
      e.preventDefault();
      const id = deleteBtn.dataset.id;
      if (!confirm('Bạn có chắc muốn xóa bộ lọc này?')) return;

      if (id.startsWith('local_')) {
        let list = getLocalSavedFilters();
        list = list.filter(x => x.id !== id);
        setLocalSavedFilters(list);
        deleteBtn.closest('.saved-filter-item')?.remove();
      } else {
        // Xóa trên DB
        api('property/deleteSavedSearch/' + id, { method: 'POST' }).then(res => {
          if (res.success) {
            deleteBtn.closest('.saved-filter-item')?.remove();
          } else {
            alert(res.message || 'Không thể xóa bộ lọc.');
          }
        });
      }
    }
  });
});
</script>
