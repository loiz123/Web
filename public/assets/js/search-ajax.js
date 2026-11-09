/**
 * Điều khiển Lọc Đa Tiêu Chí, Phân Trang AJAX, Skeleton Loading & Quản Lý Bộ Lọc Đã Lưu
 * Chuẩn UX Thương Mại BĐS Việt (Zero Layout Shift, Skeleton Shimmer, Toast & Modal Confirm)
 */
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('searchFilterForm');
  const propertiesGrid = document.getElementById('propertiesGrid');
  const paginationContainer = document.getElementById('paginationContainer');
  const totalCountBadge = document.getElementById('totalCountBadge');
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

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /**
   * Tạo danh sách 6 Skeleton Cards (.bds-skeleton-card) với khung ảnh 16:10,
   * thanh tiêu đề, thanh giá và hàng icon thông số khi đang lọc / chuyển trang AJAX
   */
  function renderSkeletonLoading(count = 6) {
    if (!propertiesGrid) return;
    const colClass = currentViewMode === 'split' ? 'col-12 col-md-6' : 'col-12 col-md-6 col-lg-4';
    let html = '';
    for (let i = 0; i < count; i++) {
      html += `
        <div class="${colClass}" aria-hidden="true">
          <div class="bds-skeleton-card">
            <div class="bds-skeleton-thumb"></div>
            <div class="bds-skeleton-body">
              <div class="bds-skeleton-line bds-skeleton-title"></div>
              <div class="bds-skeleton-line bds-skeleton-title-sub"></div>
              <div class="bds-skeleton-line bds-skeleton-price mt-1"></div>
              <div class="bds-skeleton-line bds-skeleton-location"></div>
              <div class="bds-skeleton-specs">
                <div class="bds-skeleton-spec-pill"></div>
                <div class="bds-skeleton-spec-pill"></div>
                <div class="bds-skeleton-spec-pill"></div>
              </div>
            </div>
          </div>
        </div>
      `;
    }
    propertiesGrid.style.opacity = '1';
    propertiesGrid.innerHTML = html;
  }

  function getOrInitMap() {
    if (!searchMapInstance && window.BdsMap && document.getElementById('searchMap')) {
      searchMapInstance = window.BdsMap.initSearchMap({
        mapId: 'searchMap',
        properties: window.INITIAL_SEARCH_DATA?.properties || []
      });
    }
    return searchMapInstance;
  }

  function setViewMode(mode) {
    currentViewMode = mode;
    [btnViewGrid, btnViewMap, btnViewSplit].forEach(btn => btn?.classList.remove('active'));

    if (mode === 'grid') {
      btnViewGrid?.classList.add('active');
      if (listColumn) {
        listColumn.className = 'col-12';
        listColumn.classList.remove('d-none');
      }
      mapColumn?.classList.add('d-none');
    } else if (mode === 'map') {
      btnViewMap?.classList.add('active');
      listColumn?.classList.add('d-none');
      if (mapColumn) {
        mapColumn.className = 'col-12';
        mapColumn.classList.remove('d-none');
      }
      const mapObj = getOrInitMap();
      setTimeout(() => mapObj?.invalidateSize(), 150);
    } else if (mode === 'split') {
      btnViewSplit?.classList.add('active');
      if (listColumn) {
        listColumn.className = 'col-12 col-lg-7';
        listColumn.classList.remove('d-none');
      }
      if (mapColumn) {
        mapColumn.className = 'col-12 col-lg-5';
        mapColumn.classList.remove('d-none');
      }
      const mapObj = getOrInitMap();
      setTimeout(() => mapObj?.invalidateSize(), 150);
    }
  }

  btnViewGrid?.addEventListener('click', () => setViewMode('grid'));
  btnViewMap?.addEventListener('click', () => setViewMode('map'));
  btnViewSplit?.addEventListener('click', () => setViewMode('split'));

  /**
   * Thực hiện lọc & phân trang bằng AJAX kèm Skeleton Loading và Fade-in mượt mà
   */
  async function performSearch(page = 1, updateUrl = true) {
    if (!form) return;

    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
      if (value !== '' && key !== '_csrf') {
        params.append(key, value);
      }
    }
    params.set('page', page);

    // Hiển thị ngay 6 Skeleton Cards thay vì làm mờ danh sách cũ
    renderSkeletonLoading(6);
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

        // 1. Thay thế Skeleton bằng danh sách tin thật với hiệu ứng fade-in mượt mà
        if (propertiesGrid) {
          propertiesGrid.innerHTML = payload.html;
          propertiesGrid.classList.remove('bds-fade-in');
          void propertiesGrid.offsetWidth; // trigger reflow
          propertiesGrid.classList.add('bds-fade-in');
          propertiesGrid.querySelectorAll(':scope > div').forEach((col, idx) => {
            col.classList.add('bds-fade-in');
            col.style.animationDelay = `${Math.min(idx * 35, 210)}ms`;
          });
        }

        if (window.PropertyCompare) window.PropertyCompare.renderUI();

        // 2. Cập nhật phân trang
        if (paginationContainer) {
          paginationContainer.innerHTML = payload.paginationHtml || '';
        }

        // 3. Cập nhật tổng số kết quả
        if (totalCountBadge) {
          totalCountBadge.textContent = payload.total;
        }

        // 4. Cập nhật bản đồ vị trí
        if (searchMapInstance) {
          searchMapInstance.updateMarkers(payload.properties || []);
        } else if (payload.properties && payload.properties.length > 0 && currentViewMode !== 'grid') {
          const mapObj = getOrInitMap();
          mapObj?.updateMarkers(payload.properties);
        }

        if (mapMarkerCountBadge) {
          const markerCount = (payload.properties || []).filter(p => p.latitude && p.longitude).length;
          mapMarkerCountBadge.textContent = `${markerCount} vị trí có tọa độ`;
        }

        // 5. Cập nhật URL trình duyệt
        if (updateUrl) {
          const newUrl = `${BASE_URL}/property/search?${params.toString()}`;
          window.history.pushState({ page, params: params.toString() }, '', newUrl);
        }

        if (page > 1 && form) {
          window.scrollTo({ top: Math.max(0, form.offsetTop - 20), behavior: 'smooth' });
        }
      }
    } catch (err) {
      console.error('Lỗi tìm kiếm AJAX:', err);
      if (window.BDS && typeof window.BDS.toast === 'function') {
        window.BDS.toast('Đã xảy ra lỗi khi tải danh sách bất động sản. Vui lòng thử lại.', 'danger', 'Lỗi kết nối');
      }
    } finally {
      if (propertiesGrid) propertiesGrid.style.opacity = '1';
      submitSpinner?.classList.add('d-none');
      if (btnSubmitSearch) btnSubmitSearch.disabled = false;
    }
  }

  function fetchResults(page = 1, updateUrl = true) {
    return performSearch(page, updateUrl);
  }

  // Cho phép gọi tạo skeleton hoặc tìm kiếm từ bên ngoài phục vụ kiểm thử / tương tác
  window.fetchResults = fetchResults;
  window.BdsSearchAjax = {
    performSearch: performSearch,
    fetchResults: fetchResults,
    renderSkeletonLoading: renderSkeletonLoading,
  };

  // =========================================================================
  // GỢI Ý ĐỊA CHỈ TỰ ĐỘNG (AUTOCOMPLETE) TRÊN Ô TỪ KHÓA TÌM KIẾM
  // =========================================================================
  const keywordInput = document.querySelector('#filterKeyword, #keywordInput, #searchKeyword, input[name="keyword"]');
  const suggestionsBox = document.getElementById('searchAddressSuggestions');
  let autocompleteTimer = null;
  let autocompleteAbortCtrl = null;

  const DEFAULT_POPULAR_LOCATIONS = [
    { display_name: 'Phường Thảo Điền, Thành phố Thủ Đức, Thành phố Hồ Chí Minh, Việt Nam', lat: 10.8031, lng: 106.7318 },
    { display_name: 'Quận 1, Thành phố Hồ Chí Minh, Việt Nam', lat: 10.7757, lng: 106.7004 },
    { display_name: 'Khu đô thị Phú Mỹ Hưng, Quận 7, Thành phố Hồ Chí Minh, Việt Nam', lat: 10.7295, lng: 106.7061 },
    { display_name: 'Quận Bình Thạnh, Thành phố Hồ Chí Minh, Việt Nam', lat: 10.8106, lng: 106.7091 },
    { display_name: 'Quận Hải Châu, Thành phố Đà Nẵng, Việt Nam', lat: 16.0544, lng: 108.2022 },
    { display_name: 'Quận Cầu Giấy, Thành phố Hà Nội, Việt Nam', lat: 21.0362, lng: 105.7906 }
  ];

  function hideAddressSuggestions() {
    if (!suggestionsBox) return;
    suggestionsBox.classList.add('d-none');
    suggestionsBox.innerHTML = '';
    keywordInput?.setAttribute('aria-expanded', 'false');
  }

  function highlightKeyword(displayName, keyword) {
    const safeText = escapeHtml(displayName || '');
    const cleanKw = (keyword || '').trim();
    if (!cleanKw) return safeText;
    const escapedKw = escapeHtml(cleanKw).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    if (!escapedKw) return safeText;
    const regex = new RegExp(`(${escapedKw})`, 'gi');
    return safeText.replace(regex, '<strong class="text-primary fw-bold">$1</strong>');
  }

  function renderSuggestionItems(items, keyword) {
    if (!suggestionsBox || !keywordInput) return;
    if (!Array.isArray(items) || !items.length) {
      hideAddressSuggestions();
      return;
    }

    suggestionsBox.innerHTML = items.map(item => {
      const displayName = String(item.display_name || '').trim();
      if (!displayName) return '';
      return `
        <button type="button"
                class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2 px-3 border-0 border-bottom search-suggestion-item"
                role="option"
                data-display-name="${escapeHtml(displayName)}"
                data-lat="${escapeHtml(item.lat ?? '')}"
                data-lng="${escapeHtml(item.lng ?? '')}">
          <span class="fs-6 lh-sm mt-1 flex-shrink-0">📍</span>
          <span class="flex-grow-1 text-start small text-dark lh-sm">${highlightKeyword(displayName, keyword)}</span>
        </button>
      `;
    }).join('');

    if (suggestionsBox.innerHTML.trim() !== '') {
      suggestionsBox.classList.remove('d-none');
      keywordInput.setAttribute('aria-expanded', 'true');
    } else {
      hideAddressSuggestions();
    }
  }

  function getPopularSuggestions(keyword) {
    const kw = (keyword || '').trim().toLowerCase();
    if (!kw) return DEFAULT_POPULAR_LOCATIONS;
    const matched = DEFAULT_POPULAR_LOCATIONS.filter(item =>
      String(item.display_name || '').toLowerCase().includes(kw)
    );
    return matched.length ? matched : DEFAULT_POPULAR_LOCATIONS;
  }

  async function fetchAddressSuggestions(keyword) {
    if (!suggestionsBox || !keywordInput) return;

    if (autocompleteAbortCtrl) {
      autocompleteAbortCtrl.abort();
    }
    autocompleteAbortCtrl = new AbortController();

    const basePrefix = (typeof BASE_URL === 'string' && BASE_URL) ? BASE_URL.replace(/\/+$/, '') : '/batdongsan/public';
    const endpoint = basePrefix
      ? `${basePrefix}/geocode?q=${encodeURIComponent(keyword)}`
      : `/batdongsan/public/geocode?q=${encodeURIComponent(keyword)}`;

    try {
      const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        signal: autocompleteAbortCtrl.signal,
      });

      if (!response.ok) {
        renderSuggestionItems(getPopularSuggestions(keyword), keyword);
        return;
      }

      const res = await response.json();
      const items = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : []);

      if (!items.length) {
        hideAddressSuggestions();
        return;
      }

      renderSuggestionItems(items, keyword);
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      renderSuggestionItems(getPopularSuggestions(keyword), keyword);
    }
  }

  function handleOpenAddressSuggestions() {
    if (!keywordInput) return;
    const keyword = (keywordInput.value || '').trim();
    if (keyword.length < 2) {
      renderSuggestionItems(getPopularSuggestions(keyword), keyword);
    } else {
      fetchAddressSuggestions(keyword);
    }
  }

  keywordInput?.addEventListener('focus', handleOpenAddressSuggestions);
  keywordInput?.addEventListener('click', handleOpenAddressSuggestions);

  keywordInput?.addEventListener('input', function () {
    clearTimeout(autocompleteTimer);
    const keyword = (keywordInput.value || '').trim();
    if (keyword.length < 2) {
      if (autocompleteAbortCtrl) autocompleteAbortCtrl.abort();
      if (keyword.length === 0) {
        renderSuggestionItems(DEFAULT_POPULAR_LOCATIONS, '');
      } else {
        renderSuggestionItems(getPopularSuggestions(keyword), keyword);
      }
      return;
    }
    autocompleteTimer = setTimeout(function () {
      fetchAddressSuggestions(keyword);
    }, 350);
  });

  keywordInput?.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      hideAddressSuggestions();
    }
  });

  suggestionsBox?.addEventListener('mousedown', function (e) {
    if (e.target.closest('.search-suggestion-item')) {
      e.preventDefault();
    }
  });

  suggestionsBox?.addEventListener('click', function (e) {
    const itemBtn = e.target.closest('.search-suggestion-item');
    if (!itemBtn || !keywordInput) return;
    e.preventDefault();
    const selectedAddress = itemBtn.getAttribute('data-display-name') || itemBtn.textContent.replace(/^📍\s*/, '').trim();
    keywordInput.value = selectedAddress;
    hideAddressSuggestions();
    if (propertiesGrid) {
      fetchResults(1, true);
    } else if (form) {
      form.submit();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      hideAddressSuggestions();
    }
  });

  document.addEventListener('click', function (e) {
    if (!suggestionsBox || suggestionsBox.classList.contains('d-none')) return;
    const clickedInsideInput = keywordInput && keywordInput.contains(e.target);
    const clickedInsideDropdown = suggestionsBox.contains(e.target);
    if (!clickedInsideInput && !clickedInsideDropdown) {
      hideAddressSuggestions();
    }
  });

  form?.addEventListener('submit', function (e) {
    if (!propertiesGrid) return;
    e.preventDefault();
    performSearch(1, true);
  });

  ['#filterSortBy', '#filterBedrooms', '#filterListingType', '#filterTypeId', '#filterPriceRange', '#filterAreaRange'].forEach(sel => {
    document.querySelector(sel)?.addEventListener('change', () => {
      if (!propertiesGrid) return;
      performSearch(1, true);
    });
  });

  paginationContainer?.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-page]');
    if (!link) return;
    e.preventDefault();
    const targetPage = parseInt(link.dataset.page, 10);
    if (!isNaN(targetPage) && targetPage > 0) {
      performSearch(targetPage, true);
    }
  });

  window.addEventListener('popstate', function () {
    if (!form) return;
    const currentParams = new URLSearchParams(window.location.search);
    for (const [k, v] of currentParams.entries()) {
      const el = form.elements[k];
      if (el) el.value = v;
    }
    const page = parseInt(currentParams.get('page') || '1', 10);
    performSearch(page, false);
  });

  const toggleBtn = document.getElementById('toggleCustomRangesBtn');
  const customCollapse = document.getElementById('customRangesCollapse');
  toggleBtn?.addEventListener('click', () => {
    customCollapse?.classList.toggle('d-none');
  });

  function resetAllFilters() {
    if (!form) return;
    form.reset();
    document.querySelectorAll('#searchFilterForm select').forEach(sel => sel.value = '');
    document.querySelectorAll('#searchFilterForm input[type="text"], #searchFilterForm input[type="number"]').forEach(inp => inp.value = '');
    performSearch(1, true);
    if (window.BDS && typeof window.BDS.toast === 'function') {
      window.BDS.toast('Đã đặt lại toàn bộ tiêu chí lọc tìm kiếm.', 'info', 'Đặt lại bộ lọc');
    }
  }

  document.getElementById('btnResetFilter')?.addEventListener('click', resetAllFilters);
  document.addEventListener('click', function (e) {
    if (e.target.matches('.btn-reset-filters')) {
      resetAllFilters();
    }
  });

  // =========================================================================
  // QUẢN LÝ LƯU & PHỤC HỒI BỘ LỌC TÌM KIẾM (VỚI TOAST & MODAL CONFIRM)
  // =========================================================================
  const modalSaveEl = document.getElementById('modalSaveFilter');
  const modalSaveFilter = (modalSaveEl && typeof bootstrap !== 'undefined' && bootstrap.Modal)
    ? new bootstrap.Modal(modalSaveEl)
    : null;
  const btnSaveFilterModal = document.getElementById('btnSaveFilterModal');
  const btnConfirmSaveFilter = document.getElementById('btnConfirmSaveFilter');
  const saveFilterNameInput = document.getElementById('saveFilterNameInput');
  const saveToAccountCheck = document.getElementById('saveToAccountCheck');
  const savedFiltersList = document.getElementById('savedFiltersList');

  function getLocalSavedFilters() {
    try {
      return JSON.parse(localStorage.getItem('bds_saved_filters') || '[]');
    } catch {
      return [];
    }
  }

  function setLocalSavedFilters(list) {
    try {
      localStorage.setItem('bds_saved_filters', JSON.stringify(list));
    } catch {}
  }

  btnSaveFilterModal?.addEventListener('click', () => {
    const kw = document.getElementById('filterKeyword')?.value || '';
    const defName = kw
      ? `Bộ lọc: "${kw}"`
      : `Bộ lọc ${new Date().toLocaleDateString('vi-VN')} ${new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}`;
    if (saveFilterNameInput) saveFilterNameInput.value = defName;
    modalSaveFilter?.show();
  });

  btnConfirmSaveFilter?.addEventListener('click', async () => {
    const name = (saveFilterNameInput?.value || '').trim();
    if (!name) {
      if (window.BDS) window.BDS.toast('Vui lòng nhập tên gợi nhớ cho bộ lọc tìm kiếm.', 'warning', 'Thiếu tên bộ lọc');
      saveFilterNameInput?.focus();
      return;
    }

    const currentFilters = {};
    if (form) {
      const formData = new FormData(form);
      for (const [k, v] of formData.entries()) {
        if (v !== '' && k !== '_csrf') {
          currentFilters[k] = v;
        }
      }
    }

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
          modalSaveFilter?.hide();
          if (window.BDS) {
            window.BDS.toast('Đã lưu bộ lọc tìm kiếm vào tài khoản của bạn.', 'success', 'Lưu bộ lọc thành công');
          }
          renderSavedFilterItem({
            id: String(res.data.id),
            name: res.data.name,
            filters: currentFilters,
            is_db: true,
            created_at: new Date().toISOString()
          });
        } else {
          if (window.BDS) {
            window.BDS.toast(res.message || 'Không thể lưu bộ lọc tìm kiếm lúc này.', 'danger', 'Lưu bộ lọc thất bại');
          }
        }
      } catch (err) {
        if (window.BDS) {
          window.BDS.toast('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.', 'danger', 'Lỗi kết nối');
        }
      }
    } else {
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

      modalSaveFilter?.hide();
      if (window.BDS) {
        window.BDS.toast('Đã lưu bộ lọc tìm kiếm trên trình duyệt thành công.', 'success', 'Lưu bộ lọc thành công');
      }
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

    const sourceTag = item.is_db
      ? '<span class="badge text-bg-primary" style="font-size:0.65rem">Tài khoản</span>'
      : '<span class="badge text-bg-secondary" style="font-size:0.65rem">Máy này</span>';

    li.innerHTML = `
      <a href="#" class="text-decoration-none text-dark flex-grow-1 text-truncate me-2 btn-apply-saved-filter" title="Áp dụng bộ lọc này">
        <strong>${escapeHtml(item.name)}</strong> ${sourceTag}
        <span class="d-block text-muted small" style="font-size: 0.75rem;">${new Date(item.created_at).toLocaleString('vi-VN')}</span>
      </a>
      <button type="button" class="btn btn-sm btn-outline-danger p-0 px-2 btn-delete-saved-filter" title="Xóa bộ lọc" data-id="${escapeHtml(item.id)}">&times;</button>
    `;
    savedFiltersList?.prepend(li);
  }

  const localList = getLocalSavedFilters();
  localList.forEach(item => renderSavedFilterItem(item));

  savedFiltersList?.addEventListener('click', async function (e) {
    const applyLink = e.target.closest('.btn-apply-saved-filter');
    const deleteBtn = e.target.closest('.btn-delete-saved-filter');

    if (applyLink) {
      e.preventDefault();
      const li = applyLink.closest('.saved-filter-item');
      if (!li || !li.dataset.filters || !form) return;

      try {
        const filterObj = JSON.parse(li.dataset.filters);
        form.reset();
        for (const [k, v] of Object.entries(filterObj)) {
          if (k === 'district_id' || k === 'ward_id') continue;
          const el = form.elements[k];
          if (el) el.value = v;
        }
        const district = form.elements.district_id;
        const ward = form.elements.ward_id;
        if (typeof BdsLocation !== 'undefined') {
          await BdsLocation.load(district, 'districts', form.elements.province_id?.value, filterObj.district_id);
          await BdsLocation.load(ward, 'wards', district?.value, filterObj.ward_id);
        }
        performSearch(1, true);
        if (window.BDS) {
          window.BDS.toast('Đã khôi phục và áp dụng bộ lọc tìm kiếm.', 'info', 'Áp dụng bộ lọc');
        }
      } catch (err) {
        console.error('Lỗi khôi phục bộ lọc:', err);
      }
    } else if (deleteBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = String(deleteBtn.dataset.id || '');
      const itemEl = deleteBtn.closest('.saved-filter-item');

      window.BDS.confirm(
        'Bạn có chắc chắn muốn xóa bộ lọc tìm kiếm đã lưu này khỏi danh sách không?',
        'Xác nhận xóa bộ lọc',
        function () {
          if (id.startsWith('local_')) {
            let list = getLocalSavedFilters();
            list = list.filter(x => String(x.id) !== id);
            setLocalSavedFilters(list);
            itemEl?.remove();
            if (window.BDS) {
              window.BDS.toast('Đã xóa bộ lọc tìm kiếm khỏi trình duyệt.', 'info', 'Đã xóa bộ lọc');
            }
          } else {
            api('property/deleteSavedSearch/' + id, { method: 'POST' }).then(res => {
              if (res.success) {
                itemEl?.remove();
                if (window.BDS) {
                  window.BDS.toast('Đã xóa bộ lọc tìm kiếm khỏi tài khoản.', 'info', 'Đã xóa bộ lọc');
                }
              } else if (window.BDS) {
                window.BDS.toast(res.message || 'Không thể xóa bộ lọc tìm kiếm.', 'danger', 'Lỗi xóa bộ lọc');
              }
            }).catch(() => {
              if (window.BDS) {
                window.BDS.toast('Không thể kết nối đến máy chủ.', 'danger', 'Lỗi kết nối');
              }
            });
          }
        }
      );
    }
  });
});
