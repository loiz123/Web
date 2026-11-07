/**
 * [C] Thư viện quản lý bản đồ OpenStreetMap (Leaflet) & Geocoding - Hà Ngọc Thiện phụ trách
 * Phục vụ: Chọn vị trí khi đăng tin (Hải dùng), xem vị trí chi tiết tin, và trang thử nghiệm.
 */

window.BdsMap = (function () {
  const DEFAULT_LAT = 10.776889;
  const DEFAULT_LNG = 106.700806;
  const DEFAULT_ZOOM = 14;
  const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  const OSM_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors';

  /**
   * Khởi tạo bản đồ chọn vị trí (Picker) cho form đăng tin hoặc trang test
   */
  function initPicker(options = {}) {
    const mapId = options.mapId || 'map';
    const mapEl = document.getElementById(mapId);
    if (!mapEl) return null;

    if (typeof L === 'undefined') {
      console.error('Leaflet chưa được tải. Vui lòng nhúng leaflet.js và leaflet.css');
      return null;
    }

    const latInput = typeof options.latInput === 'string' ? document.querySelector(options.latInput) : options.latInput;
    const lngInput = typeof options.lngInput === 'string' ? document.querySelector(options.lngInput) : options.lngInput;
    const addressInput = typeof options.addressInput === 'string' ? document.querySelector(options.addressInput) : options.addressInput;
    const searchBtn = typeof options.searchBtn === 'string' ? document.querySelector(options.searchBtn) : options.searchBtn;
    const statusEl = typeof options.statusEl === 'string' ? document.querySelector(options.statusEl) : options.statusEl;

    // Lấy tọa độ ban đầu từ input hoặc mặc định
    let initialLat = parseFloat(latInput?.value) || options.defaultLat || DEFAULT_LAT;
    let initialLng = parseFloat(lngInput?.value) || options.defaultLng || DEFAULT_LNG;
    let initialZoom = options.defaultZoom || DEFAULT_ZOOM;

    const map = L.map(mapId, {
      center: [initialLat, initialLng],
      zoom: initialZoom,
      scrollWheelZoom: true,
    });

    L.tileLayer(OSM_TILE_URL, {
      maxZoom: 19,
      attribution: OSM_ATTRIBUTION,
    }).addTo(map);

    const marker = L.marker([initialLat, initialLng], {
      draggable: options.draggable !== false,
      title: 'Kéo marker hoặc bấm lên bản đồ để chọn vị trí',
    }).addTo(map);

    setCoordinates(initialLat, initialLng);

    function setCoordinates(lat, lng, popupText = null) {
      const fixedLat = parseFloat(Number(lat).toFixed(7));
      const fixedLng = parseFloat(Number(lng).toFixed(7));

      if (latInput) latInput.value = fixedLat;
      if (lngInput) lngInput.value = fixedLng;

      marker.setLatLng([fixedLat, fixedLng]);
      const text = popupText || `<strong>Vị trí đã chọn</strong><br>Tọa độ: ${fixedLat}, ${fixedLng}`;
      marker.bindPopup(text).openPopup();

      if (typeof options.onChange === 'function') {
        options.onChange(fixedLat, fixedLng);
      }
    }

    // Sự kiện kéo marker
    marker.on('dragend', function (e) {
      const pos = e.target.getLatLng();
      setCoordinates(pos.lat, pos.lng);
      if (statusEl) {
        statusEl.className = 'alert alert-info py-2 small mb-3';
        statusEl.innerHTML = `Đã cập nhật vị trí: <strong>${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}</strong> (kéo marker)`;
      }
    });

    // Sự kiện click lên bản đồ để dời marker
    map.on('click', function (e) {
      setCoordinates(e.latlng.lat, e.latlng.lng);
      if (statusEl) {
        statusEl.className = 'alert alert-info py-2 small mb-3';
        statusEl.innerHTML = `Đã chọn vị trí: <strong>${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}</strong>`;
      }
    });

    // Tìm địa chỉ khi người dùng yêu cầu và chọn một kết quả từ API.
    const resultsEl = document.createElement('div');
    resultsEl.className = 'list-group mb-2';
    resultsEl.setAttribute('aria-label', 'Kết quả tìm địa chỉ');
    resultsEl.hidden = true;
    if (addressInput && addressInput.parentElement) {
      addressInput.parentElement.insertAdjacentElement('afterend', resultsEl);
    }

    function chooseLocation(target) {
      const lat = Number(target.lat);
      const lng = Number(target.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
      const popup = document.createElement('span');
      popup.textContent = target.display_name || 'Vị trí đã chọn';
      map.setView([lat, lng], 16);
      setCoordinates(lat, lng, popup);
      resultsEl.hidden = true;
      if (statusEl) {
        statusEl.className = 'alert alert-success py-2 small mb-3';
        statusEl.textContent = 'Đã chọn: ' + (target.display_name || 'vị trí trên bản đồ') + '. Có thể kéo marker để điều chỉnh.';
      }
    }

    function createLocationButton(target) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'list-group-item list-group-item-action';
      button.textContent = target.display_name || 'Vị trí không có tên';
      button.addEventListener('click', function () { chooseLocation(target); });
      return button;
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

    async function searchLocation(query) {
      const q = (query || (addressInput && addressInput.value) || '').trim();
      resultsEl.replaceChildren();
      resultsEl.hidden = true;
      if (!q) {
        if (statusEl) {
          statusEl.className = 'alert alert-warning py-2 small mb-3';
          statusEl.textContent = 'Vui lòng nhập địa chỉ cần tìm trên bản đồ.';
        }
        return;
      }
      if (statusEl) {
        statusEl.className = 'alert alert-info py-2 small mb-3';
        statusEl.textContent = 'Đang tìm địa chỉ...';
      }
      if (searchBtn) searchBtn.disabled = true;
      try {
        const contextualQuery = addLocationContext(q);
        let res = await api('geocode?q=' + encodeURIComponent(contextualQuery));
        if ((!res.success || !Array.isArray(res.data) || res.data.length === 0) && contextualQuery !== q) {
          res = await api('geocode?q=' + encodeURIComponent(q));
        }
        if (!res.success || !Array.isArray(res.data) || res.data.length === 0) {
          if (statusEl) {
            statusEl.className = 'alert alert-warning py-2 small mb-3';
            statusEl.textContent = 'Không tìm thấy địa chỉ cụ thể. Hãy thử tên xã, tỉnh hoặc địa danh gần đó, rồi chọn vị trí chính xác trên bản đồ.';
          }
          return;
        }
        for (const target of res.data) resultsEl.appendChild(createLocationButton(target));
        resultsEl.hidden = false;
        if (statusEl) {
          statusEl.className = 'alert alert-info py-2 small mb-3';
          statusEl.textContent = 'Chọn địa chỉ phù hợp trong danh sách hoặc bấm trực tiếp trên bản đồ.';
        }
      } catch (err) {
        if (statusEl) {
          statusEl.className = 'alert alert-danger py-2 small mb-3';
          statusEl.textContent = 'Không thể tìm địa chỉ. Bạn vẫn có thể chọn trực tiếp trên bản đồ.';
        }
      } finally {
        if (searchBtn) searchBtn.disabled = false;
      }
    }

    if (searchBtn) {
      searchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        searchLocation();
      });
    }

    if (addressInput) {
      addressInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          searchLocation();
        }
      });
    }

    // Đảm bảo không bị vỡ/trắng khung bản đồ khi tải xong
    setTimeout(() => map.invalidateSize(), 250);
    window.addEventListener('resize', () => map.invalidateSize());

    return {
      map,
      marker,
      setCoordinates,
      searchLocation,
      invalidateSize: () => map.invalidateSize(),
    };
  }

  /**
   * Khởi tạo bản đồ xem chi tiết vị trí (View-only cho trang chi tiết tin)
   */
  function initViewer(options = {}) {
    const mapId = options.mapId || 'map';
    const mapEl = document.getElementById(mapId);
    if (!mapEl) return null;

    if (typeof L === 'undefined') {
      console.error('Leaflet chưa được tải.');
      return null;
    }

    const lat = parseFloat(options.lat) || DEFAULT_LAT;
    const lng = parseFloat(options.lng) || DEFAULT_LNG;
    const zoom = options.zoom || 15;

    const map = L.map(mapId, {
      center: [lat, lng],
      zoom: zoom,
      scrollWheelZoom: false,
    });

    L.tileLayer(OSM_TILE_URL, {
      maxZoom: 19,
      attribution: OSM_ATTRIBUTION,
    }).addTo(map);

    const marker = L.marker([lat, lng]).addTo(map);
    if (options.popupText) {
      marker.bindPopup(options.popupText).openPopup();
    }

    setTimeout(() => map.invalidateSize(), 250);
    window.addEventListener('resize', () => map.invalidateSize());

    return { map, marker };
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

    const defaultLat = options.defaultLat || DEFAULT_LAT;
    const defaultLng = options.defaultLng || DEFAULT_LNG;
    const defaultZoom = options.defaultZoom || 12;

    const map = L.map(mapId, {
      center: [defaultLat, defaultLng],
      zoom: defaultZoom,
      scrollWheelZoom: true,
    });

    L.tileLayer(OSM_TILE_URL, {
      maxZoom: 19,
      attribution: OSM_ATTRIBUTION,
    }).addTo(map);

    const markersLayer = L.layerGroup().addTo(map);

    function updateMarkers(properties = []) {
      markersLayer.clearLayers();
      const validPoints = [];

      properties.forEach(p => {
        if (!p.latitude || !p.longitude) return;
        const lat = parseFloat(p.latitude);
        const lng = parseFloat(p.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const coverImg = p.cover_url
          ? `<img src="${p.cover_url}" alt="${p.title}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 4px; margin-bottom: 6px;">`
          : '';

        const typeBadge = p.listing_type === 'rent'
          ? '<span class="badge text-bg-info" style="font-size: 0.7rem;">Cho thuê</span>'
          : '<span class="badge text-bg-danger" style="font-size: 0.7rem;">Bán</span>';

        const popupHtml = `
          <div class="search-map-popup" style="max-width: 230px; font-family: system-ui, sans-serif; line-height: 1.4;">
            ${coverImg}
            <div class="fw-bold text-truncate mb-1" title="${p.title}" style="font-size: 0.85rem;">
              <a href="${p.detail_url}" target="_blank" class="text-decoration-none text-dark">${p.title}</a>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-danger fw-bold" style="font-size: 0.9rem;">${p.formatted_price || p.price}</span>
              ${typeBadge}
            </div>
            <div class="text-muted small mb-2" style="font-size: 0.75rem;">
              <span>📐 ${p.area} m²</span> • <span>🛏️ ${p.bedrooms || 0} PN</span>
              ${p.district_name ? ` • <span>📍 ${p.district_name}</span>` : ''}
            </div>
            <a href="${p.detail_url}" class="btn btn-warning btn-sm w-100 py-1 fw-semibold text-dark" style="font-size: 0.75rem;">Xem chi tiết &rarr;</a>
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

    setTimeout(() => map.invalidateSize(), 250);
    window.addEventListener('resize', () => map.invalidateSize());

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
