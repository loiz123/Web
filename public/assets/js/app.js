// Tiện ích AJAX, Hệ thống Toast & Modal Xác nhận chuẩn UX thương mại BĐS Việt
var BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';
var CSRF = document.querySelector('meta[name="csrf"]')?.content || '';

async function api(path, { method = 'GET', body = null } = {}) {
  const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': CSRF } };
  if (body) opts.body = body instanceof FormData ? body : new URLSearchParams(body);
  const res = await fetch(`${BASE_URL}/${path}`, opts);
  return res.json();
}

// =============================================================================
// Hệ thống Toast Thông báo & Modal Xác nhận sang trọng (Bootstrap 5)
// =============================================================================
window.BDS = (function () {
  const TOAST_DURATION_MS = 3500;

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function ensureToastContainer() {
    let container = document.getElementById('bdsToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'bdsToastContainer';
      container.className = 'bds-toast-container';
      container.setAttribute('aria-live', 'polite');
      container.setAttribute('aria-atomic', 'true');
      document.body.appendChild(container);
    }
    return container;
  }

  /**
   * Hiển thị Toast thông báo nổi chuẩn Bootstrap 5
   * @param {string} message Nội dung thông báo
   * @param {'success'|'danger'|'info'|'warning'} type Phân loại thông báo
   * @param {string} title Tiêu đề thông báo
   */
  function toast(message, type = 'info', title = 'Thông báo') {
    const normalizedType = type === 'error' ? 'danger' : (['success', 'danger', 'info', 'warning'].includes(type) ? type : 'info');
    const iconMap = {
      success: 'bi-check-circle-fill',
      danger: 'bi-exclamation-octagon-fill',
      warning: 'bi-exclamation-triangle-fill',
      info: 'bi-info-circle-fill',
    };

    const container = ensureToastContainer();
    const toastEl = document.createElement('div');
    toastEl.className = `toast show border-0 bds-toast bds-toast-${normalizedType}`;
    toastEl.setAttribute('role', 'status');
    toastEl.setAttribute('aria-live', 'polite');

    toastEl.innerHTML = `
      <div class="d-flex align-items-start gap-3 p-3">
        <div class="bds-toast-icon">
          <i class="bi ${iconMap[normalizedType]}"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <strong class="text-dark small fw-bold">${escapeHtml(title || 'Thông báo')}</strong>
            <button type="button" class="btn-close small" style="font-size: 0.7rem;" aria-label="Đóng thông báo"></button>
          </div>
          <div class="text-secondary small lh-sm">${escapeHtml(message)}</div>
        </div>
      </div>
      <div class="bds-toast-progress"></div>
    `;

    let removed = false;
    function dismiss() {
      if (removed) return;
      removed = true;
      toastEl.style.transition = 'opacity 0.22s ease, transform 0.22s ease';
      toastEl.style.opacity = '0';
      toastEl.style.transform = 'translateX(24px)';
      setTimeout(function () {
        toastEl.remove();
      }, 230);
    }

    const closeBtn = toastEl.querySelector('.btn-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', dismiss);
    }

    const timer = setTimeout(dismiss, TOAST_DURATION_MS);
    toastEl.addEventListener('mouseenter', function () {
      clearTimeout(timer);
      const bar = toastEl.querySelector('.bds-toast-progress');
      if (bar) bar.style.animationPlayState = 'paused';
    });
    toastEl.addEventListener('mouseleave', function () {
      setTimeout(dismiss, 1400);
    });

    container.appendChild(toastEl);
    return toastEl;
  }

  function ensureConfirmModal() {
    let modalEl = document.getElementById('bdsConfirmModal');
    if (!modalEl) {
      modalEl = document.createElement('div');
      modalEl.id = 'bdsConfirmModal';
      modalEl.className = 'modal fade bds-confirm-modal';
      modalEl.tabIndex = -1;
      modalEl.setAttribute('aria-hidden', 'true');
      modalEl.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
          <div class="modal-content overflow-hidden">
            <div class="modal-body p-4 text-center">
              <div class="bds-confirm-icon-wrap mx-auto mb-3" id="bdsConfirmIconWrap">
                <i class="bi bi-question-circle-fill" id="bdsConfirmIcon"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2" id="bdsConfirmTitle">Xác nhận thao tác</h5>
              <p class="text-secondary mb-0 small lh-base" id="bdsConfirmMessage"></p>
            </div>
            <div class="modal-footer border-top bg-light px-4 py-3 d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-outline-secondary fw-semibold rounded-3 flex-fill bds-btn-touch" data-bs-dismiss="modal" id="bdsConfirmCancelBtn">
                Hủy bỏ
              </button>
              <button type="button" class="btn btn-danger fw-bold rounded-3 flex-fill bds-btn-touch" id="bdsConfirmOkBtn">
                Xác nhận
              </button>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modalEl);
    }
    return modalEl;
  }

  /**
   * Hiển thị Modal xác nhận Bootstrap 5 sang trọng thay thế window.confirm()
   * Hỗ trợ cả callback (onConfirm, onCancel) và Promise<boolean>
   */
  function confirm(message, title = 'Xác nhận', onConfirm = null, onCancel = null) {
    return new Promise(function (resolve) {
      const modalEl = ensureConfirmModal();
      const titleEl = modalEl.querySelector('#bdsConfirmTitle');
      const msgEl = modalEl.querySelector('#bdsConfirmMessage');
      const okBtn = modalEl.querySelector('#bdsConfirmOkBtn');
      const cancelBtn = modalEl.querySelector('#bdsConfirmCancelBtn');

      if (titleEl) titleEl.textContent = title || 'Xác nhận';
      if (msgEl) msgEl.textContent = message || 'Bạn có chắc chắn muốn thực hiện thao tác này?';

      if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        // Dự phòng cực hiếm nếu chưa nạp Bootstrap JS
        toast(message, 'warning', title);
        resolve(false);
        return;
      }

      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
      let settled = false;

      function handleOk() {
        if (settled) return;
        settled = true;
        cleanup();
        bsModal.hide();
        if (typeof onConfirm === 'function') onConfirm();
        resolve(true);
      }

      function handleCancel() {
        if (settled) return;
        settled = true;
        cleanup();
        bsModal.hide();
        if (typeof onCancel === 'function') onCancel();
        resolve(false);
      }

      function handleHidden() {
        if (settled) return;
        settled = true;
        cleanup();
        if (typeof onCancel === 'function') onCancel();
        resolve(false);
      }

      function cleanup() {
        okBtn.removeEventListener('click', handleOk);
        cancelBtn.removeEventListener('click', handleCancel);
        modalEl.removeEventListener('hidden.bs.modal', handleHidden);
      }

      okBtn.addEventListener('click', handleOk);
      cancelBtn.addEventListener('click', handleCancel);
      modalEl.addEventListener('hidden.bs.modal', handleHidden);

      bsModal.show();
    });
  }

  // Tự động hỗ trợ các form khai báo data-bds-confirm="..."
  document.addEventListener('submit', function (event) {
    const form = event.target.closest('form[data-bds-confirm]');
    if (!form) return;
    if (form.dataset.bdsConfirmPassed === '1') {
      form.dataset.bdsConfirmPassed = '0';
      return;
    }
    event.preventDefault();
    const message = form.getAttribute('data-bds-confirm') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
    const title = form.getAttribute('data-bds-confirm-title') || 'Xác nhận thao tác';
    confirm(message, title, function () {
      form.dataset.bdsConfirmPassed = '1';
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  });

  return {
    toast: toast,
    confirm: confirm,
  };
})();

