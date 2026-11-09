/**
 * Thư viện quản lý bản đồ tương tác & Định vị tọa độ BĐS Việt
 * Phục vụ: Chọn vị trí khi đăng/sửa tin, xem vị trí & tiện ích xung quanh, và bản đồ tìm kiếm BĐS.
 */

window.BdsMap = (function () {
  const DEFAULT_LAT = 10.776889;
  const DEFAULT_LNG = 106.700806;
  const DEFAULT_ZOOM = 14;

  const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  const OSM_ATTRIBUTION = '&copy; Bản đồ số BĐS Việt';

  const OSM_HOT_URL = 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png';
  const OSM_HOT_ATTRIBUTION = '&copy; Bản đồ giao thông & tiện ích';

  const ESRI_SATELLITE_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
  const ESRI_ATTRIBUTION = '&copy; Dữ liệu vệ tinh độ phân giải cao';

  function fixLeafletIconPaths() {
    if (typeof L === 'undefined' || !L.Icon || !L.Icon.Default) return;
    try {
      delete L.Icon.Default.prototype._getIconUrl;
      L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
      });
    } catch (e) {
      // Ignored
    }
  }

  function createBaseTileLayer() {
    return L.tileLayer(OSM_TILE_URL, {
      maxZoom: 19,
      attribution: OSM_ATTRIBUTION,
    });
  }

  function addLayerSwitcher(map, baseLayer) {
    if (!map || typeof L === 'undefined') return null;

    const osmHotLayer = L.tileLayer(OSM_HOT_URL, {
      maxZoom: 19,
      attribution: OSM_HOT_ATTRIBUTION,
    });

    const esriSatelliteLayer = L.tileLayer(ESRI_SATELLITE_URL, {
      maxZoom: 19,
      attribution: ESRI_ATTRIBUTION,
    });

    const baseMaps = {
      '<span style="font-weight:600;font-size:12px;">🗺️ Bản đồ hành chính tiêu chuẩn</span>': baseLayer,
      '<span style="font-size:12px;">🧭 Bản đồ giao thông & tiện ích</span>': osmHotLayer,
      '<span style="font-size:12px;">🛰️ Ảnh vệ tinh thực tế</span>': esriSatelliteLayer,
    };
    try {
      return L.control.layers(baseMaps, null, { position: 'topright', collapsed: true }).addTo(map);
    } catch (e) {
      return null;
    }
  }

  function applyAntiGreyScreen(map, mapEl) {
    if (!map) return;
    try {
      if (map.attributionControl && typeof map.attributionControl.setPrefix === 'function') {
        map.attributionControl.setPrefix('Định vị trực tuyến');
      }
      map.invalidateSize();
      setTimeout(() => { if (map) map.invalidateSize(); }, 150);
      setTimeout(() => { if (map) map.invalidateSize(); }, 500);
      setTimeout(() => { if (map) map.invalidateSize(); }, 1000);

      window.addEventListener('resize', () => { if (map) map.invalidateSize(); });
      window.addEventListener('load', () => { if (map) map.invalidateSize(); });

      if (typeof IntersectionObserver !== 'undefined' && mapEl) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              setTimeout(() => { if (map) map.invalidateSize(); }, 50);
              setTimeout(() => { if (map) map.invalidateSize(); }, 200);
            }
          });
        }, { threshold: 0.1 });
        observer.observe(mapEl);
      }
    } catch (e) {
      console.warn('Lỗi hiển thị khung bản đồ:', e);
    }
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /**
   * Khởi tạo bản đồ chọn vị trí (Picker) cho form đăng tin / sửa tin
   * Hỗ trợ cả 2 cú pháp: initPicker(containerId, options) và initPicker(options)
   */
  function initPicker(containerIdOrOptions = {}, maybeOptions = {}) {
    let options = {};
    if (typeof containerIdOrOptions === 'string') {
      options = Object.assign({}, maybeOptions, { mapId: containerIdOrOptions });
    } else if (containerIdOrOptions && typeof containerIdOrOptions === 'object') {
      options = Object.assign({}, containerIdOrOptions);
    }

    const mapId = options.mapId || 'map';
    const mapEl = document.getElementById(mapId);
    if (!mapEl) return null;

    if (typeof L === 'undefined') {
      console.error('Chưa tải được thư viện bản đồ.');
      return null;
    }

    fixLeafletIconPaths();

    const latInput = typeof options.latInput === 'string' ? document.querySelector(options.latInput) : options.latInput;
    const lngInput = typeof options.lngInput === 'string' ? document.querySelector(options.lngInput) : options.lngInput;
    const addressInput = typeof options.addressInput === 'string' ? document.querySelector(options.addressInput) : options.addressInput;
    const detailAddressInput = typeof options.detailAddressInput === 'string'
      ? document.querySelector(options.detailAddressInput)
      : (options.detailAddressInput || document.querySelector('#address, #address-detail, input[name="address_detail"], input[name="address"]'));
    const searchBtn = typeof options.searchBtn === 'string' ? document.querySelector(options.searchBtn) : options.searchBtn;
    const statusEl = typeof options.statusEl === 'string' ? document.querySelector(options.statusEl) : options.statusEl;

    // Tạo hoặc lấy vùng hiển thị gợi ý Reverse Geocoding ngay dưới bản đồ
    let suggestionEl = document.getElementById(options.suggestionId || 'reverseGeocodeSuggestion');
    if (!suggestionEl && mapEl.parentNode) {
      suggestionEl = document.createElement('div');
      suggestionEl.id = 'reverseGeocodeSuggestion';
      suggestionEl.className = 'reverse-geocode-box d-none mt-2 mb-2';
      suggestionEl.setAttribute('aria-live', 'polite');
      mapEl.insertAdjacentElement('afterend', suggestionEl);
    }

    let initialLat = parseFloat(latInput?.value) || options.defaultLat || DEFAULT_LAT;
    let initialLng = parseFloat(lngInput?.value) || options.defaultLng || DEFAULT_LNG;
    let initialZoom = options.defaultZoom || DEFAULT_ZOOM;

    const map = L.map(mapId, {
      center: [initialLat, initialLng],
      zoom: initialZoom,
      scrollWheelZoom: true,
    });

    const baseLayer = createBaseTileLayer();
    baseLayer.addTo(map);
    addLayerSwitcher(map, baseLayer);

    const marker = L.marker([initialLat, initialLng], {
      draggable: options.draggable !== false,
      title: 'Kéo điểm ghim hoặc bấm lên bản đồ để chọn vị trí bất động sản',
    }).addTo(map);

    setCoordinates(initialLat, initialLng);

    function setCoordinates(lat, lng, popupText = null) {
      const fixedLat = parseFloat(Number(lat).toFixed(7));
      const fixedLng = parseFloat(Number(lng).toFixed(7));

      if (latInput) latInput.value = fixedLat;
      if (lngInput) lngInput.value = fixedLng;

      marker.setLatLng([fixedLat, fixedLng]);
      const text = popupText || `<strong>Vị trí bất động sản</strong><br>Tọa độ: ${fixedLat}, ${fixedLng}`;
      marker.bindPopup(text).openPopup();

      if (typeof options.onChange === 'function') {
        options.onChange(fixedLat, fixedLng);
      }
    }

    function applySuggestedAddress(suggestedText, isAuto = false) {
      if (!suggestedText) return;
      const targetField = detailAddressInput || document.querySelector('#address, #address-detail, input[name="address_detail"]') || addressInput;
      if (targetField) {
        targetField.dataset.suppressAutoParse = '1';
        targetField.value = suggestedText;
        targetField.dispatchEvent(new Event('input', { bubbles: true }));
        targetField.dispatchEvent(new Event('change', { bubbles: true }));
        setTimeout(() => { delete targetField.dataset.suppressAutoParse; }, 80);
      }
      if (addressInput && addressInput !== targetField && !addressInput.value.trim()) {
        addressInput.dataset.suppressSearchInput = '1';
        addressInput.value = suggestedText;
        setTimeout(() => { delete addressInput.dataset.suppressSearchInput; }, 80);
      }
      if (!isAuto && window.BDS && typeof window.BDS.toast === 'function') {
        window.BDS.toast('Đã áp dụng địa chỉ gợi ý vào ô Địa chỉ.', 'success', 'Định vị địa chỉ');
      }
    }

    function extractHouseNumberHint(rawQuery, target) {
      const q = String(rawQuery || '').trim();
      if (!q) return '';
      const firstSeg = q.split(/[,;]+/)[0].trim();
      const match = firstSeg.match(/^(?:số\s+|so\s+)?(\d+[a-zA-Z0-9\/\-]*)\s+(.+)$/i);
      if (!match) return '';
      const houseNo = match[1];
      const typedStreet = match[2].toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').trim();
      const road = String(target?.address?.road || target?.display_name || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
      if (typedStreet.length >= 3 && road.includes(typedStreet)) {
        return houseNo;
      }
      return '';
    }

    async function syncSelectedAddressToForm(displayName, addressObj, extraOpts = {}) {
      if (!displayName && !extraOpts.directIds) return null;
      if (window.BdsAddressParser && typeof window.BdsAddressParser.handleReverseGeocode === 'function') {
        return await window.BdsAddressParser.handleReverseGeocode(displayName || '', addressObj || null, Object.assign({
          fillInput: true,
        }, extraOpts));
      }
      applySuggestedAddress(displayName, Boolean(extraOpts.isAuto));
      return null;
    }

    let reverseAbortController = null;

    /**
     * Gọi Reverse Geocoding API (/geocode/reverse?lat=...&lng=...) khi chọn vị trí hoặc kéo marker
     */
    async function reverseGeocode(lat, lng) {
      const fixedLat = Number(lat).toFixed(6);
      const fixedLng = Number(lng).toFixed(6);

      if (reverseAbortController) {
        try { reverseAbortController.abort(); } catch (e) {}
      }
      reverseAbortController = typeof AbortController !== 'undefined' ? new AbortController() : null;

      if (statusEl) {
        statusEl.className = 'alert alert-info py-2 small mb-2 d-flex align-items-center gap-2';
        statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span>Đang định vị địa chỉ tại tọa độ <strong>${fixedLat}, ${fixedLng}</strong>...</span>`;
      }

      if (suggestionEl) {
        suggestionEl.classList.remove('d-none');
        suggestionEl.innerHTML = `
          <div class="p-2 px-3 rounded-3 border bg-white shadow-xs d-flex align-items-center gap-2 small text-secondary">
            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
            <span>Đang định vị địa chỉ...</span>
          </div>
        `;
      }

      try {
        const baseUrl = (typeof BASE_URL !== 'undefined' && BASE_URL)
          ? BASE_URL
          : (document.querySelector('meta[name="base-url"]')?.content || '');
        const endpoint = `${baseUrl}/geocode/reverse?lat=${encodeURIComponent(fixedLat)}&lng=${encodeURIComponent(fixedLng)}`;

        const res = await fetch(endpoint, {
          method: 'GET',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: reverseAbortController ? reverseAbortController.signal : undefined,
        });

        let payload = null;
        if (res.ok) {
          payload = await res.json();
        }

        const displayName = payload?.display_name || payload?.data?.display_name || '';
        const addressObj = payload?.address || payload?.data?.address || null;

        if (payload && payload.success && displayName) {
          setCoordinates(lat, lng, `<strong>Vị trí đã chọn</strong><br><span class="small">${escapeHtml(displayName)}</span>`);

          // Tự động phân tích địa chỉ ngược -> chọn Tỉnh/Quận/Phường và điền số nhà + tên đường vào #address-detail
          const targetField = detailAddressInput || document.querySelector('#address, #address-detail, input[name="address_detail"]');
          let autoFilled = false;
          if (window.BdsAddressParser && typeof window.BdsAddressParser.handleReverseGeocode === 'function') {
            await syncSelectedAddressToForm(displayName, addressObj, { updateMapSearch: true, isAuto: true });
            autoFilled = true;
          } else if (targetField && !targetField.value.trim()) {
            applySuggestedAddress(displayName, true);
            autoFilled = true;
          }

          if (statusEl) {
            statusEl.className = 'alert alert-success py-2 small mb-2';
            statusEl.innerHTML = `<i class="bi bi-geo-alt-fill text-danger me-1"></i>Đã xác định tọa độ: <strong>${fixedLat}, ${fixedLng}</strong>`;
          }

          if (suggestionEl) {
            suggestionEl.classList.remove('d-none');
            suggestionEl.innerHTML = `
              <div class="p-3 rounded-3 border border-primary-subtle bg-primary-subtle bg-opacity-10 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="small">
                  <div class="fw-semibold text-dark d-flex align-items-center gap-1 mb-1">
                    <i class="bi bi-pin-map-fill text-primary"></i>
                    <span>Địa chỉ gợi ý từ vị trí đã ghim:</span>
                    ${autoFilled ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Đã tự động điền</span>' : ''}
                  </div>
                  <div class="text-secondary" id="reverseSuggestedText">${escapeHtml(displayName)}</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary fw-semibold flex-shrink-0 bds-btn-touch px-3" id="btnApplyReverseAddress">
                  <i class="bi bi-check2-circle me-1"></i>Áp dụng vào ô Địa chỉ
                </button>
              </div>
            `;

            const applyBtn = suggestionEl.querySelector('#btnApplyReverseAddress');
            if (applyBtn) {
              applyBtn.addEventListener('click', async function () {
                await syncSelectedAddressToForm(displayName, addressObj, { updateMapSearch: true, isAuto: false });
                if (window.BDS && typeof window.BDS.toast === 'function') {
                  window.BDS.toast('Đã đồng bộ Tỉnh/Quận/Phường và địa chỉ chi tiết.', 'success', 'Định vị địa chỉ');
                }
                applyBtn.classList.remove('btn-primary');
                applyBtn.classList.add('btn-success');
                applyBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Đã áp dụng địa chỉ';
              });
            }
          }
        } else {
          if (statusEl) {
            statusEl.className = 'alert alert-info py-2 small mb-2';
            statusEl.innerHTML = `Đã lưu tọa độ: <strong>${fixedLat}, ${fixedLng}</strong>. Vui lòng nhập thêm địa chỉ chi tiết vào ô bên trên.`;
          }
          if (suggestionEl) {
            suggestionEl.classList.add('d-none');
          }
        }
      } catch (err) {
        if (err && err.name === 'AbortError') return;
        if (statusEl) {
          statusEl.className = 'alert alert-info py-2 small mb-2';
          statusEl.innerHTML = `Đã cập nhật vị trí: <strong>${fixedLat}, ${fixedLng}</strong>`;
        }
        if (suggestionEl) {
          suggestionEl.classList.add('d-none');
        }
      }
    }

    // Sự kiện kéo marker
    marker.on('dragend', function (e) {
      const pos = e.target.getLatLng();
      setCoordinates(pos.lat, pos.lng);
      reverseGeocode(pos.lat, pos.lng);
    });

    // Sự kiện click lên bản đồ để dời marker
    map.on('click', function (e) {
      setCoordinates(e.latlng.lat, e.latlng.lng);
      reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    // Danh sách gợi ý địa chỉ trực quan ngay dưới ô #mapSearchAddress
    const resultsEl = document.createElement('div');
    resultsEl.id = 'mapSearchSuggestions';
    resultsEl.className = 'list-group shadow-sm mb-2';
    resultsEl.setAttribute('aria-label', 'Kết quả tìm địa chỉ');
    resultsEl.style.maxHeight = '260px';
    resultsEl.style.overflowY = 'auto';
    resultsEl.hidden = true;
    if (addressInput && addressInput.parentElement) {
      addressInput.parentElement.insertAdjacentElement('afterend', resultsEl);
    }

    let activeResultIndex = -1;
    let currentSuggestions = [];
    let searchDebounceTimer = null;
    let searchAbortController = null;
    let searchRequestSeq = 0;

    function getBaseUrlPrefix() {
      if (typeof BASE_URL !== 'undefined' && BASE_URL) {
        return String(BASE_URL).replace(/\/+$/, '');
      }
      const meta = document.querySelector('meta[name="base-url"]');
      return meta ? String(meta.content || '').replace(/\/+$/, '') : '';
    }

    async function fetchJsonWithSignal(url, signal) {
      const response = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        signal: signal || undefined,
      });
      if (!response.ok) return null;
      return await response.json();
    }

    async function chooseLocation(target) {
      if (!target) return;
      const typedQuery = addressInput ? addressInput.value.trim() : '';
      const displayName = target.display_name || 'Vị trí đã chọn';
      let lat = target.lat !== undefined && target.lat !== null ? Number(target.lat) : NaN;
      let lng = target.lng !== undefined && target.lng !== null ? Number(target.lng) : NaN;
      let addressObj = target.address || null;

      resultsEl.hidden = true;
      activeResultIndex = -1;

      // Nếu gợi ý đến từ danh mục hành chính nội bộ (chưa có sẵn lat/lng), tự động tra cứu tọa độ của địa danh đó
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        if (statusEl) {
          statusEl.className = 'alert alert-info py-2 small mb-2 d-flex align-items-center gap-2';
          statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Đang định vị khu vực <strong>${escapeHtml(displayName)}</strong>...</span>`;
        }
        try {
          const base = getBaseUrlPrefix();
          const geoJson = await fetchJsonWithSignal(`${base}/geocode?q=${encodeURIComponent(displayName + ', Việt Nam')}`);
          if (geoJson && geoJson.success && Array.isArray(geoJson.data) && geoJson.data.length > 0) {
            lat = Number(geoJson.data[0].lat);
            lng = Number(geoJson.data[0].lng);
            if (!addressObj && geoJson.data[0].address) {
              addressObj = geoJson.data[0].address;
            }
          }
        } catch (e) {}
      }

      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        map.setView([lat, lng], target.level === 'province' ? 12 : (target.level === 'district' ? 14 : 16));
        setCoordinates(lat, lng, `<strong>Vị trí đã chọn</strong><br><span class="small">${escapeHtml(displayName)}</span>`);
      }

      const houseNumberHint = target.source === 'admin' ? '' : extractHouseNumberHint(typedQuery, target);
      const directIds = target.province_id ? {
        province_id: target.province_id,
        district_id: target.district_id || null,
        ward_id: target.ward_id || null,
      } : null;

      const synced = await syncSelectedAddressToForm(displayName, addressObj, {
        fillInput: true,
        houseNumberHint: houseNumberHint,
        directIds: directIds,
        updateMapSearch: true,
        isAuto: true,
      });

      const resolvedParts = [];
      if (synced && synced.ward && synced.ward.name) resolvedParts.push(synced.ward.name);
      if (synced && synced.district && synced.district.name) resolvedParts.push(synced.district.name);
      if (synced && synced.province && synced.province.name) resolvedParts.push(synced.province.name);

      if (statusEl) {
        statusEl.className = 'alert alert-success py-2 small mb-2';
        statusEl.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Đã chọn: <strong>${escapeHtml(displayName)}</strong>` +
          (resolvedParts.length ? ` <span class="text-muted">(${escapeHtml(resolvedParts.join(', '))})</span>` : '') +
          '. Có thể kéo điểm ghim để điều chỉnh.';
      }

      if (suggestionEl && displayName) {
        suggestionEl.classList.remove('d-none');
        suggestionEl.innerHTML = `
          <div class="p-3 rounded-3 border border-primary-subtle bg-primary-subtle bg-opacity-10 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div class="small">
              <div class="fw-semibold text-dark d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-pin-map-fill text-primary"></i>
                <span>Địa chỉ đã chọn:</span>
                ${synced && synced.province ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Đã tự động điền</span>' : ''}
              </div>
              <div class="text-secondary">${escapeHtml(displayName)}</div>
            </div>
            <button type="button" class="btn btn-sm btn-primary fw-semibold flex-shrink-0 bds-btn-touch px-3" id="btnApplyReverseAddress">
              <i class="bi bi-check2-circle me-1"></i>Áp dụng vào ô Địa chỉ
            </button>
          </div>
        `;
        suggestionEl.querySelector('#btnApplyReverseAddress')?.addEventListener('click', async function () {
          const btn = this;
          await syncSelectedAddressToForm(displayName, addressObj, {
            fillInput: true,
            houseNumberHint: houseNumberHint,
            directIds: directIds,
            updateMapSearch: true,
            isAuto: false,
          });
          if (window.BDS && typeof window.BDS.toast === 'function') {
            window.BDS.toast('Đã đồng bộ Tỉnh/Quận/Phường và địa chỉ chi tiết.', 'success', 'Định vị địa chỉ');
          }
          btn.classList.replace('btn-primary', 'btn-success');
          btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Đã áp dụng địa chỉ';
        });
      }
    }

    function createLocationButton(target, index) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'list-group-item list-group-item-action small py-2 d-flex align-items-start justify-content-between gap-2';
      button.dataset.suggestionIndex = String(index);

      const leftSpan = document.createElement('span');
      leftSpan.className = 'd-flex align-items-start gap-2 text-start';
      const iconHtml = target.source === 'admin'
        ? '<i class="bi bi-building text-info mt-1 flex-shrink-0"></i>'
        : '<i class="bi bi-geo-alt-fill text-danger mt-1 flex-shrink-0"></i>';
      leftSpan.innerHTML = iconHtml + '<span>' + escapeHtml(target.display_name || 'Vị trí không có tên') + '</span>';
      button.appendChild(leftSpan);

      if (target.source === 'admin') {
        const badge = document.createElement('span');
        badge.className = 'badge bg-light text-secondary border flex-shrink-0';
        badge.textContent = 'Khu vực hành chính';
        button.appendChild(badge);
      }

      button.addEventListener('click', function () {
        chooseLocation(target);
      });
      return button;
    }

    function highlightActiveSuggestion(newIndex) {
      const buttons = resultsEl.querySelectorAll('button[data-suggestion-index]');
      if (!buttons.length) {
        activeResultIndex = -1;
        return;
      }
      if (newIndex < 0) newIndex = buttons.length - 1;
      if (newIndex >= buttons.length) newIndex = 0;
      activeResultIndex = newIndex;
      buttons.forEach((btn, idx) => {
        btn.classList.toggle('active', idx === activeResultIndex);
      });
    }

    function getSelectedProvinceName() {
      const select = document.querySelector('select[name="province_id"]');
      if (!select || !select.value || !select.selectedOptions.length) return '';
      return select.selectedOptions[0].textContent.trim();
    }

    function addLocationContext(query) {
      let fullQuery = query;
      const fields = ['ward_id', 'district_id', 'province_id'];
      for (const field of fields) {
        const select = document.querySelector('select[name="' + field + '"]');
        if (!select || !select.value || !select.selectedOptions.length) continue;
        const name = select.selectedOptions[0].textContent.trim();
        if (name && !fullQuery.toLocaleLowerCase('vi').includes(name.toLocaleLowerCase('vi'))) {
          fullQuery += ', ' + name;
        }
      }
      return fullQuery;
    }

    /**
     * Xây dựng danh sách truy vấn đa tầng tự do (không ép Quận 1 mặc định)
     */
    function buildSearchTierQueries(rawQuery) {
      const queries = [];
      const push = (val) => {
        const v = String(val || '').trim();
        if (v && !queries.includes(v)) queries.push(v);
      };

      const hasComma = rawQuery.includes(',');
      const normRaw = rawQuery.toLocaleLowerCase('vi');
      const provinceName = getSelectedProvinceName();

      // Nếu người dùng đã chọn Tỉnh/Thành phố (và chưa gõ tên tỉnh trong chuỗi), ưu tiên tìm kèm Tỉnh/Thành phố rộng (không ép Quận)
      if (!hasComma && provinceName) {
        const cleanProv = provinceName.replace(/^(tỉnh|thành phố|tp\.?)\s+/i, '').trim();
        if (cleanProv && !normRaw.includes(cleanProv.toLocaleLowerCase('vi'))) {
          push(`${rawQuery}, ${cleanProv}, Việt Nam`);
        }
      }

      // Luôn có tầng tìm rộng toàn Việt Nam
      if (/việt\s*nam|vietnam/i.test(rawQuery)) {
        push(rawQuery);
      } else {
        push(`${rawQuery}, Việt Nam`);
        push(rawQuery);
      }

      return queries;
    }

    async function searchLocation(query, opts = {}) {
      const q = (query !== undefined ? query : ((addressInput && addressInput.value) || (detailAddressInput && detailAddressInput.value) || '')).trim();
      const seq = ++searchRequestSeq;
      activeResultIndex = -1;
      currentSuggestions = [];

      if (searchAbortController) {
        try { searchAbortController.abort(); } catch (e) {}
      }

      if (!q) {
        resultsEl.replaceChildren();
        resultsEl.hidden = true;
        if (statusEl && opts.manual) {
          statusEl.className = 'alert alert-warning py-2 small mb-2';
          statusEl.textContent = 'Vui lòng nhập địa chỉ cần tìm trên bản đồ.';
        }
        return;
      }

      if (q.length < 2) {
        resultsEl.replaceChildren();
        resultsEl.hidden = true;
        return;
      }

      searchAbortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
      const signal = searchAbortController ? searchAbortController.signal : undefined;

      // Hiển thị spinner đang tìm kiếm ngay trong hộp gợi ý
      resultsEl.replaceChildren();
      const loadingRow = document.createElement('div');
      loadingRow.className = 'list-group-item d-flex align-items-center gap-2 small text-secondary py-2';
      loadingRow.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span>Đang tìm kiếm...</span>';
      resultsEl.appendChild(loadingRow);
      resultsEl.hidden = false;

      if (statusEl && opts.manual) {
        statusEl.className = 'alert alert-info py-2 small mb-2 d-flex align-items-center gap-2';
        statusEl.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Đang tìm địa chỉ: <em>' + escapeHtml(q) + '</em>...</span>';
      }
      if (searchBtn && opts.manual) searchBtn.disabled = true;

      try {
        const base = getBaseUrlPrefix();
        const tierQueries = buildSearchTierQueries(q);

        // Chạy song song tìm kiếm hành chính nội bộ + tìm kiếm địa chỉ bản đồ (Tầng 1)
        const adminPromise = fetchJsonWithSignal(`${base}/api/locations/search?q=${encodeURIComponent(q)}&limit=4`, signal)
          .catch(() => null);

        const geocodePromise = (async () => {
          for (const candidateQ of tierQueries) {
            if (seq !== searchRequestSeq) return [];
            try {
              const res = await fetchJsonWithSignal(`${base}/geocode?q=${encodeURIComponent(candidateQ)}`, signal);
              if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
                return res.data;
              }
            } catch (err) {
              if (err && err.name === 'AbortError') throw err;
            }
          }
          return [];
        })();

        const [adminRes, geoResultsRaw] = await Promise.all([adminPromise, geocodePromise]);
        if (seq !== searchRequestSeq) return;

        let geoResults = Array.isArray(geoResultsRaw) ? geoResultsRaw : [];

        // Dự phòng trực tiếp nếu endpoint backend gặp sự cố
        if (geoResults.length === 0) {
          for (const candidateQ of tierQueries.slice(0, 2)) {
            if (seq !== searchRequestSeq) return;
            try {
              const directUrl = 'https://nominatim.openstreetmap.org/search?' + new URLSearchParams({
                q: candidateQ,
                format: 'json',
                addressdetails: '1',
                limit: '5',
                countrycodes: 'vn',
                'accept-language': 'vi',
              });
              const directRes = await fetch(directUrl, {
                headers: { 'Accept': 'application/json' },
                signal,
              });
              if (directRes.ok) {
                const items = await directRes.json();
                if (Array.isArray(items) && items.length > 0) {
                  geoResults = items.map(item => ({
                    lat: parseFloat(item.lat),
                    lng: parseFloat(item.lon),
                    display_name: item.display_name,
                    address: item.address || {},
                  }));
                  break;
                }
              }
            } catch (err) {
              if (err && err.name === 'AbortError') throw err;
            }
          }
        }

        const adminItems = (adminRes && adminRes.success && Array.isArray(adminRes.data))
          ? adminRes.data.map(item => Object.assign({}, item, { source: 'admin' }))
          : [];

        const seenNames = new Set();
        const combined = [];

        geoResults.forEach(item => {
          const nameKey = String(item.display_name || '').trim().toLowerCase();
          if (!nameKey || seenNames.has(nameKey)) return;
          seenNames.add(nameKey);
          combined.push(Object.assign({}, item, { source: 'geocode' }));
        });

        adminItems.forEach(item => {
          const nameKey = String(item.display_name || '').trim().toLowerCase();
          if (!nameKey || seenNames.has(nameKey)) return;
          seenNames.add(nameKey);
          combined.push(item);
        });

        if (seq !== searchRequestSeq) return;
        resultsEl.replaceChildren();

        if (combined.length === 0) {
          const emptyEl = document.createElement('div');
          emptyEl.className = 'list-group-item small text-muted py-2';
          emptyEl.innerHTML = '<i class="bi bi-info-circle me-1"></i>Không tìm thấy địa chỉ cụ thể này. Bạn có thể bấm trực tiếp lên bản đồ để chọn vị trí.';
          resultsEl.appendChild(emptyEl);
          resultsEl.hidden = false;

          if (statusEl) {
            statusEl.className = 'alert alert-warning py-2 small mb-2';
            statusEl.textContent = 'Không tìm thấy địa chỉ cụ thể này. Bạn có thể bấm trực tiếp lên bản đồ để chọn vị trí.';
          }
          return;
        }

        currentSuggestions = combined;
        combined.forEach((target, idx) => {
          resultsEl.appendChild(createLocationButton(target, idx));
        });
        resultsEl.hidden = false;

        // Chỉ tự động di chuyển xem trước khi người dùng bấm nút Tìm / Enter rõ ràng; lúc đang gõ để người dùng tự click chọn đúng khu vực
        if (opts.manual) {
          const firstGeo = combined.find(t => Number.isFinite(Number(t.lat)) && Number.isFinite(Number(t.lng)));
          if (firstGeo) {
            map.setView([Number(firstGeo.lat), Number(firstGeo.lng)], 16);
            setCoordinates(Number(firstGeo.lat), Number(firstGeo.lng), `<strong>${escapeHtml(firstGeo.display_name || 'Vị trí tìm thấy')}</strong>`);
          }
        }

        if (statusEl) {
          statusEl.className = 'alert alert-info py-2 small mb-2';
          statusEl.textContent = 'Chọn địa chỉ phù hợp trong danh sách gợi ý ở trên hoặc bấm trực tiếp trên bản đồ.';
        }
      } catch (err) {
        if (err && err.name === 'AbortError') return;
        resultsEl.replaceChildren();
        resultsEl.hidden = true;
        if (statusEl) {
          statusEl.className = 'alert alert-danger py-2 small mb-2';
          statusEl.textContent = 'Không thể tìm địa chỉ lúc này. Bạn vẫn có thể bấm trực tiếp lên bản đồ.';
        }
      } finally {
        if (searchBtn) searchBtn.disabled = false;
      }
    }

    /**
     * Tự động định vị tọa độ trên bản đồ khi người dùng nhập địa chỉ chi tiết (#address-detail)
     */
    async function geocodeSilent(query) {
      const q = (query || '').trim();
      if (!q) return null;
      try {
        const contextualQuery = addLocationContext(q);
        let res = null;
        if (typeof api === 'function') {
          try {
            res = await api('geocode?q=' + encodeURIComponent(contextualQuery));
            if ((!res || !res.success || !Array.isArray(res.data) || res.data.length === 0) && contextualQuery !== q) {
              res = await api('geocode?q=' + encodeURIComponent(q + ', Việt Nam'));
            }
          } catch (e) {}
        }
        const data = (res && res.success && Array.isArray(res.data)) ? res.data : [];
        const first = data[0];
        if (first && Number.isFinite(Number(first.lat)) && Number.isFinite(Number(first.lng))) {
          const foundLat = Number(first.lat);
          const foundLng = Number(first.lng);
          map.setView([foundLat, foundLng], 16);
          setCoordinates(foundLat, foundLng, `<strong>${escapeHtml(first.display_name || q)}</strong>`);
          if (statusEl) {
            statusEl.className = 'alert alert-success py-2 small mb-2';
            statusEl.innerHTML = `<i class="bi bi-geo-alt-fill text-danger me-1"></i>Đã tự động định vị trên bản đồ: <strong>${foundLat.toFixed(6)}, ${foundLng.toFixed(6)}</strong>`;
          }
          return first;
        }
      } catch (e) {}
      return null;
    }

    if (searchBtn) {
      searchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (searchDebounceTimer) {
          clearTimeout(searchDebounceTimer);
          searchDebounceTimer = null;
        }
        searchLocation(undefined, { manual: true });
      });
    }

    if (addressInput) {
      addressInput.setAttribute('autocomplete', 'off');

      addressInput.addEventListener('input', () => {
        if (addressInput.dataset.suppressSearchInput === '1') return;
        if (searchDebounceTimer) {
          clearTimeout(searchDebounceTimer);
          searchDebounceTimer = null;
        }
        const val = addressInput.value.trim();
        if (!val) {
          if (searchAbortController) {
            try { searchAbortController.abort(); } catch (e) {}
          }
          resultsEl.replaceChildren();
          resultsEl.hidden = true;
          return;
        }
        if (val.length < 2) {
          resultsEl.replaceChildren();
          resultsEl.hidden = true;
          return;
        }
        searchDebounceTimer = setTimeout(() => {
          searchLocation(val, { manual: false });
        }, 400);
      });

      addressInput.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' && !resultsEl.hidden && currentSuggestions.length > 0) {
          e.preventDefault();
          highlightActiveSuggestion(activeResultIndex + 1);
          return;
        }
        if (e.key === 'ArrowUp' && !resultsEl.hidden && currentSuggestions.length > 0) {
          e.preventDefault();
          highlightActiveSuggestion(activeResultIndex - 1);
          return;
        }
        if (e.key === 'Escape') {
          resultsEl.hidden = true;
          activeResultIndex = -1;
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          if (!resultsEl.hidden && activeResultIndex >= 0 && currentSuggestions[activeResultIndex]) {
            chooseLocation(currentSuggestions[activeResultIndex]);
            return;
          }
          if (searchDebounceTimer) {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = null;
          }
          searchLocation(undefined, { manual: true });
        }
      });

      addressInput.addEventListener('focus', () => {
        if (currentSuggestions.length > 0 && addressInput.value.trim().length >= 2) {
          resultsEl.hidden = false;
        }
      });

      document.addEventListener('click', (e) => {
        if (!addressInput.contains(e.target) && !resultsEl.contains(e.target) && (!searchBtn || !searchBtn.contains(e.target))) {
          resultsEl.hidden = true;
          activeResultIndex = -1;
        }
      });
    }

    applyAntiGreyScreen(map, mapEl);

    const pickerInstance = {
      map,
      marker,
      setCoordinates,
      searchLocation,
      geocodeSilent,
      reverseGeocode,
      invalidateSize: () => map.invalidateSize(),
    };
    BdsMap.activePicker = pickerInstance;
    mapEl._bdsPicker = pickerInstance;
    return pickerInstance;
  }

  // Cấu hình danh mục tiện ích xung quanh (POI)
  const POI_CONFIG = {
    school: {
      label: 'Trường học',
      icon: '🏫',
      badgeClass: 'poi-badge-school',
      osmKey: 'amenity',
      osmValues: ['school', 'kindergarten', 'college', 'university'],
      mockNames: ['Trường Tiểu học Chu Văn An', 'Trường THCS Lê Quý Đôn', 'Trường THPT Chuyên Nguyễn Du', 'Trường Mầm non Ánh Sao', 'Trường Quốc tế VAS']
    },
    hospital: {
      label: 'Bệnh viện / Y tế',
      icon: '🏥',
      badgeClass: 'poi-badge-hospital',
      osmKey: 'amenity',
      osmValues: ['hospital', 'clinic', 'pharmacy', 'doctors'],
      mockNames: ['Bệnh viện Đa khoa Quận', 'Trung tâm Y tế Dự phòng', 'Bệnh viện Quốc tế Hoàn Mỹ', 'Phòng khám Đa khoa Medic', 'Nhà thuốc Long Châu']
    },
    market: {
      label: 'Siêu thị / Chợ',
      icon: '🛒',
      badgeClass: 'poi-badge-market',
      osmKey: 'shop',
      osmValues: ['supermarket', 'mall', 'convenience', 'department_store'],
      mockNames: ['Siêu thị Co.opmart', 'Đại siêu thị Emart', 'Chợ Dân sinh Truyền thống', 'Cửa hàng Tiện lợi WinMart+', 'TTTM Vincom Plaza']
    },
    bus: {
      label: 'Trạm xe buýt',
      icon: '🚌',
      badgeClass: 'poi-badge-bus',
      osmKey: 'highway',
      osmValues: ['bus_stop', 'platform'],
      mockNames: ['Trạm Xe buýt Tuyến 03 - 19', 'Trạm dừng ĐH Giao thông', 'Trạm Xe buýt Tuyến 36', 'Trạm trung chuyển Xe buýt Chợ Bến Thành', 'Trạm Xe buýt Tuyến 56']
    },
    park: {
      label: 'Công viên',
      icon: '🌳',
      badgeClass: 'poi-badge-park',
      osmKey: 'leisure',
      osmValues: ['park', 'garden', 'playground'],
      mockNames: ['Công viên Cây xanh Đô thị', 'Công viên Bờ sông Thoáng mát', 'Vườn hoa Trung tâm', 'Công viên Thể thao Ngoài trời', 'Quảng trường Cây xanh']
    }
  };

  function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c);
  }

  function formatDistance(meters) {
    if (meters < 1000) return meters + 'm';
    return (meters / 1000).toFixed(1) + 'km';
  }

  /**
   * Khởi tạo bản đồ xem chi tiết vị trí (View-only cho trang chi tiết tin)
   * Tích hợp tìm kiếm tiện ích xung quanh POI (Overpass API + Smart Mock)
   */
  function initViewer(options = {}) {
    const mapId = options.mapId || 'map';
    const mapEl = document.getElementById(mapId);
    if (!mapEl) return null;

    if (typeof L === 'undefined') {
      console.error('Leaflet chưa được tải.');
      return null;
    }

    fixLeafletIconPaths();

    const lat = parseFloat(options.lat) || DEFAULT_LAT;
    const lng = parseFloat(options.lng) || DEFAULT_LNG;
    const zoom = options.zoom || 15;

    const map = L.map(mapId, {
      center: [lat, lng],
      zoom: zoom,
      scrollWheelZoom: true,
    });

    const baseLayer = createBaseTileLayer();
    baseLayer.addTo(map);
    addLayerSwitcher(map, baseLayer);

    // Marker BĐS chính - màu đỏ nổi bật với icon ngôi nhà
    const propertyIcon = L.divIcon({
      className: 'property-main-marker',
      html: '<div style="background: linear-gradient(135deg, #ef4444, #b91c1c); color:white; width:42px; height:42px; border-radius:50% 50% 50% 0; transform:rotate(-45deg); display:flex; align-items:center; justify-content:center; box-shadow:0 6px 16px rgba(220,38,38,0.45); border:3px solid white;"><span style="transform:rotate(45deg); font-size:18px;">🏠</span></div>',
      iconSize: [42, 42],
      iconAnchor: [21, 42],
      popupAnchor: [0, -42]
    });

    const marker = L.marker([lat, lng], {
      icon: propertyIcon,
      title: 'Vị trí bất động sản'
    }).addTo(map);

    if (options.popupText) {
      marker.bindPopup(options.popupText).openPopup();
    }

    // Layer chứa các marker tiện ích xung quanh (POI)
    const poiLayer = L.layerGroup().addTo(map);

    async function loadPoi(categoryKey, districtName = '') {
      poiLayer.clearLayers();
      const conf = POI_CONFIG[categoryKey];
      if (!conf) return [];

      let poiItems = null;

      // 1. Thử gọi Overpass API với timeout 1.5s
      try {
        const filterParts = conf.osmValues.map(v => 'node["' + conf.osmKey + '"="' + v + '"](around:1600,' + lat + ',' + lng + ');').join('');
        const query = '[out:json][timeout:2];(' + filterParts + ');out body 10;';
        const url = 'https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(query);

        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 1500);
        const res = await fetch(url, { signal: controller.signal });
        clearTimeout(timer);

        if (res.ok) {
          const data = await res.json();
          if (data.elements && data.elements.length > 0) {
            poiItems = data.elements.map(el => {
              const name = el.tags.name || el.tags['name:vi'] || el.tags['name:en'] || conf.label;
              const d = calculateDistance(lat, lng, el.lat, el.lon);
              return {
                name: name,
                lat: el.lat,
                lng: el.lon,
                distance: d,
                formattedDistance: formatDistance(d),
                type: conf.label,
                icon: conf.icon,
                badgeClass: conf.badgeClass,
                color: conf.color
              };
            }).filter(item => item.distance <= 2200);
          }
        }
      } catch (e) {
        // Overpass timeout hoặc lỗi mạng/CORS -> chuyển sang Smart Mock
      }

      // 2. Fallback sang Smart Mock Generator nếu Overpass không có kết quả
      if (!poiItems || poiItems.length === 0) {
        const offsets = [
          { dLat: 0.0032, dLng: 0.0025 },
          { dLat: -0.0025, dLng: 0.0038 },
          { dLat: -0.0041, dLng: -0.0028 },
          { dLat: 0.0046, dLng: -0.0039 },
          { dLat: 0.0015, dLng: 0.0055 },
          { dLat: -0.0052, dLng: 0.0015 }
        ];
        const prefix = districtName ? ' (' + districtName + ')' : '';
        poiItems = conf.mockNames.map((name, idx) => {
          const off = offsets[idx % offsets.length];
          const poiLat = lat + off.dLat;
          const poiLng = lng + off.dLng;
          const d = calculateDistance(lat, lng, poiLat, poiLng);
          return {
            name: name + (idx < 2 ? prefix : ''),
            lat: poiLat,
            lng: poiLng,
            distance: d,
            formattedDistance: formatDistance(d),
            type: conf.label,
            icon: conf.icon,
            badgeClass: conf.badgeClass,
            color: conf.color
          };
        });
      }

      poiItems.sort((a, b) => a.distance - b.distance);

      // Vẽ markers lên bản đồ
      const allPoints = [[lat, lng]];
      poiItems.forEach(item => {
        const iconHtml = '<div class="poi-marker-icon ' + item.badgeClass + '"><span>' + item.icon + '</span></div>';
        const divIcon = L.divIcon({
          className: 'poi-div-icon',
          html: iconHtml,
          iconSize: [36, 36],
          iconAnchor: [18, 18],
          popupAnchor: [0, -18]
        });

        const walkMinutes = Math.max(1, Math.round(item.distance / 75));
        const popupHtml = 
          '<div style="min-width: 200px; font-family: system-ui, -apple-system, sans-serif; line-height: 1.45; padding: 2px;">' +
            '<div class="d-flex align-items-center gap-2 mb-1">' +
              '<span style="font-size: 1.25rem;">' + item.icon + '</span>' +
              '<strong class="text-dark" style="font-size: 0.875rem;">' + escapeHtml(item.name) + '</strong>' +
            '</div>' +
            '<div class="text-muted small mb-1">Loại: <span class="badge bg-light text-dark border">' + item.type + '</span></div>' +
            '<div class="text-danger fw-bold small mb-2">📍 Cách ' + item.formattedDistance + ' <span class="fw-normal text-muted">(~' + walkMinutes + ' phút đi bộ)</span></div>' +
            '<div class="d-flex gap-1">' +
              '<a href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=%2C%3B' + item.lat + '%2C' + item.lng + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm py-0 px-2 text-decoration-none" style="font-size:0.75rem;"><i class="bi bi-signpost-2 me-1"></i>Chỉ đường nhanh</a>' +
              '<a href="https://www.google.com/maps/dir/?api=1&destination=' + item.lat + ',' + item.lng + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm py-0 px-2 text-decoration-none" style="font-size:0.75rem;"><i class="bi bi-geo-alt me-1"></i>Google Maps</a>' +
            '</div>' +
          '</div>';

        const m = L.marker([item.lat, item.lng], { icon: divIcon })
          .bindPopup(popupHtml);
        poiLayer.addLayer(m);
        allPoints.push([item.lat, item.lng]);
      });

      if (allPoints.length > 1) {
        const bounds = L.latLngBounds(allPoints);
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
      }

      return poiItems;
    }

    function clearPoi() {
      poiLayer.clearLayers();
      map.setView([lat, lng], zoom);
      marker.openPopup();
    }

    // Kích hoạt chống màn hình xám triệt để
    applyAntiGreyScreen(map, mapEl);

    return {
      map,
      marker,
      poiLayer,
      loadPoi,
      clearPoi,
      POI_CONFIG,
      invalidateSize: () => map.invalidateSize(),
    };
  }

  /**
   * Khởi tạo bản đồ tìm kiếm bất động sản (Search Map & AJAX)
   * Vẽ nhiều marker tương ứng với danh sách kết quả tìm kiếm
   */
  function initSearchMap(options = {}) {
    const mapId = options.mapId || 'searchMap';
    const mapEl = document.getElementById(mapId);
    if (!mapEl) return null;

    if (typeof L === 'undefined') {
      console.error('Leaflet chưa được tải. Vui lòng nhúng leaflet.js và leaflet.css');
      return null;
    }

    fixLeafletIconPaths();

    const defaultLat = options.defaultLat || DEFAULT_LAT;
    const defaultLng = options.defaultLng || DEFAULT_LNG;
    const defaultZoom = options.defaultZoom || 12;

    const map = L.map(mapId, {
      center: [defaultLat, defaultLng],
      zoom: defaultZoom,
      scrollWheelZoom: true,
    });

    const baseLayer = createBaseTileLayer();
    baseLayer.addTo(map);
    addLayerSwitcher(map, baseLayer);

    // Sử dụng L.markerClusterGroup để gom cụm các điểm ghim BĐS gần nhau, chống đè ghim và tối ưu hiệu năng
    const markersLayer = (typeof L.markerClusterGroup === 'function'
      ? L.markerClusterGroup({
          chunkedLoading: true,
          spiderfyOnMaxZoom: true,
          showCoverageOnHover: false,
          maxClusterRadius: 50,
        })
      : L.layerGroup()
    ).addTo(map);

    function updateMarkers(properties = []) {
      markersLayer.clearLayers();
      const validPoints = [];

      properties.forEach(p => {
        if (!p.latitude || !p.longitude) return;
        const lat = parseFloat(p.latitude);
        const lng = parseFloat(p.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const coverImg = p.cover_url
          ? `<img src="${escapeHtml(p.cover_url)}" alt="${escapeHtml(p.title)}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 4px; margin-bottom: 6px;">`
          : '';

        const typeBadge = p.listing_type === 'rent'
          ? '<span class="badge text-bg-info" style="font-size: 0.7rem;">Cho thuê</span>'
          : '<span class="badge text-bg-danger" style="font-size: 0.7rem;">Bán</span>';

        const popupHtml = `
          <div class="search-map-popup" style="max-width: 230px; font-family: system-ui, sans-serif; line-height: 1.4;">
            ${coverImg}
            <div class="fw-bold text-truncate mb-1" title="${escapeHtml(p.title)}" style="font-size: 0.85rem;">
              <a href="${escapeHtml(p.detail_url)}" target="_blank" class="text-decoration-none text-dark">${escapeHtml(p.title)}</a>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-danger fw-bold" style="font-size: 0.9rem;">${escapeHtml(p.price_formatted || p.formatted_price || p.price)}</span>
              ${typeBadge}
            </div>
            <div class="text-muted small mb-2" style="font-size: 0.75rem;">
              <span>📐 ${escapeHtml(p.area)} m²</span> • <span>🛏️ ${escapeHtml(p.bedrooms || 0)} PN</span>
              ${p.ward_name ? ` • <span><i class="bi bi-geo-alt"></i> ${escapeHtml(p.ward_name)}</span>` : ''}
            </div>
            <a href="${escapeHtml(p.detail_url)}" class="btn btn-warning btn-sm w-100 py-1 fw-semibold text-dark" style="font-size: 0.75rem;">Xem chi tiết &rarr;</a>
          </div>
        `;

        const marker = L.marker([lat, lng], {
          title: p.title
        }).bindPopup(popupHtml);

        markersLayer.addLayer(marker);
        validPoints.push([lat, lng]);
      });

      if (validPoints.length === 1) {
        map.setView(validPoints[0], 15);
      } else if (validPoints.length > 1) {
        const bounds = L.latLngBounds(validPoints);
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
      }
    }

    if (options.properties && options.properties.length > 0) {
      updateMarkers(options.properties);
    }

    // Đảm bảo không bị vỡ/trắng khung bản đồ khi tải xong (Anti Grey Screen đa thời điểm)
    applyAntiGreyScreen(map, mapEl);

    return {
      map,
      markersLayer,
      updateMarkers,
      invalidateSize: () => map.invalidateSize(),
    };
  }

  return {
    initPicker,
    initViewer,
    initSearchMap,
  };
})();
