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
  var selectedDistrict = district.dataset.selected;
  var selectedWard = ward.dataset.selected;

  province.addEventListener('change', function () {
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
  var district = document.querySelector(province.dataset.target);
  if (!district) return;

  async function updateDistricts(keepSelected) {
    if (!province.value) {
      fillSelect(district, [], 'Quận/Huyện');
      return;
    }
    var response = await api(`api/districts/${province.value}`);
    fillSelect(district, response.success ? response.data : [], 'Quận/Huyện');
    if (keepSelected && district.dataset.selected) {
      district.value = district.dataset.selected;
    }
  }

  province.addEventListener('change', function () {
    district.dataset.selected = '';
    updateDistricts(false);
  });

  if (province.value) {
    updateDistricts(true);
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

  function getList() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      return data ? JSON.parse(data) : [];
    } catch (e) {
      return [];
    }
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

    if (btnGo) {
      var ids = list.map(function (p) { return p.id; }).join(',');
      btnGo.href = (BASE_URL || '') + '/property/compare?ids=' + encodeURIComponent(ids);
    }

    if (thumbsContainer) {
      thumbsContainer.innerHTML = '';
      list.forEach(function (p) {
        var item = document.createElement('div');
        item.className = 'compare-thumb-item';
        item.title = p.title || '';
        var imgUrl = p.cover_url || '';
        item.innerHTML = (imgUrl
          ? '<img src="' + imgUrl + '" alt="' + (p.title || '') + '">'
          : '<div class="d-flex align-items-center justify-content-center h-100 text-white small">🏠</div>') +
          '<span class="compare-thumb-remove" title="Xóa khỏi so sánh" onclick="window.PropertyCompare.remove(' + p.id + ')">&times;</span>';
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