function fillSelect(select, items, placeholder) {
  select.innerHTML = `<option value="">${placeholder}</option>`;
  items.forEach(function (item) {
    select.add(new Option(item.name, item.id));
  });
}

async function loadLocationOptions(url, select, placeholder, selectedId) {
  fillSelect(select, [], placeholder);
  if (!url) return;
  try {
    const response = await api(url);
    if (!response.success) throw new Error(response.message || 'Không thể tải danh sách khu vực.');
    fillSelect(select, response.data, placeholder);
    if (selectedId) select.value = selectedId;
  } catch (error) {
    BDS.toast('Không thể tải danh sách khu vực hành chính. Vui lòng tải lại trang và thử lại.', 'danger', 'Lỗi kết nối');
  }
}

function setupAgentForm() {
  var province = document.querySelector('#province');
  if (!province) return;

  var district = document.querySelector('#district');
  var ward = document.querySelector('#ward');
  if (!district || !ward) return;
  var selectedDistrict = district.dataset.selected;
  var selectedWard = ward.dataset.selected;

  province.addEventListener('change', function () {
    if (province.dataset.autoPopulating === '1') return;
    selectedDistrict = '';
    selectedWard = '';
    loadLocationOptions(
      province.value ? `api/districts/${province.value}` : '',
      district,
      'Chọn quận/huyện',
      ''
    );
    fillSelect(ward, [], 'Chọn phường/xã');
  });

  district.addEventListener('change', function () {
    if (district.dataset.autoPopulating === '1') return;
    selectedWard = '';
    loadLocationOptions(
      district.value ? `api/wards/${district.value}` : '',
      ward,
      'Chọn phường/xã',
      ''
    );
  });

  if (province.value) {
    loadLocationOptions(
      `api/districts/${province.value}`,
      district,
      'Chọn quận/huyện',
      selectedDistrict
    ).then(function () {
      if (district.value) {
        loadLocationOptions(
          `api/wards/${district.value}`,
          ward,
          'Chọn phường/xã',
          selectedWard
        );
      }
    });
  }

  var listingType = document.querySelector('#listing-type');
  var rentFields = document.querySelector('#rent-fields');
  if (listingType && rentFields) {
    var rentInputs = rentFields.querySelectorAll('input, select');
    function toggleRentFields() {
      var isRent = listingType.value === 'rent';
      rentFields.hidden = !isRent;
      rentFields.classList.toggle('d-none', !isRent);
      rentInputs.forEach(function (input) {
        input.disabled = !isRent;
      });
    }
    listingType.addEventListener('change', toggleRentFields);
    toggleRentFields();
  }

  var imageInput = document.querySelector('#images');
  if (imageInput) {
    imageInput.addEventListener('change', function () {
      var files = imageInput.files;
      var error = '';
      if (files.length < 1 || files.length > 10) error = 'Chọn từ 1 đến 10 ảnh.';
      Array.from(files).forEach(function (file) {
        if (file.size > 5 * 1024 * 1024) error = 'Mỗi ảnh phải nhỏ hơn 5 MB.';
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
          error = 'Ảnh chỉ được có định dạng JPG, PNG hoặc WEBP.';
        }
      });
      imageInput.setCustomValidity(error);
      if (error) {
        BDS.toast(error, 'warning', 'Kiểm tra hình ảnh');
      }

      var preview = document.querySelector('#image-preview');
      if (!preview) return;
      preview.replaceChildren();
      var hiddenCover = document.querySelector('input[name="cover_image"][type="hidden"]');
      if (hiddenCover) hiddenCover.remove();

      Array.from(files).forEach(function (file, index) {
        var column = document.createElement('div');
        column.className = 'col-6 col-md-3';
        var card = document.createElement('div');
        card.className = 'card h-100';
        var image = document.createElement('img');
        image.className = 'card-img-top';
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        image.style.height = '140px';
        image.style.objectFit = 'cover';
        var label = document.createElement('label');
        label.className = 'card-body form-check';
        var radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'cover_image';
        radio.value = String(index);
        radio.checked = index === 0;
        radio.className = 'form-check-input';
        var text = document.createElement('span');
        text.className = 'form-check-label';
        text.textContent = 'Ảnh bìa';
        label.append(radio, text);
        card.append(image, label);
        column.append(card);
        preview.append(column);
      });
    });
  }
}

