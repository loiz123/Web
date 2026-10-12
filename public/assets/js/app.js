// Tiện ích AJAX dùng chung. Server trả JSON dạng {success, message, data}.
const BASE_URL = document.querySelector('meta[name="base-url"]').content;
const CSRF = document.querySelector('meta[name="csrf"]').content;

async function api(path, { method = 'GET', body = null } = {}) {
  const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': CSRF } };
  if (body) opts.body = body instanceof FormData ? body : new URLSearchParams(body);
  const res = await fetch(`${BASE_URL}/${path}`, opts);
  return res.json();
}

function fillSelect(select, items, placeholder) {
  select.innerHTML = `<option value="">${placeholder}</option>`;
  items.forEach(i => select.add(new Option(i.name, i.id)));
}

// Mẫu AJAX: chọn Tỉnh/Thành -> nạp Quận/Huyện (gọi api/districts/{id})
document.querySelectorAll('select[data-target]').forEach(province => {
  province.addEventListener('change', async () => {
    const district = document.querySelector(province.dataset.target);
    if (!province.value) return fillSelect(district, [], 'Quận/Huyện');
    const res = await api(`api/districts/${province.value}`);
    fillSelect(district, res.success ? res.data : [], 'Quận/Huyện');
  });
});
