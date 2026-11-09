/**
 * Cơ chế bóc tách địa chỉ thông minh & Tự động điền Tỉnh/Thành - Quận/Huyện - Phường/Xã
 * Hỗ trợ đồng bộ 2 chiều với bản đồ chọn vị trí (#agentMapPicker)
 */
(function (window, document) {
  'use strict';

  if (window.__bdsAddressParserInitialized) {
    return;
  }
  window.__bdsAddressParserInitialized = true;

  const DEBOUNCE_MS = 600;

  // Từ điển viết tắt và tên gọi phổ biến của các Tỉnh/Thành phố
  const PROVINCE_ALIASES = {
    'ho chi minh': [
      'tp.hcm', 'tphcm', 'tp hcm', 'hcm', 'tp ho chi minh', 'thanh pho ho chi minh',
      'ho chi minh', 'ho chi minh city', 'sai gon', 'saigon', 'sg'
    ],
    'ha noi': [
      'tp.hn', 'tphn', 'tp hn', 'hn', 'tp ha noi', 'thanh pho ha noi',
      'ha noi', 'hanoi', 'ha noi city', 'thu do ha noi'
    ],
    'da nang': [
      'tp.dn', 'tpdn', 'tp dn', 'tp da nang', 'thanh pho da nang',
      'da nang', 'danang', 'da nang city'
    ],
    'hai phong': [
      'tp.hp', 'tphp', 'tp hp', 'tp hai phong', 'thanh pho hai phong', 'hai phong', 'hai phong city'
    ],
    'can tho': [
      'tp.ct', 'tpct', 'tp ct', 'tp can tho', 'thanh pho can tho', 'can tho', 'can tho city'
    ],
    'hue': [
      'thua thien hue', 'tinh thua thien hue', 'tp hue', 'thanh pho hue', 'hue', 'hue city'
    ],
    'ba ria vung tau': [
      'ba ria - vung tau', 'ba ria vung tau', 'brvt', 'br-vt', 'vung tau'
    ],
    'dak lak': [
      'dak lak', 'dac lac', 'daklak', 'buon ma thuot'
    ],
    'dak nong': [
      'dak nong', 'dac nong', 'daknong'
    ],
    'kon tum': [
      'kon tum', 'kontum'
    ],
    'bac kan': [
      'bac kan', 'bac can'
    ]
  };

  const districtCache = new Map();
  const wardCache = new Map();
  const resolveCache = new Map();

  /**
   * Chuẩn hóa chuỗi tiếng Việt về dạng không dấu, chữ thường để đối sánh
   */
  function normalizeUnaccented(str) {
    if (!str) return '';
    return String(str)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd')
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  /**
   * Chuẩn hóa chuỗi tiếng Việt giữ nguyên dấu, chữ thường
   */
  function normalizeAccented(str) {
    if (!str) return '';
    return String(str)
      .toLowerCase()
      .normalize('NFC')
      .replace(/[.,;:/\\|()[\]{}"'`~!?+-]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  /**
   * Chuẩn hóa chữ số La Mã ở cuối tên phường/xã (VD: Hải Châu I -> Hải Châu 1)
   */
  function normalizeRomanSuffix(normStr) {
    return normStr
      .replace(/\bviii$/i, '8')
      .replace(/\bvii$/i, '7')
      .replace(/\bvi$/i, '6')
      .replace(/\biv$/i, '4')
      .replace(/\bv$/i, '5')
      .replace(/\biii$/i, '3')
      .replace(/\bii$/i, '2')
      .replace(/\bi$/i, '1');
  }

  /**
   * Bỏ tiền tố hành chính khỏi tên Tỉnh/Quận/Phường để lấy tên cốt lõi
   */
  function stripAdminPrefix(name, level) {
    const norm = normalizeUnaccented(name);
    if (!norm) return '';

    if (level === 'province') {
      return norm.replace(/^(thanh pho|tp|tinh)\s+/, '').trim();
    }
    if (level === 'district') {
      const stripped = norm.replace(/^(quan|q|huyen|h|thi xa|tx|thanh pho|tp)\s+/, '').trim();
      // Nếu sau khi bỏ tiền tố chỉ còn số (VD: Quận 1, Quận 3, Quận 12) thì giữ cả dạng có tiền tố để tránh khớp nhầm số nhà
      if (/^\d+$/.test(stripped)) {
        return 'quan ' + stripped.replace(/^0+/, '');
      }
      return stripped;
    }
    if (level === 'ward') {
      const stripped = normalizeRomanSuffix(
        norm.replace(/^(phuong|p|xa|x|thi tran|tt|quan|q|huyen|h|thi xa|tx)\s+/, '').trim()
      );
      if (/^\d+$/.test(stripped)) {
        return 'phuong ' + stripped.replace(/^0+/, '');
      }
      return stripped;
    }
    return norm;
  }

  function stripAccentedPrefix(name, level) {
    const norm = normalizeAccented(name);
    if (!norm) return '';
    if (level === 'province') {
      return norm.replace(/^(thành phố|tp|tỉnh)\s+/u, '').trim();
    }
    if (level === 'district') {
      const stripped = norm.replace(/^(quận|q|huyện|h|thị xã|tx|thành phố|tp)\s+/u, '').trim();
      if (/^\d+$/.test(stripped)) {
        return 'quận ' + stripped.replace(/^0+/, '');
      }
      return stripped;
    }
    if (level === 'ward') {
      const stripped = norm.replace(/^(phường|p|xã|x|thị trấn|tt|quận|q|huyện|h|thị xã|tx)\s+/u, '').trim();
      if (/^\d+$/.test(stripped)) {
        return 'phường ' + stripped.replace(/^0+/, '');
      }
      return stripped;
    }
    return norm;
  }

  function escapeRegex(str) {
    return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  /**
   * Kiểm tra một cụm từ có xuất hiện độc lập theo ranh giới từ trong chuỗi đích hay không
   */
  function containsWholePhrase(haystack, needle) {
    if (!haystack || !needle) return false;
    const pattern = new RegExp('(?:^|\\s)' + escapeRegex(needle) + '(?:$|\\s)', 'i');
    return pattern.test(haystack);
  }

  /**
   * Lấy đường dẫn gốc API
   */
  function getBaseUrl() {
    if (typeof window.BASE_URL === 'string' && window.BASE_URL) {
      return window.BASE_URL.replace(/\/+$/, '');
    }
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? (meta.content || '').replace(/\/+$/, '') : '';
  }

  /**
   * Gọi API lấy danh sách Quận/Huyện hoặc Phường/Xã có bộ nhớ đệm
   */
  async function fetchAdminList(level, parentId) {
    const pid = Number(parentId);
    if (!pid || pid <= 0) return [];

    const cacheKey = level + '_' + pid;
    const cache = level === 'districts' ? districtCache : wardCache;
    if (cache.has(cacheKey)) {
      return cache.get(cacheKey);
    }
    if (cache.has(pid)) {
      return cache.get(pid);
    }

    try {
      let rows = [];
      if (typeof window.api === 'function') {
        const res = await window.api(`api/${level}/${pid}`);
        if (res && res.success && Array.isArray(res.data)) {
          rows = res.data;
        }
      } else {
        const url = `${getBaseUrl()}/api/${level}/${pid}`;
        const response = await fetch(url, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.ok) {
          const json = await response.json();
          if (json && json.success && Array.isArray(json.data)) {
            rows = json.data;
          }
        }
      }
      if (rows.length > 0) {
        cache.set(cacheKey, rows);
        cache.set(pid, rows);
      }
      return rows;
    } catch (e) {
      return [];
    }
  }

  /**
   * Đổ dữ liệu vào thẻ <select> mà không gây xung đột sự kiện change
   */
  function populateSelectOptions(selectEl, items, placeholder, selectedValue) {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    if (Array.isArray(items)) {
      items.forEach(function (item) {
        const opt = new Option(item.name, String(item.id));
        if (selectedValue && String(item.id) === String(selectedValue)) {
          opt.selected = true;
        }
        selectEl.add(opt);
      });
    }
    if (selectedValue) {
      selectEl.value = String(selectedValue);
      selectEl.dataset.selected = String(selectedValue);
    } else {
      selectEl.value = '';
    }
  }

  /**
   * Tách chuỗi địa chỉ thành các phân đoạn (segments) từ phải qua trái
   * (Ưu tiên đối sánh từ cấp Tỉnh -> Quận -> Phường theo cấu trúc địa chỉ Việt Nam)
   */
  function splitAddressSegments(rawAddress) {
    if (!rawAddress) return [];
    return String(rawAddress)
      .split(/[,;\n]+/)
      .map(function (seg) { return seg.trim(); })
      .filter(function (seg) {
        if (!seg) return false;
        const norm = normalizeUnaccented(seg);
        // Loại bỏ hậu tố quốc gia hoặc mã bưu chính thuần số ở cuối
        if (norm === 'viet nam' || norm === 'vietnam' || norm === 'vn' || /^\d{5,6}$/.test(norm)) {
          return false;
        }
        return true;
      });
  }

  /**
   * Tìm Tỉnh/Thành phố phù hợp nhất từ danh sách options của #province
   */
  function matchProvince(rawAddress, segments, provinceSelect, addressObj) {
    if (!provinceSelect || !provinceSelect.options) return null;

    const options = Array.from(provinceSelect.options)
      .filter(function (opt) { return opt.value !== ''; })
      .map(function (opt) {
        const name = opt.textContent.trim();
        const coreUnaccented = stripAdminPrefix(name, 'province');
        const coreAccented = stripAccentedPrefix(name, 'province');
        return {
          id: String(opt.value),
          name: name,
          fullUnaccented: normalizeUnaccented(name),
          coreUnaccented: coreUnaccented,
          coreAccented: coreAccented,
          aliases: PROVINCE_ALIASES[coreUnaccented] || []
        };
      });

    // Sắp xếp độ dài tên cốt lõi giảm dần để tránh khớp chuỗi con ngắn trước
    options.sort(function (a, b) {
      return b.coreUnaccented.length - a.coreUnaccented.length;
    });

    // 1. Nếu có đối tượng address từ Nominatim (city / state / province)
    if (addressObj && typeof addressObj === 'object') {
      const candidates = [
        addressObj.state,
        addressObj.city,
        addressObj.province,
        addressObj.region
      ].filter(Boolean);

      for (let i = 0; i < candidates.length; i++) {
        const candNorm = stripAdminPrefix(candidates[i], 'province');
        const candFull = normalizeUnaccented(candidates[i]);
        for (let j = 0; j < options.length; j++) {
          const opt = options[j];
          if (
            candNorm === opt.coreUnaccented ||
            candFull === opt.fullUnaccented ||
            opt.aliases.indexOf(candFull) !== -1 ||
            opt.aliases.indexOf(candNorm) !== -1
          ) {
            return { id: opt.id, name: opt.name, segmentIndex: -1 };
          }
        }
      }
    }

    // 2. Duyệt từng phân đoạn từ phải sang trái (cuối chuỗi địa chỉ lên đầu)
    for (let sIdx = segments.length - 1; sIdx >= 0; sIdx--) {
      const segRaw = segments[sIdx];
      const segUnaccented = normalizeUnaccented(segRaw);
      const segAccented = normalizeAccented(segRaw);
      const segCore = stripAdminPrefix(segRaw, 'province');

      // 2a. Khớp chính xác phân đoạn hoặc có dấu
      for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        if (
          segAccented === opt.coreAccented ||
          containsWholePhrase(segAccented, opt.coreAccented) ||
          segUnaccented === opt.fullUnaccented ||
          segCore === opt.coreUnaccented
        ) {
          return { id: opt.id, name: opt.name, segmentIndex: sIdx };
        }
        for (let a = 0; a < opt.aliases.length; a++) {
          const aliasNorm = normalizeUnaccented(opt.aliases[a]);
          if (segUnaccented === aliasNorm || containsWholePhrase(segUnaccented, aliasNorm)) {
            return { id: opt.id, name: opt.name, segmentIndex: sIdx };
          }
        }
      }

      // 2b. Khớp cụm từ không dấu trong phân đoạn
      for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        if (containsWholePhrase(segUnaccented, opt.coreUnaccented)) {
          return { id: opt.id, name: opt.name, segmentIndex: sIdx };
        }
      }
    }

    // 3. Dự phòng quét trên toàn bộ chuỗi địa chỉ
    const fullUnaccented = normalizeUnaccented(rawAddress);
    const fullAccented = normalizeAccented(rawAddress);
    for (let i = 0; i < options.length; i++) {
      const opt = options[i];
      if (containsWholePhrase(fullAccented, opt.coreAccented) || containsWholePhrase(fullUnaccented, opt.coreUnaccented)) {
        return { id: opt.id, name: opt.name, segmentIndex: -1 };
      }
      for (let a = 0; a < opt.aliases.length; a++) {
        const aliasNorm = normalizeUnaccented(opt.aliases[a]);
        if (aliasNorm.length >= 2 && containsWholePhrase(fullUnaccented, aliasNorm)) {
          return { id: opt.id, name: opt.name, segmentIndex: -1 };
        }
      }
    }

    return null;
  }

  /**
   * Kiểm tra khớp Quận/Huyện dạng số (VD: Quận 1, Q.1, Q1, Quận 03)
   */
  function matchNumericAdmin(textUnaccented, prefixType, numberStr) {
    const num = String(numberStr).replace(/^0+/, '');
    const prefixPattern = prefixType === 'district'
      ? '(?:quan|q)'
      : '(?:phuong|p)';
    const regex = new RegExp('(?:^|\\s)' + prefixPattern + '\\s*0*' + escapeRegex(num) + '(?:$|\\s)', 'i');
    return regex.test(textUnaccented);
  }

  /**
   * Tìm Quận/Huyện phù hợp trong danh sách districts của Tỉnh đã chọn
   */
  function matchDistrict(rawAddress, segments, districts, matchedProvince, addressObj) {
    if (!Array.isArray(districts) || districts.length === 0) return null;

    const prepared = districts.map(function (d) {
      const name = String(d.name || '').trim();
      const normFull = normalizeUnaccented(name);
      const rawStripped = normFull.replace(/^(quan|q|huyen|h|thi xa|tx|thanh pho|tp)\s+/, '').trim();
      const isNumeric = /^\d+$/.test(rawStripped);
      return {
        id: String(d.id),
        name: name,
        fullUnaccented: normFull,
        coreUnaccented: stripAdminPrefix(name, 'district'),
        coreAccented: stripAccentedPrefix(name, 'district'),
        rawStripped: rawStripped,
        isNumeric: isNumeric
      };
    });

    // Ưu tiên tên dài hơn trước (VD: "Quận Bắc Từ Liêm" trước "Từ Liêm", "Quận 10" trước "Quận 1")
    prepared.sort(function (a, b) {
      return b.coreUnaccented.length - a.coreUnaccented.length;
    });

    // 1. Đối chiếu từ đối tượng address của Nominatim nếu có
    if (addressObj && typeof addressObj === 'object') {
      const candidates = [
        addressObj.city_district,
        addressObj.district,
        addressObj.county,
        addressObj.town,
        addressObj.municipality,
        addressObj.suburb
      ].filter(Boolean);

      for (let c = 0; c < candidates.length; c++) {
        const candUnaccented = normalizeUnaccented(candidates[c]);
        const candCore = stripAdminPrefix(candidates[c], 'district');
        for (let i = 0; i < prepared.length; i++) {
          const item = prepared[i];
          if (item.isNumeric) {
            if (matchNumericAdmin(candUnaccented, 'district', item.rawStripped)) {
              return { id: item.id, name: item.name, segmentIndex: -1 };
            }
          } else if (
            candCore === item.coreUnaccented ||
            candUnaccented === item.fullUnaccented ||
            containsWholePhrase(candUnaccented, item.coreUnaccented)
          ) {
            return { id: item.id, name: item.name, segmentIndex: -1 };
          }
        }
      }
    }

    // 2. Duyệt các phân đoạn từ phải qua trái
    for (let sIdx = segments.length - 1; sIdx >= 0; sIdx--) {
      if (matchedProvince && sIdx === matchedProvince.segmentIndex) {
        // Nếu phân đoạn này chỉ chứa tên tỉnh thì bỏ qua, trừ khi người dùng viết gộp "Cầu Giấy Hà Nội"
        const segOnlyProvince = stripAdminPrefix(segments[sIdx], 'province');
        const provCore = stripAdminPrefix(matchedProvince.name, 'province');
        if (segOnlyProvince === provCore) {
          continue;
        }
      }

      const segRaw = segments[sIdx];
      const segUnaccented = normalizeUnaccented(segRaw);
      const segAccented = normalizeAccented(segRaw);

      for (let i = 0; i < prepared.length; i++) {
        const item = prepared[i];
        if (item.isNumeric) {
          if (matchNumericAdmin(segUnaccented, 'district', item.rawStripped)) {
            return { id: item.id, name: item.name, segmentIndex: sIdx };
          }
        } else {
          if (
            segAccented === item.coreAccented ||
            containsWholePhrase(segAccented, item.coreAccented) ||
            segUnaccented === item.fullUnaccented ||
            segUnaccented === item.coreUnaccented ||
            containsWholePhrase(segUnaccented, item.coreUnaccented)
          ) {
            return { id: item.id, name: item.name, segmentIndex: sIdx };
          }
        }
      }
    }

    // 3. Quét trên toàn bộ chuỗi địa chỉ
    const fullUnaccented = normalizeUnaccented(rawAddress);
    const fullAccented = normalizeAccented(rawAddress);
    for (let i = 0; i < prepared.length; i++) {
      const item = prepared[i];
      if (item.isNumeric) {
        if (matchNumericAdmin(fullUnaccented, 'district', item.rawStripped)) {
          return { id: item.id, name: item.name, segmentIndex: -1 };
        }
      } else if (
        containsWholePhrase(fullAccented, item.coreAccented) ||
        containsWholePhrase(fullUnaccented, item.coreUnaccented)
      ) {
        return { id: item.id, name: item.name, segmentIndex: -1 };
      }
    }

    return null;
  }

  /**
   * Tìm Phường/Xã phù hợp trong danh sách wards của Quận/Huyện đã chọn
   */
  function matchWard(rawAddress, segments, wards, matchedProvince, matchedDistrict, addressObj) {
    if (!Array.isArray(wards) || wards.length === 0) return null;

    const prepared = wards.map(function (w) {
      const name = String(w.name || '').trim();
      const normFull = normalizeRomanSuffix(normalizeUnaccented(name));
      const rawStripped = normFull.replace(/^(phuong|p|xa|x|thi tran|tt)\s+/, '').trim();
      const isNumeric = /^\d+$/.test(rawStripped);
      return {
        id: String(w.id),
        name: name,
        fullUnaccented: normFull,
        coreUnaccented: stripAdminPrefix(name, 'ward'),
        coreAccented: stripAccentedPrefix(name, 'ward'),
        rawStripped: rawStripped,
        isNumeric: isNumeric
      };
    });

    // Ưu tiên tên dài hơn trước (VD: "Phường Dịch Vọng Hậu" trước "Phường Dịch Vọng")
    prepared.sort(function (a, b) {
      return b.coreUnaccented.length - a.coreUnaccented.length;
    });

    // 1. Đối chiếu từ đối tượng address của Nominatim nếu có
    if (addressObj && typeof addressObj === 'object') {
      const candidates = [
        addressObj.ward,
        addressObj.suburb,
        addressObj.quarter,
        addressObj.neighbourhood,
        addressObj.village,
        addressObj.hamlet,
        addressObj.town,
        addressObj.city_district,
        addressObj.district,
        addressObj.county
      ].filter(Boolean);

      for (let c = 0; c < candidates.length; c++) {
        const candUnaccented = normalizeRomanSuffix(normalizeUnaccented(candidates[c]));
        const candCore = stripAdminPrefix(candidates[c], 'ward');
        for (let i = 0; i < prepared.length; i++) {
          const item = prepared[i];
          if (item.isNumeric) {
            if (matchNumericAdmin(candUnaccented, 'ward', item.rawStripped)) {
              return { id: item.id, name: item.name, segmentIndex: -1 };
            }
          } else if (
            candCore === item.coreUnaccented ||
            candUnaccented === item.fullUnaccented ||
            containsWholePhrase(candUnaccented, item.coreUnaccented)
          ) {
            return { id: item.id, name: item.name, segmentIndex: -1 };
          }
        }
      }
    }

    // 2. Duyệt từng phân đoạn từ phải qua trái (bỏ qua phân đoạn đã khớp Tỉnh hoặc Quận)
    for (let sIdx = segments.length - 1; sIdx >= 0; sIdx--) {
      if (matchedProvince && sIdx === matchedProvince.segmentIndex) continue;
      if (matchedDistrict && sIdx === matchedDistrict.segmentIndex) {
        const segNorm = normalizeUnaccented(segments[sIdx]);
        const distFullNorm = normalizeUnaccented(matchedDistrict.name);
        if (segNorm === distFullNorm || segNorm === stripAdminPrefix(matchedDistrict.name, 'district')) {
          continue;
        }
      }

      const segRaw = segments[sIdx];
      const segUnaccented = normalizeRomanSuffix(normalizeUnaccented(segRaw));
      const segAccented = normalizeAccented(segRaw);

      for (let i = 0; i < prepared.length; i++) {
        const item = prepared[i];
        if (item.isNumeric) {
          if (matchNumericAdmin(segUnaccented, 'ward', item.rawStripped)) {
            return { id: item.id, name: item.name, segmentIndex: sIdx };
          }
        } else {
          if (
            segAccented === item.coreAccented ||
            containsWholePhrase(segAccented, item.coreAccented) ||
            segUnaccented === item.fullUnaccented ||
            segUnaccented === item.coreUnaccented ||
            containsWholePhrase(segUnaccented, item.coreUnaccented)
          ) {
            return { id: item.id, name: item.name, segmentIndex: sIdx };
          }
        }
      }
    }

    // 3. Quét trên toàn chuỗi địa chỉ (ưu tiên có tiền tố Phường/Xã/Thị trấn hoặc tên >= 4 ký tự)
    const fullUnaccented = normalizeRomanSuffix(normalizeUnaccented(rawAddress));
    const fullAccented = normalizeAccented(rawAddress);
    for (let i = 0; i < prepared.length; i++) {
      const item = prepared[i];
      if (item.isNumeric) {
        if (matchNumericAdmin(fullUnaccented, 'ward', item.rawStripped)) {
          return { id: item.id, name: item.name, segmentIndex: -1 };
        }
      } else if (
        containsWholePhrase(fullUnaccented, item.fullUnaccented) ||
        containsWholePhrase(fullAccented, item.coreAccented) ||
        containsWholePhrase(fullUnaccented, item.coreUnaccented)
      ) {
        return { id: item.id, name: item.name, segmentIndex: -1 };
      }
    }

    return null;
  }

  /**
   * Đảm bảo phần tử hiển thị phản hồi #addressParseFeedback luôn tồn tại ngay dưới #address-detail
   */
  function ensureFeedbackElement(addressInput) {
    let feedbackEl = document.getElementById('addressParseFeedback');
    if (!feedbackEl && addressInput && addressInput.parentElement) {
      feedbackEl = document.createElement('small');
      feedbackEl.id = 'addressParseFeedback';
      feedbackEl.className = 'text-success d-none align-items-center gap-1 mt-1';
      addressInput.insertAdjacentElement('afterend', feedbackEl);
    }
    return feedbackEl;
  }

  /**
   * Cập nhật dòng trạng thái nhận diện địa chỉ tự động (#addressParseFeedback)
   */
  function updateFeedbackUI(addressInput, matchedProvince, matchedDistrict, matchedWard) {
    const feedbackEl = ensureFeedbackElement(addressInput);
    if (!feedbackEl) return;

    if (!matchedProvince) {
      feedbackEl.classList.remove('d-flex');
      feedbackEl.classList.add('d-none');
      feedbackEl.innerHTML = '';
      return;
    }

    feedbackEl.classList.remove('d-none');
    feedbackEl.classList.add('d-flex');
    feedbackEl.innerHTML = `✓ Đã tự động nhận diện: ${matchedWard ? matchedWard.name + ', ' : ''}${matchedProvince.name}`;
  }

  /**
   * Đồng bộ địa chỉ vừa nhận diện sang bản đồ (#agentMapPicker) để dịch chuyển marker và cập nhật tọa độ
   */
  async function syncToMapPicker(rawAddress, matchedProvince, matchedDistrict, matchedWard) {
    const queryParts = [rawAddress];
    const normRaw = normalizeUnaccented(rawAddress);

    if (matchedWard && matchedWard.name && !containsWholePhrase(normRaw, stripAdminPrefix(matchedWard.name, 'ward'))) {
      queryParts.push(matchedWard.name);
    }
    if (matchedDistrict && matchedDistrict.name && !containsWholePhrase(normRaw, stripAdminPrefix(matchedDistrict.name, 'district'))) {
      queryParts.push(matchedDistrict.name);
    }
    if (matchedProvince && matchedProvince.name && !containsWholePhrase(normRaw, stripAdminPrefix(matchedProvince.name, 'province'))) {
      queryParts.push(matchedProvince.name);
    }

    const fullQuery = queryParts.filter(Boolean).join(', ');
    const mapSearchInput = document.getElementById('mapSearchAddress');
    if (mapSearchInput && fullQuery) {
      mapSearchInput.value = rawAddress;
    }

    if (window.BdsMap && window.BdsMap.activePicker && typeof window.BdsMap.activePicker.geocodeSilent === 'function') {
      await window.BdsMap.activePicker.geocodeSilent(fullQuery);
    } else if (window.BdsMap && window.BdsMap.activePicker && typeof window.BdsMap.activePicker.searchLocation === 'function') {
      await window.BdsMap.activePicker.searchLocation(fullQuery);
    }
  }

  /**
   * Đối chiếu địa chỉ bản đồ (bao gồm phường sau sắp xếp đơn vị hành chính hoặc thiếu cấp Quận)
   * qua API nội bộ GET api/locations/resolve
   */
  async function resolveAdminViaApi(provinceId, provinceName, districtName, wardName, displayName) {
    const cacheKey = [provinceId || '', provinceName || '', districtName || '', wardName || '', displayName || ''].join('|').toLowerCase();
    if (resolveCache.has(cacheKey)) {
      return resolveCache.get(cacheKey);
    }

    const params = new URLSearchParams();
    if (provinceId) params.set('province_id', String(provinceId));
    if (provinceName) params.set('province', String(provinceName));
    if (districtName) params.set('district', String(districtName));
    if (wardName) params.set('ward', String(wardName));
    if (displayName) params.set('display_name', String(displayName));

    const path = 'api/locations/resolve?' + params.toString();
    try {
      let resolved = null;
      if (typeof window.api === 'function') {
        const res = await window.api(path);
        if (res && res.success && res.data) {
          resolved = res.data;
        }
      } else {
        const response = await fetch(`${getBaseUrl()}/${path}`, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.ok) {
          const json = await response.json();
          if (json && json.success && json.data) {
            resolved = json.data;
          }
        }
      }
      resolveCache.set(cacheKey, resolved);
      return resolved;
    } catch (e) {
      return null;
    }
  }

  let parseSequence = 0;

  /**
   * Phân tích chuỗi địa chỉ và tự động chọn Tỉnh/Thành -> Quận/Huyện -> Phường/Xã
   */
  async function parseAndPopulate(rawAddress, options) {
    const opts = options || {};
    const addressInput = document.getElementById('address-detail') || document.querySelector('input[name="address_detail"]');
    const provinceSelect = document.getElementById('province') || document.querySelector('select[name="province_id"]');
    const districtSelect = document.getElementById('district') || document.querySelector('select[name="district_id"]');
    const wardSelect = document.getElementById('ward') || document.querySelector('select[name="ward_id"]');

    if (!provinceSelect || !wardSelect) {
      return null;
    }

    const isTwoTier = !districtSelect || provinceSelect.dataset.level === 'wards';
    if (!isTwoTier && !districtSelect) {
      return null;
    }

    const text = String(rawAddress !== undefined ? rawAddress : (addressInput ? addressInput.value : '')).trim();
    if (text.length < 3 && !opts.addressObj && !opts.directIds) {
      updateFeedbackUI(addressInput, null, null, null);
      return null;
    }

    const currentSeq = ++parseSequence;
    const segments = splitAddressSegments(text);
    const addressObj = opts.addressObj || null;
    const directIds = opts.directIds || null;

    // Bước 1: Tìm Tỉnh/Thành phố
    let matchedProvince = null;
    if (directIds && directIds.province_id) {
      const targetOpt = Array.from(provinceSelect.options).find(function (opt) {
        return String(opt.value) === String(directIds.province_id);
      });
      if (targetOpt) {
        matchedProvince = { id: String(targetOpt.value), name: targetOpt.textContent.trim(), segmentIndex: -1 };
      }
    }
    if (!matchedProvince) {
      matchedProvince = matchProvince(text, segments, provinceSelect, addressObj);
    }

    let resolvedFallback = null;
    if (!matchedProvince) {
      resolvedFallback = await resolveAdminViaApi(
        0,
        addressObj && (addressObj.city || addressObj.state || addressObj.province) ? (addressObj.city || addressObj.state || addressObj.province) : '',
        addressObj && (addressObj.city_district || addressObj.district || addressObj.county) ? (addressObj.city_district || addressObj.district || addressObj.county) : '',
        addressObj && (addressObj.suburb || addressObj.quarter || addressObj.neighbourhood || addressObj.village) ? (addressObj.suburb || addressObj.quarter || addressObj.neighbourhood || addressObj.village) : '',
        text
      );
      if (currentSeq !== parseSequence) return null;
      if (resolvedFallback && resolvedFallback.province) {
        matchedProvince = {
          id: String(resolvedFallback.province.id),
          name: resolvedFallback.province.name,
          segmentIndex: -1
        };
      }
    }

    if (!matchedProvince) {
      updateFeedbackUI(addressInput, null, null, null);
      return null;
    }

    // Mô hình 2 cấp (Tỉnh/Thành -> Phường/Xã): nạp trực tiếp danh sách phường theo tỉnh
    if (isTwoTier) {
      provinceSelect.dataset.autoPopulating = '1';
      provinceSelect.value = String(matchedProvince.id);
      provinceSelect.dataset.selected = String(matchedProvince.id);

      const twoTierWards = await fetchAdminList('wards', matchedProvince.id);
      if (currentSeq !== parseSequence) {
        delete provinceSelect.dataset.autoPopulating;
        return null;
      }

      let twoTierWard = null;
      if (directIds && directIds.ward_id) {
        const targetW2 = twoTierWards.find(function (w) { return String(w.id) === String(directIds.ward_id); });
        if (targetW2) {
          twoTierWard = { id: String(targetW2.id), name: targetW2.name, segmentIndex: -1 };
        }
      }
      if (!twoTierWard) {
        twoTierWard = matchWard(text, segments, twoTierWards, matchedProvince, null, addressObj);
      }

      // Dự phòng: đối soát qua API nội bộ khi chưa khớp được phường
      if (!twoTierWard) {
        const wardHint2 = addressObj
          ? (addressObj.ward || addressObj.suburb || addressObj.quarter || addressObj.neighbourhood || addressObj.village || addressObj.hamlet || addressObj.city_district || addressObj.district || addressObj.county || addressObj.town || '')
          : '';
        const resolved2 = resolvedFallback || await resolveAdminViaApi(
          matchedProvince.id,
          matchedProvince.name,
          '',
          wardHint2,
          text
        );
        if (currentSeq !== parseSequence) {
          delete provinceSelect.dataset.autoPopulating;
          return null;
        }
        if (resolved2 && resolved2.ward) {
          const foundW2 = twoTierWards.find(function (w) { return String(w.id) === String(resolved2.ward.id); });
          twoTierWard = foundW2
            ? { id: String(foundW2.id), name: foundW2.name, segmentIndex: -1 }
            : { id: String(resolved2.ward.id), name: resolved2.ward.name, segmentIndex: -1 };
        }
      }

      populateSelectOptions(
        wardSelect,
        twoTierWards,
        'Chọn phường/xã',
        twoTierWard ? twoTierWard.id : ''
      );
      wardSelect.dataset.selected = twoTierWard ? String(twoTierWard.id) : '';
      if (twoTierWard) {
        wardSelect.value = String(twoTierWard.id);
        wardSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
      provinceSelect.dispatchEvent(new Event('change', { bubbles: true }));
      delete provinceSelect.dataset.autoPopulating;

      // f) Nếu có ô #address-detail (hoặc addressInput): Bóc tách số nhà + tên đường (loại bỏ phần tên phường và tỉnh đã nhận diện) điền vào #address-detail
      if (addressInput && opts.fillDetail !== false && !opts.isTyping) {
        const extracted = extractStreetAddress(text, addressObj, {
          province: matchedProvince,
          district: null,
          ward: twoTierWard
        }, {
          houseNumberHint: opts.houseNumberHint || ''
        });
        if (extracted && addressInput.value !== extracted) {
          addressInput.dataset.suppressAutoParse = '1';
          addressInput.value = extracted;
          setTimeout(function () {
            delete addressInput.dataset.suppressAutoParse;
          }, 80);
        }
      }

      updateFeedbackUI(addressInput, matchedProvince, null, twoTierWard);

      if (!opts.skipGeocode && text.length >= 5) {
        await syncToMapPicker(text, matchedProvince, null, twoTierWard);
      }

      return {
        province: matchedProvince,
        district: null,
        ward: twoTierWard
      };
    }

    // Gán giá trị cho #province và tải danh sách Quận/Huyện qua Ajax
    provinceSelect.dataset.autoPopulating = '1';
    provinceSelect.value = matchedProvince.id;
    provinceSelect.dataset.selected = matchedProvince.id;

    const districts = await fetchAdminList('districts', matchedProvince.id);
    if (currentSeq !== parseSequence) {
      delete provinceSelect.dataset.autoPopulating;
      return null;
    }

    // Bước 2: Tìm Quận/Huyện trong danh sách vừa tải về
    let matchedDistrict = null;
    if (directIds && directIds.district_id) {
      const targetD = districts.find(function (d) { return String(d.id) === String(directIds.district_id); });
      if (targetD) {
        matchedDistrict = { id: String(targetD.id), name: targetD.name, segmentIndex: -1 };
      }
    }
    if (!matchedDistrict) {
      matchedDistrict = matchDistrict(text, segments, districts, matchedProvince, addressObj);
    }

    let wards = [];
    let matchedWard = null;
    if (matchedDistrict) {
      wards = await fetchAdminList('wards', matchedDistrict.id);
      if (currentSeq !== parseSequence) {
        delete provinceSelect.dataset.autoPopulating;
        return null;
      }
      if (directIds && directIds.ward_id) {
        const targetW = wards.find(function (w) { return String(w.id) === String(directIds.ward_id); });
        if (targetW) {
          matchedWard = { id: String(targetW.id), name: targetW.name, segmentIndex: -1 };
        }
      }
      if (!matchedWard) {
        matchedWard = matchWard(text, segments, wards, matchedProvince, matchedDistrict, addressObj);
      }
    }

    // Nếu thiếu Quận hoặc thiếu Phường (ví dụ bản đồ trả về phường mới sau sáp nhập 2025 hoặc bỏ trống city_district),
    // đối soát trực tiếp qua CSDL 63 Tỉnh / 696 Quận / 10.052 Phường
    if (!matchedDistrict || !matchedWard) {
      const wardHint = addressObj
        ? (addressObj.suburb || addressObj.quarter || addressObj.neighbourhood || addressObj.village || addressObj.hamlet || '')
        : '';
      const distHint = matchedDistrict
        ? matchedDistrict.name
        : (addressObj ? (addressObj.city_district || addressObj.district || addressObj.county || addressObj.town || '') : '');

      if (!resolvedFallback) {
        resolvedFallback = await resolveAdminViaApi(
          matchedProvince.id,
          matchedProvince.name,
          distHint,
          wardHint,
          text
        );
      }
      if (currentSeq !== parseSequence) {
        delete provinceSelect.dataset.autoPopulating;
        return null;
      }

      if (resolvedFallback) {
        if (!matchedDistrict && resolvedFallback.district) {
          const foundD = districts.find(function (d) { return String(d.id) === String(resolvedFallback.district.id); });
          matchedDistrict = foundD
            ? { id: String(foundD.id), name: foundD.name, segmentIndex: -1 }
            : { id: String(resolvedFallback.district.id), name: resolvedFallback.district.name, segmentIndex: -1 };
          wards = await fetchAdminList('wards', matchedDistrict.id);
          if (currentSeq !== parseSequence) {
            delete provinceSelect.dataset.autoPopulating;
            return null;
          }
          matchedWard = matchWard(text, segments, wards, matchedProvince, matchedDistrict, addressObj);
        }

        if (matchedDistrict && !matchedWard && resolvedFallback.ward && resolvedFallback.district && String(resolvedFallback.district.id) === String(matchedDistrict.id)) {
          const foundW = wards.find(function (w) { return String(w.id) === String(resolvedFallback.ward.id); });
          matchedWard = foundW
            ? { id: String(foundW.id), name: foundW.name, segmentIndex: -1 }
            : { id: String(resolvedFallback.ward.id), name: resolvedFallback.ward.name, segmentIndex: -1 };
        }
      }
    }

    populateSelectOptions(
      districtSelect,
      districts,
      'Chọn quận/huyện',
      matchedDistrict ? matchedDistrict.id : ''
    );
    delete provinceSelect.dataset.autoPopulating;

    if (matchedDistrict) {
      districtSelect.dataset.autoPopulating = '1';
      if (wards.length === 0) {
        wards = await fetchAdminList('wards', matchedDistrict.id);
        if (currentSeq !== parseSequence) {
          delete districtSelect.dataset.autoPopulating;
          return null;
        }
      }
      populateSelectOptions(
        wardSelect,
        wards,
        'Chọn phường/xã',
        matchedWard ? matchedWard.id : ''
      );
      delete districtSelect.dataset.autoPopulating;
    } else {
      populateSelectOptions(wardSelect, [], 'Chọn phường/xã', '');
    }

    // Bước 4: Cập nhật giao diện phản hồi thông minh
    updateFeedbackUI(addressInput, matchedProvince, matchedDistrict, matchedWard);

    // Bước 5: Kích hoạt định vị tọa độ trên bản đồ (nếu sự kiện đến từ người dùng nhập địa chỉ)
    if (!opts.skipGeocode && text.length >= 5) {
      await syncToMapPicker(text, matchedProvince, matchedDistrict, matchedWard);
    }

    return {
      province: matchedProvince,
      district: matchedDistrict,
      ward: matchedWard
    };
  }

  /**
   * Trích xuất số nhà và tên đường chi tiết từ dữ liệu Geocoding / Reverse Geocoding
   */
  function extractStreetAddress(displayName, addressObj, matchedResult, options) {
    const opts = options || {};
    const hint = String(opts.houseNumberHint || '').trim();

    if (addressObj && typeof addressObj === 'object') {
      const road = String(addressObj.road || addressObj.pedestrian || addressObj.residential || addressObj.street || '').trim();
      let houseNo = String(addressObj.house_number || hint || '').trim();
      if (!houseNo && displayName) {
        const m = String(displayName).match(/^(\d+[A-Za-z]?(?:\/\d+[A-Za-z]?)*)\s*[, -]/);
        if (m) {
          houseNo = m[1];
        }
      }
      const streetParts = [];
      if (houseNo) streetParts.push(houseNo);
      if (road) streetParts.push(road);

      if (streetParts.length > 0) {
        return streetParts.join(' ');
      }
    }

    if (!displayName) return hint;
    // Loại bỏ các phân đoạn hành chính, khu phố, mã bưu chính để giữ lại phần số nhà/tên đường
    const rawSegments = splitAddressSegments(displayName);
    const cleaned = rawSegments.filter(function (seg) {
      const sNorm = normalizeUnaccented(seg);
      if (!sNorm) return false;
      if (/^(khu pho|kp|to dan pho|to|ap|thon|xom)\b/.test(sNorm)) return false;
      if (matchedResult && matchedResult.province) {
        const provCore = stripAdminPrefix(matchedResult.province.name, 'province');
        const segProvCore = stripAdminPrefix(seg, 'province');
        if (segProvCore === provCore || sNorm === normalizeUnaccented(matchedResult.province.name)) return false;
        const aliases = PROVINCE_ALIASES[provCore] || [];
        if (aliases.indexOf(sNorm) !== -1 || aliases.indexOf(segProvCore) !== -1) return false;
      }
      if (matchedResult && matchedResult.district && stripAdminPrefix(seg, 'district') === stripAdminPrefix(matchedResult.district.name, 'district')) return false;
      if (matchedResult && matchedResult.ward) {
        const wardCore = stripAdminPrefix(matchedResult.ward.name, 'ward');
        const segWardCore = stripAdminPrefix(seg, 'ward');
        if (segWardCore === wardCore || sNorm === normalizeUnaccented(matchedResult.ward.name)) return false;
      }
      if (/^(phuong|p|xa|x|thi tran|tt|quan|q|huyen|h|thi xa|tx|thanh pho|tp|tinh)\b/.test(sNorm)) return false;
      return true;
    });

    if (cleaned.length > 0) {
      let firstLine = cleaned[0];
      if (cleaned.length === 1 && matchedResult) {
        if (matchedResult.province && matchedResult.province.name) {
          firstLine = firstLine.replace(new RegExp('(?:,\\s*)?' + escapeRegex(matchedResult.province.name), 'gi'), '').trim();
          const pCore = stripAccentedPrefix(matchedResult.province.name, 'province');
          if (pCore) {
            firstLine = firstLine.replace(new RegExp('(?:,\\s*)?' + escapeRegex(pCore), 'gi'), '').trim();
          }
        }
        if (matchedResult.ward && matchedResult.ward.name) {
          firstLine = firstLine.replace(new RegExp('(?:,\\s*)?' + escapeRegex(matchedResult.ward.name), 'gi'), '').trim();
          const wCore = stripAccentedPrefix(matchedResult.ward.name, 'ward');
          if (wCore) {
            firstLine = firstLine.replace(new RegExp('(?:,\\s*)?' + escapeRegex(wCore), 'gi'), '').trim();
          }
        }
        firstLine = firstLine.replace(/,\s*$/, '').trim();
        cleaned[0] = firstLine;
      }
      if (hint && !/^\d/.test(firstLine)) {
        return hint + ' ' + firstLine;
      }
      return cleaned.slice(0, 2).join(', ');
    }

    return hint;
  }

  /**
   * Xử lý chiều ngược lại (Reverse Geocoding hoặc chọn từ gợi ý tìm kiếm bản đồ):
   * Phân tích địa chỉ, tự động chọn Tỉnh -> Quận -> Phường và điền số nhà + tên đường vào #address-detail
   */
  async function handleReverseGeocode(displayName, addressObj, options) {
    const opts = options || {};
    const addressInput = document.getElementById('address-detail') || document.querySelector('input[name="address_detail"]');
    const matched = await parseAndPopulate(displayName || '', {
      addressObj: addressObj || null,
      directIds: opts.directIds || null,
      skipGeocode: true,
      fillDetail: opts.fillInput !== false,
      houseNumberHint: opts.houseNumberHint || ''
    });

    if (addressInput && opts.fillInput !== false) {
      const formattedAddress = extractStreetAddress(displayName, addressObj, matched, {
        houseNumberHint: opts.houseNumberHint || ''
      });
      if (formattedAddress && addressInput.value !== formattedAddress) {
        addressInput.dataset.suppressAutoParse = '1';
        addressInput.value = formattedAddress;
        setTimeout(function () {
          delete addressInput.dataset.suppressAutoParse;
        }, 80);
      }
    }

    const mapSearchInput = document.getElementById('mapSearchAddress');
    if (mapSearchInput && opts.updateMapSearch !== false && displayName) {
      mapSearchInput.dataset.suppressSearchInput = '1';
      mapSearchInput.value = displayName;
      setTimeout(function () {
        delete mapSearchInput.dataset.suppressSearchInput;
      }, 80);
    }

    return matched;
  }

  /**
   * Khởi tạo lắng nghe sự kiện trên ô #address-detail
   */
  function initAddressParser() {
    const addressInput = document.getElementById('address-detail') || document.querySelector('input[name="address_detail"]');
    if (!addressInput || addressInput.dataset.parserBound === '1') return;
    addressInput.dataset.parserBound = '1';

    ensureFeedbackElement(addressInput);

    let debounceTimer = null;

    addressInput.addEventListener('input', function () {
      if (addressInput.dataset.suppressAutoParse === '1') return;
      if (debounceTimer) clearTimeout(debounceTimer);
      const val = addressInput.value.trim();
      if (!val) {
        updateFeedbackUI(addressInput, null, null, null);
        return;
      }
      debounceTimer = setTimeout(function () {
        parseAndPopulate(val, { skipGeocode: false, isTyping: true });
      }, DEBOUNCE_MS);
    });

    const handleImmediateParse = function () {
      if (addressInput.dataset.suppressAutoParse === '1') return;
      if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
      }
      const val = addressInput.value.trim();
      if (!val) {
        updateFeedbackUI(addressInput, null, null, null);
        return;
      }
      parseAndPopulate(val, { skipGeocode: false, fillDetail: true });
    };

    addressInput.addEventListener('blur', handleImmediateParse);
    addressInput.addEventListener('change', handleImmediateParse);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAddressParser);
  } else {
    initAddressParser();
  }

  window.BdsAddressParser = {
    init: initAddressParser,
    parseAndPopulate: parseAndPopulate,
    handleReverseGeocode: handleReverseGeocode,
    normalizeUnaccented: normalizeUnaccented
  };
})(window, document);