document.querySelectorAll('select[data-target]').forEach(function (province) {
  if (province.dataset.wardDirectBound === '1' || province.dataset.bdsBound === '1') return;
  var targetSelect = document.querySelector(province.dataset.target);
  if (!targetSelect) return;

  var isWardLevel = (province.dataset.level === 'wards' || targetSelect.name === 'ward_id' || targetSelect.id === 'searchWard');
  var placeholder = targetSelect.dataset.placeholder || (isWardLevel ? 'Phường/Xã' : 'Quận/Huyện');

  async function updateChildOptions(keepSelected) {
    if (!province.value) {
      fillSelect(targetSelect, [], placeholder);
      return;
    }
    var endpoint = isWardLevel
      ? `api/wards?province_id=${encodeURIComponent(province.value)}`
      : `api/districts/${province.value}`;
    var response = await api(endpoint);
    var items = Array.isArray(response) ? response : (response.success ? response.data : []);
    if (isWardLevel && Array.isArray(items)) {
      var seenNames = Object.create(null);
      items = items.filter(function (w) {
        var n = String(w.name || '').trim();
        if (!n || seenNames[n]) return false;
        seenNames[n] = true;
        return true;
      });
    }
    fillSelect(targetSelect, items, placeholder);
    if (keepSelected && targetSelect.dataset.selected) {
      targetSelect.value = targetSelect.dataset.selected;
    }
  }

  province.addEventListener('change', function () {
    targetSelect.dataset.selected = '';
    updateChildOptions(false);
  });

  if (province.value && targetSelect.options.length <= 1) {
    updateChildOptions(true);
  }
});

