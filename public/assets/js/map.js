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

    // Hàm tìm kiếm địa chỉ qua GeocodeController
    async function searchLocation(query) {
      const q = (query || addressInput?.value || '').trim();
      if (!q) {
        if (statusEl) {
          statusEl.className = 'alert alert-warning py-2 small mb-3';
          statusEl.innerHTML = 'Vui lòng nhập địa chỉ cần tìm trên bản đồ.';
        }
        return;
      }

      if (statusEl) {
        statusEl.className = 'alert alert-info py-2 small mb-3';
        statusEl.innerHTML = 'Đang tìm kiếm tọa độ cho: <em>' + q + '</em>...';
      }

      try {
        // 1. Thử gọi qua backend GeocodeController của nhóm
        let res = null;
        try {
          res = await api('geocode?q=' + encodeURIComponent(q));
        } catch (e) {
          console.warn('Backend GeocodeController không phản hồi, thử gọi trực tiếp Nominatim...', e);
        }

        // 2. Dự phòng nếu host chặn cURL: gọi trực tiếp Nominatim phía client (CORS)
        let data = res && res.success ? res.data : null;
        if (!data || data.length === 0) {
          const directUrl = 'https://nominatim.openstreetmap.org/search?' + new URLSearchParams({
            q: q,
            format: 'json',
            limit: '1',
            countrycodes: 'vn',
          });
          const directRes = await fetch(directUrl, {
            headers: { 'Accept': 'application/json' },
          });
          if (directRes.ok) {
            const items = await directRes.json();
            if (items && items.length > 0) {
              data = [{ lat: parseFloat(items[0].lat), lng: parseFloat(items[0].lon), display_name: items[0].display_name }];
            }
          }
        }

        if (data && data.length > 0) {
          const target = data[0];
          map.setView([target.lat, target.lng], 16);
          setCoordinates(target.lat, target.lng, `<strong>${target.display_name}</strong>`);
          if (statusEl) {
            statusEl.className = 'alert alert-success py-2 small mb-3';
            statusEl.innerHTML = `Tìm thấy tọa độ: <strong>${target.lat.toFixed(6)}, ${target.lng.toFixed(6)}</strong> (${target.display_name})`;
          }
        } else {
          if (statusEl) {
            statusEl.className = 'alert alert-warning py-2 small mb-3';
            statusEl.innerHTML = 'Không tìm thấy tọa độ chính xác. Bạn có thể bấm trực tiếp lên bản đồ để chọn vị trí.';
          }
        }
      } catch (err) {
        if (statusEl) {
          statusEl.className = 'alert alert-danger py-2 small mb-3';
          statusEl.innerHTML = 'Lỗi tìm kiếm vị trí: ' + err.message;
        }
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
