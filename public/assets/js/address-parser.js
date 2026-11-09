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
      'ho chi minh', 'sai gon', 'saigon', 'sg'
    ],
    'ha noi': [
      'tp.hn', 'tphn', 'tp hn', 'hn', 'tp ha noi', 'thanh pho ha noi',
      'ha noi', 'hanoi', 'thu do ha noi'
    ],
    'da nang': [
      'tp.dn', 'tpdn', 'tp dn', 'tp da nang', 'thanh pho da nang',
      'da nang', 'danang'
    ],
    'hai phong': [
      'tp.hp', 'tphp', 'tp hp', 'tp hai phong', 'thanh pho hai phong', 'hai phong'
    ],
    'can tho': [
      'tp.ct', 'tpct', 'tp ct', 'tp can tho', 'thanh pho can tho', 'can tho'
    ],
    'hue': [
      'thua thien hue', 'tinh thua thien hue', 'tp hue', 'thanh pho hue', 'hue'
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
        norm.replace(/^(phuong|p|xa|x|thi tran|tt)\s+/, '').trim()
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
      const stripped = norm.replace(/^(phường|p|xã|x|thị trấn|tt)\s+/u, '').trim();
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

    const cache = level === 'districts' ? districtCache : wardCache;
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
        addressObj.suburb,
        addressObj.quarter,
        addressObj.neighbourhood,
        addressObj.village,
        addressObj.hamlet,
        addressObj.town
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

    if (!matchedProvince && !matchedDistrict && !matchedWard) {
      feedbackEl.classList.remove('d-flex');
      feedbackEl.classList.add('d-none');
      feedbackEl.innerHTML = '';
      return;
    }

    const parts = [];
    if (matchedWard && matchedWard.name) {
      parts.push(matchedWard.name);
    }
    if (matchedDistrict && matchedDistrict.name) {
      parts.push(matchedDistrict.name);
    }
    if (matchedProvince && matchedProvince.name) {
      const pName = matchedProvince.name;
      const pNorm = normalizeUnaccented(pName);
      if (pNorm.indexOf('thanh pho') === 0 || pNorm.indexOf('tp') === 0 || pNorm.indexOf('tinh') === 0) {
        parts.push(pName);
      } else {
        parts.push('Thành phố ' + pName);
      }
    }

    if (parts.length === 0) {
      feedbackEl.classList.remove('d-flex');
      feedbackEl.classList.add('d-none');
      feedbackEl.innerHTML = '';
      return;
    }

    feedbackEl.className = 'text-success d-flex align-items-center gap-1 mt-1';
    feedbackEl.innerHTML = `<i class="bi bi-geo-alt-fill"></i> Đã tự động nhận diện: ${parts.join(', ')}`;
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

    if (!provinceSelect || !districtSelect || !wardSelect) {
      return null;
    }

    const text = String(rawAddress !== undefined ? rawAddress : (addressInput ? addressInput.value : '')).trim();
    if (text.length < 3 && !opts.addressObj) {
      updateFeedbackUI(addressInput, null, null, null);
      return null;
    }

    const currentSeq = ++parseSequence;
    const segments = splitAddressSegments(text);
    const addressObj = opts.addressObj || null;

    // Bước 1: Tìm Tỉnh/Thành phố
    const matchedProvince = matchProvince(text, segments, provinceSelect, addressObj);
    if (!matchedProvince) {
      updateFeedbackUI(addressInput, null, null, null);
      return null;
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
    const matchedDistrict = matchDistrict(text, segments, districts, matchedProvince, addressObj);
    populateSelectOptions(
      districtSelect,
      districts,
      'Chọn quận/huyện',
      matchedDistrict ? matchedDistrict.id : ''
    );
    delete provinceSelect.dataset.autoPopulating;

    let matchedWard = null;
    if (matchedDistrict) {
      districtSelect.dataset.autoPopulating = '1';
      const wards = await fetchAdminList('wards', matchedDistrict.id);
      if (currentSeq !== parseSequence) {
        delete districtSelect.dataset.autoPopulating;
        return null;
      }

      // Bước 3: Tìm Phường/Xã trong danh sách vừa tải về
      matchedWard = matchWard(text, segments, wards, matchedProvince, matchedDistrict, addressObj);
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
   * Trích xuất tên đường / số nhà chi tiết từ dữ liệu Reverse Geocoding (Nominatim)
   */
  function extractStreetAddress(displayName, addressObj, matchedResult) {
    if (addressObj && typeof addressObj === 'object') {
      const streetParts = [];
      if (addressObj.house_number) streetParts.push(String(addressObj.house_number).trim());
      if (addressObj.road) streetParts.push(String(addressObj.road).trim());
      else if (addressObj.pedestrian) streetParts.push(String(addressObj.pedestrian).trim());
      else if (addressObj.residential) streetParts.push(String(addressObj.residential).trim());

      if (streetParts.length > 0) {
        const streetLine = streetParts.join(' ');
        const contextParts = [streetLine];
        if (matchedResult && matchedResult.ward) contextParts.push(matchedResult.ward.name);
        if (matchedResult && matchedResult.district) contextParts.push(matchedResult.district.name);
        if (matchedResult && matchedResult.province) contextParts.push(matchedResult.province.name);
        return contextParts.join(', ');
      }
    }

    if (!displayName) return '';
    // Làm sạch hậu tố mã bưu chính và tên quốc gia ở cuối chuỗi display_name
    const cleanedSegments = splitAddressSegments(displayName);
    return cleanedSegments.join(', ') || String(displayName).trim();
  }

  /**
   * Xử lý chiều ngược lại (Reverse Geocoding):
   * Khi người dùng kéo thả ghim hoặc bấm trên bản đồ -> phân tích địa chỉ từ Nominatim,
   * tự động chọn Tỉnh -> Quận -> Phường và điền địa chỉ chi tiết vào #address-detail
   */
  async function handleReverseGeocode(displayName, addressObj, options) {
    const opts = options || {};
    const addressInput = document.getElementById('address-detail') || document.querySelector('input[name="address_detail"]');
    const matched = await parseAndPopulate(displayName || '', {
      addressObj: addressObj || null,
      skipGeocode: true
    });

    if (addressInput && opts.fillInput !== false) {
      const formattedAddress = extractStreetAddress(displayName, addressObj, matched);
      if (formattedAddress) {
        addressInput.dataset.suppressAutoParse = '1';
        addressInput.value = formattedAddress;
        const mapSearchInput = document.getElementById('mapSearchAddress');
        if (mapSearchInput) {
          mapSearchInput.value = formattedAddress;
        }
        setTimeout(function () {
          delete addressInput.dataset.suppressAutoParse;
        }, 50);
      }
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
        parseAndPopulate(val, { skipGeocode: false });
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
      parseAndPopulate(val, { skipGeocode: false });
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