async function toggleCardFavorite(event, propertyId, btn) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  if (!btn) return;
  btn.disabled = true;

  try {
    var response = await api('favorite/toggle/' + propertyId, { method: 'POST' });
    if (response.success) {
      if (response.data && response.data.liked) {
        btn.textContent = '❤️';
        BDS.toast('Đã thêm bất động sản vào danh sách yêu thích.', 'success', 'Đã lưu tin yêu thích');
      } else {
        btn.textContent = '🤍';
        BDS.toast('Đã gỡ bất động sản khỏi danh sách yêu thích.', 'warning', 'Cập nhật yêu thích');
      }
    } else {
      if (response.data && response.data.require_login) {
        BDS.confirm(
          'Vui lòng đăng nhập tài khoản để lưu bất động sản yêu thích. Bạn có muốn chuyển đến trang đăng nhập ngay không?',
          'Yêu cầu đăng nhập',
          function () {
            window.location.href = (BASE_URL || '') + '/auth/login';
          }
        );
      } else {
        BDS.toast(response.message || 'Không thể thực hiện thao tác lúc này.', 'danger', 'Thông báo');
      }
    }
  } catch (error) {
    BDS.toast('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.', 'danger', 'Lỗi kết nối');
  } finally {
    btn.disabled = false;
  }
}

var agentStatusLabels = {
  pending: 'Chờ duyệt',
  approved: 'Đã duyệt',
  rejected: 'Bị từ chối',
  hidden: 'Đã ẩn',
  closed: 'Đã đóng'
};

function agentStatusForm(path, status, label, buttonClass) {
  return `<form method="post" action="${BASE_URL}/${path}" data-status-path="${path}" data-status-form>` +
    `<input type="hidden" name="_csrf" value="${CSRF}">` +
    `<input type="hidden" name="status" value="${status}">` +
    `<button type="submit" class="btn btn-sm ${buttonClass}">${label}</button></form>`;
}

function renderAgentStatusActions(card, status) {
  var actions = card.querySelector('[data-status-actions]');
  if (!actions) return;
  var pathElement = actions.querySelector('[data-status-path]');
  var path = pathElement ? pathElement.dataset.statusPath : '';
  if (!path) return;
  if (status === 'approved') {
    actions.innerHTML = agentStatusForm(path, 'hidden', 'Ẩn tin', 'btn-outline-secondary') +
      agentStatusForm(path, 'closed', 'Đóng tin', 'btn-outline-warning');
  } else if (status === 'hidden') {
    actions.innerHTML = agentStatusForm(path, 'pending', 'Gửi duyệt lại', 'btn-outline-success');
  } else if (status === 'closed') {
    actions.innerHTML = agentStatusForm(path, 'pending', 'Mở lại tin', 'btn-outline-success');
  } else {
    actions.innerHTML = '';
  }
  bindAgentStatusForms();
}

