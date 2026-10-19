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
  select.replaceChildren(new Option(placeholder, ''));
  items.forEach(i => select.add(new Option(i.name, i.id)));
}

// Dropdown Tỉnh/Quận/Phường: xem location.js
