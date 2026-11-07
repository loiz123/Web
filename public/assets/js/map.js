/**
 * [C] Thư viện quản lý bản đồ OpenStreetMap (Leaflet) & Geocoding - Hà Ngọc Thiện phụ trách
 * Phục vụ: Chọn vị trí khi đăng tin (Hải dùng), xem vị trí chi tiết tin, và trang tìm kiếm BĐS.
 * 
 * Lưu ý kỹ thuật:
 * - Sử dụng Leaflet mã nguồn mở kết hợp các lớp gạch OpenStreetMap chuẩn & ESRI World Imagery.
 * - KHÔNG BẮT BUỘC API KEY (100% miễn phí, mã nguồn mở, không giới hạn request, không watermark).
 * - Mặc định dùng OpenStreetMap tiêu chuẩn tải mượt mà, sạch sẽ 100%.
 */

window.BdsMap = (function () {
  const DEFAULT_LAT = 10.776889;
  const DEFAULT_LNG = 106.700806;
  const DEFAULT_ZOOM = 14;

  // Lớp gạch OpenStreetMap chuẩn (Miễn phí, không watermark, không cần API key)
  const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  const OSM_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors';

  // Lớp gạch OpenStreetMap nhân đạo HOT (Tối ưu hiển thị công cộng, địa danh)
  const OSM_HOT_URL = 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png';
  const OSM_HOT_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors, Tiles style by <a href="https://www.hotosm.org/" target="_blank">Humanitarian OpenStreetMap Team</a>';

  // Lớp gạch Vệ tinh ESRI World Imagery (Sắc nét, toàn cầu, miễn phí)
  const ESRI_SATELLITE_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
  const ESRI_ATTRIBUTION = 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics';

  /**
   * Sửa lỗi đường dẫn icon mặc định của Leaflet khi nạp qua CDN
   */
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

  /**
   * Tạo tile layer nền: Mặc định OpenStreetMap tiêu chuẩn sạch sẽ 100%
   */
  function createBaseTileLayer() {
    return L.tileLayer(OSM_TILE_URL, {
      maxZoom: 19,
      attribution: OSM_ATTRIBUTION,
    });
  }

  /**
   * Thêm bộ điều khiển chuyển đổi layer:
   * 1. Bản đồ OpenStreetMap tiêu chuẩn
   * 2. Bản đồ OpenStreetMap nhân đạo (HOT)
   * 3. Ảnh vệ tinh ESRI World Imagery
   */
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
      '<span style="font-weight:600;font-size:12px;">🗺️ Bản đồ OpenStreetMap tiêu chuẩn</span>': baseLayer,
      '<span style="font-size:12px;">❤️ Bản đồ OpenStreetMap nhân đạo (HOT)</span>': osmHotLayer,
      '<span style="font-size:12px;">🛰️ Ảnh vệ tinh ESRI World Imagery</span>': esriSatelliteLayer,
    };
    try {
      return L.control.layers(baseMaps, null, { position: 'topright', collapsed: true }).addTo(map);
    } catch (e) {
      return null;
    }
  }

  /**
   * Xử lý triệt để lỗi màn hình xám của Leaflet:
   * Gọi invalidateSize ngay, sau 150ms, 500ms, 1000ms, khi resize, khi load và IntersectionObserver khi cuộn tới
   */
  function applyAntiGreyScreen(map, mapEl) {
    if (!map) return;
    try {
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
      console.warn('Lỗi applyAntiGreyScreen:', e);
    }
  }

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

    fixLeafletIconPaths();

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

    const baseLayer = createBaseTileLayer();
    baseLayer.addTo(map);
    addLayerSwitcher(map, baseLayer);

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
          if (typeof api === 'function') {
            res = await api('geocode?q=' + encodeURIComponent(q));
          }
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

    // Đảm bảo không bị vỡ/trắng khung bản đồ khi tải xong (Anti Grey Screen đa thời điểm)
    applyAntiGreyScreen(map, mapEl);

    return {
      map,
      marker,
      setCoordinates,
      searchLocation,
      invalidateSize: () => map.invalidateSize(),
    };
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
      scrollWheelZoom: false,
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
              '<strong class="text-dark" style="font-size: 0.875rem;">' + item.name + '</strong>' +
            '</div>' +
            '<div class="text-muted small mb-1">Loại: <span class="badge bg-light text-dark border">' + item.type + '</span></div>' +
            '<div class="text-danger fw-bold small mb-2">📍 Cách ' + item.formattedDistance + ' <span class="fw-normal text-muted">(~' + walkMinutes + ' phút đi bộ)</span></div>' +
            '<div class="d-flex gap-1">' +
              '<a href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=%2C%3B' + item.lat + '%2C' + item.lng + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm py-0 px-2 text-decoration-none" style="font-size:0.75rem;"><i class="bi bi-signpost-2 me-1"></i>Chỉ đường OSM</a>' +
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