function bindAgentStatusForms() {
  document.querySelectorAll('[data-status-form]').forEach(function (form) {
    if (form.dataset.bound === 'true') return;
    form.dataset.bound = 'true';
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      var button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
      try {
        var response = await api(form.dataset.statusPath, {
          method: 'POST',
          body: new FormData(form)
        });
        if (!response.success) throw new Error(response.message || 'Không thể cập nhật trạng thái.');
        var card = form.closest('[data-listing-card]');
        var status = response.data.status;
        var badge = card.querySelector('[data-status-badge]');
        badge.textContent = agentStatusLabels[status] || status;
        badge.className = 'badge align-self-start ' + (status === 'approved' ? 'text-bg-success' : 'text-bg-secondary');
        var reason = card.querySelector('[data-reject-reason]');
        if (reason) reason.remove();
        renderAgentStatusActions(card, status);
        BDS.toast('Đã cập nhật trạng thái tin đăng thành công.', 'success', 'Cập nhật trạng thái');
      } catch (error) {
        BDS.toast(error.message || 'Không thể kết nối đến máy chủ. Vui lòng thử lại.', 'danger', 'Lỗi cập nhật');
        if (button) button.disabled = false;
      }
    });
  });
}

bindAgentStatusForms();
setupAgentForm();

// =============================================================================
// Bộ quản lý So Sánh Bất Động Sản (Property Comparison Manager)
// Hỗ trợ lưu tối đa 3 tin trong localStorage, hiển thị Floating Bar & đồng bộ UI
// =============================================================================
window.PropertyCompare = (function () {
  const STORAGE_KEY = 'bds_compare_properties';
  const MAX_COMPARE = 3;

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getList() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      const parsed = data ? JSON.parse(data) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function count() {
    return getList().length;
  }

  function saveList(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    } catch (e) {}
    renderUI();
  }

  function has(id) {
    return getList().some(function (p) { return Number(p.id) === Number(id); });
  }

  function add(property) {
    var list = getList();
    if (list.some(function (p) { return Number(p.id) === Number(property.id); })) {
      return true;
    }
    if (list.length >= MAX_COMPARE) {
      BDS.toast(
        'Bạn chỉ có thể so sánh tối đa ' + MAX_COMPARE + ' bất động sản cùng lúc. Vui lòng gỡ bớt tin để thêm tin mới.',
        'warning',
        'Giới hạn so sánh'
      );
      return false;
    }
    list.push(property);
    saveList(list);
    BDS.toast('Đã thêm bất động sản vào khay so sánh chi tiết.', 'info', 'So sánh bất động sản');
    return true;
  }

  function remove(id) {
    var list = getList().filter(function (p) { return Number(p.id) !== Number(id); });
    saveList(list);
  }

  function clear() {
    saveList([]);
  }

  function toggle(property) {
    if (!property || !property.id) return false;
    if (has(property.id)) {
      remove(property.id);
      BDS.toast('Đã gỡ bất động sản khỏi khay so sánh.', 'warning', 'So sánh bất động sản');
      return false;
    } else {
      return add(property);
    }
  }

  function renderUI() {
    var list = getList();
    var bar = document.getElementById('floatingCompareBar');
    var badge = document.getElementById('compareNavBadge');
    var countText = document.getElementById('compareCountText');
    var thumbsContainer = document.getElementById('compareThumbnails');
    var btnGo = document.getElementById('btnGoCompare');

    if (badge) {
      badge.textContent = list.length;
      if (list.length > 0) {
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }
    }

    if (bar) {
      if (list.length > 0) {
        bar.classList.remove('d-none');
      } else {
        bar.classList.add('d-none');
      }
    }

    if (countText) {
      countText.textContent = 'Đã chọn ' + list.length + '/' + MAX_COMPARE + ' tin';
    }

    var ids = list.map(function (p) { return (p && typeof p === 'object') ? p.id : p; }).filter(Boolean).join(',');
    var compareTargetUrl = (BASE_URL || '') + '/property/compare' + (ids ? ('?ids=' + encodeURIComponent(ids)) : '');
    if (btnGo) {
      btnGo.href = compareTargetUrl;
    }
    document.querySelectorAll('a[href$="/property/compare"], a[href*="/property/compare?ids="]').forEach(function (link) {
      link.href = compareTargetUrl;
    });

    if (thumbsContainer) {
      thumbsContainer.innerHTML = '';
      list.forEach(function (p) {
        var item = document.createElement('div');
        item.className = 'compare-thumb-item';
        item.title = p.title || '';
        var imgUrl = p.cover_url || p.avatar || '';
        item.innerHTML = (imgUrl
          ? '<img src="' + escapeHtml(imgUrl) + '" alt="' + escapeHtml(p.title) + '">'
          : '<div class="d-flex align-items-center justify-content-center h-100 text-white small">🏠</div>') +
          '<span class="compare-thumb-remove" title="Xóa khỏi so sánh" onclick="window.PropertyCompare.remove(' + Number(p.id) + ')">&times;</span>';
        thumbsContainer.appendChild(item);
      });
    }

    document.querySelectorAll('.btn-compare-card').forEach(function (btn) {
      var id = btn.dataset.compareId;
      if (id && has(id)) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        btn.title = 'Đã thêm vào so sánh (bấm để gỡ)';
      } else {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="bi bi-arrow-left-right"></i>';
        btn.title = 'Thêm vào danh sách so sánh';
      }
    });

    var detailBtn = document.getElementById('btnDetailCompare');
    if (detailBtn) {
      var detailId = detailBtn.dataset.compareId;
      if (detailId && has(detailId)) {
        detailBtn.classList.remove('btn-outline-primary');
        detailBtn.classList.add('btn-primary');
        detailBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Đã thêm so sánh';
      } else {
        detailBtn.classList.remove('btn-primary');
        detailBtn.classList.add('btn-outline-primary');
        detailBtn.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i>So sánh BĐS này';
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    renderUI();
  });

  return {
    get: getList,
    getAll: function () { return this.get(); },
    count: count,
    add: add,
    remove: remove,
    clear: clear,
    has: has,
    toggle: toggle,
    renderUI: renderUI
  };
})();

