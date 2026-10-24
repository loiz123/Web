// Tiện ích AJAX dùng chung. Server trả JSON dạng {success, message, data}.
var BASE_URL = document.querySelector('meta[name="base-url"]').content;
var CSRF = document.querySelector('meta[name="csrf"]').content;

async function api(path, { method = 'GET', body = null } = {}) {
  const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': CSRF } };
  if (body) opts.body = body instanceof FormData ? body : new URLSearchParams(body);
  const res = await fetch(`${BASE_URL}/${path}`, opts);
  return res.json();
}

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
    if (!response.success) throw new Error(response.message || 'Không thể tải danh sách địa điểm.');
    fillSelect(select, response.data, placeholder);
    if (selectedId) select.value = selectedId;
  } catch (error) {
    window.alert('Không thể tải danh sách địa điểm. Vui lòng tải lại trang và thử lại.');
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

  var imageInput = document.querySelector('#images');
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

    var preview = document.querySelector('#image-preview');
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

document.querySelectorAll('select[data-target]').forEach(function (province) {
  province.addEventListener('change', async function () {
    var district = document.querySelector(province.dataset.target);
    if (!province.value) return fillSelect(district, [], 'Quận/Huyện');
    var response = await api(`api/districts/${province.value}`);
    fillSelect(district, response.success ? response.data : [], 'Quận/Huyện');
  });
});

setupAgentForm();