function toggleCardCompare(event, propertyId, btn, propertyData) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  if (!window.PropertyCompare) return;
  window.PropertyCompare.toggle(propertyData);
}

// =============================================================================
// Bộ quản lý Lịch sử Bất động sản vừa xem (Recently Viewed Properties)
// Lưu tối đa 6 tin mới nhất vào localStorage ('bds_recently_viewed')
// =============================================================================
window.RecentlyViewed = (function () {
  const STORAGE_KEY = 'bds_recently_viewed';
  const MAX_ITEMS = 6;

  function escapeText(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getList() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function saveList(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, MAX_ITEMS)));
    } catch (e) {}
  }

  function normalizeWardName(raw) {
    const val = String(raw ?? '').trim();
    if (!val) return '';
    const map = {
      'Quận 1': 'Phường Bến Nghé',
      'Quận 2': 'Phường Thảo Điền',
      'TP. Thủ Đức': 'Phường Thảo Điền',
      'Quận 3': 'Phường Võ Thị Sáu',
      'Quận Cầu Giấy': 'Phường Dịch Vọng',
      'Quận Hải Châu': 'Phường Hải Châu 1',
      'Quận Ba Đình': 'Phường Điện Biên',
      'Quận Bình Thạnh': 'Phường Bình Thạnh'
    };
    return map[val] || val;
  }

  function record(property) {
    if (!property || !property.id) return;
    const wardVal = normalizeWardName(property.ward_name || property.ward || '');
    const normalized = {
      id: Number(property.id),
      title: property.title || '',
      price: property.price || property.price_formatted || '',
      price_formatted: property.price_formatted || property.price || '',
      area: property.area || '',
      ward: wardVal,
      ward_name: wardVal,
      image: property.image || property.avatar_url || '',
      avatar_url: property.avatar_url || property.image || '',
      url: property.url || ((BASE_URL || '') + '/property/detail/' + Number(property.id))
    };

    const filtered = getList().filter(function (item) {
      return Number(item.id) !== normalized.id;
    });
    filtered.unshift(normalized);
    saveList(filtered.slice(0, MAX_ITEMS));
  }

  function clear() {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch (e) {}
    const section = document.getElementById('recently-viewed-section');
    if (section) {
      section.style.display = 'none';
    }
    const listEl = document.getElementById('recently-viewed-list');
    if (listEl) {
      listEl.innerHTML = '';
    }
    if (window.BDS && typeof window.BDS.toast === 'function') {
      window.BDS.toast('Đã xóa toàn bộ lịch sử bất động sản vừa xem.', 'info', 'Lịch sử xem tin');
    }
  }

  function render(currentPropertyId = 0) {
    const section = document.getElementById('recently-viewed-section');
    if (!section) return;

    const excludeId = Number(currentPropertyId || section.dataset.currentId || 0);
    const items = getList().filter(function (item) {
      return item && Number(item.id) > 0 && Number(item.id) !== excludeId;
    });

    if (items.length === 0) {
      section.style.display = 'none';
      return;
    }

    let listEl = document.getElementById('recently-viewed-list');
    if (!listEl) {
      section.innerHTML =
        '<div class="card border-0 shadow-sm rounded-4 p-4">' +
          '<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">' +
            '<h4 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Bất động sản vừa xem</h4>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="window.RecentlyViewed.clear()">' +
              '<i class="bi bi-trash3 me-1"></i>Xóa lịch sử xem' +
            '</button>' +
          '</div>' +
          '<div id="recently-viewed-list" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 g-3"></div>' +
        '</div>';
      listEl = document.getElementById('recently-viewed-list');
    }

    const fallbackImg = (BASE_URL || '') + '/uploads/sample-property-01.jpg';
    listEl.innerHTML = items.map(function (item) {
      const title = escapeText(item.title || 'Bất động sản');
      const price = escapeText(item.price || item.price_formatted || 'Liên hệ');
      const areaVal = String(item.area ?? '').trim();
      const areaText = areaVal ? escapeText(areaVal.includes('m²') ? areaVal : (areaVal + ' m²')) : '';
      const ward = escapeText(normalizeWardName(item.ward_name || item.ward || item.district_name || item.district || ''));
      const img = escapeText(item.image || item.avatar_url || fallbackImg);
      const url = escapeText(item.url || ((BASE_URL || '') + '/property/detail/' + Number(item.id)));

      return '<div class="col">' +
        '<div class="card h-100 border rounded-3 shadow-sm overflow-hidden property-card">' +
          '<a href="' + url + '" class="d-block overflow-hidden" style="height: 115px;">' +
            '<img src="' + img + '" alt="' + title + '" class="w-100 h-100" style="object-fit: cover;" loading="lazy">' +
          '</a>' +
          '<div class="card-body p-2 d-flex flex-column">' +
            '<a href="' + url + '" class="text-decoration-none text-dark fw-semibold small text-truncate mb-1" title="' + title + '">' +
              title +
            '</a>' +
            '<div class="text-danger fw-bold small mb-1">' + price + '</div>' +
            '<div class="text-muted mt-auto text-truncate" style="font-size: 0.75rem;">' +
              (areaText ? ('<span>' + areaText + '</span>') : '') +
              (areaText && ward ? ' • ' : '') +
              (ward ? ('<span><i class="bi bi-geo-alt text-muted"></i> ' + ward + '</span>') : '') +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');

    section.style.display = 'block';
  }

  return {
    get: getList,
    record: record,
    clear: clear,
    render: render
  };
})();

// Ẩn / hiện mật khẩu cho các ô input password
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-toggle-password');
  if (!btn) return;
  e.preventDefault();
  const inputGroup = btn.closest('.input-group');
  if (!inputGroup) return;
  const input = inputGroup.querySelector('input');
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
  const icon = btn.querySelector('i');
  if (icon) {
    icon.classList.toggle('bi-eye', input.type === 'password');
    icon.classList.toggle('bi-eye-slash', input.type !== 'password');
  }
});

