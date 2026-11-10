// [D] Quốc: thao tác duyệt tin và xử lý báo cáo bằng AJAX. Dùng hàm api() trong app.js.
          (function _callback(){
  const table = document.getElementById('mod-table');
  if (!table) return;

  const tab = table.dataset.tab;
  const alertBox = document.getElementById('mod-alert');
  const modalEl = document.getElementById('rejectModal');
  const modal = new bootstrap.Modal(modalEl);
  const reasonEl = document.getElementById('reject-reason');
  const reasonErr = document.getElementById('reject-error');
  let rejectRow = null;

  function notify(message, ok) {
    alertBox.className = 'alert alert-' +( ok ? 'success' : 'danger');
    alertBox.textContent = message;
  }

  function adjustBadge(tabKey, delta) {
    const badge = document.querySelector('[data-tab-badge="' + tabKey + '"]');
    if (!badge) return;
    const current = parseInt(badge.dataset.count || badge.textContent.replace(/[^\d]/g, ''), 10) || 0;
    const next = Math.max(0, current + delta);
    badge.dataset.count = String(next);
    badge.textContent = '(' + next + ')';
  }

  function removeRow(row, actionType) {
    if (tab === 'all' && actionType && actionType !== 'delete') {
      window.location.reload();
      return;
    }
    row.remove();
    const totalEl = document.getElementById('mod-total');
    if (totalEl) {
      const total = Math.max(0, Number(totalEl.dataset.total) - 1);
      totalEl.dataset.total = total;
      totalEl.textContent = 'Tổng: ' + total + ' mục.';
    }
    adjustBadge(tab, -1);
    if (tab !== 'all' && actionType === 'delete') adjustBadge('all', -1);
    if (actionType === 'approve') adjustBadge('approved', 1);
    if (actionType === 'reject') adjustBadge('rejected', 1);
    if (actionType === 'resolve-hide') adjustBadge('hidden', 1);
    if (!table.querySelector('tbody tr')) {
      notify('Đã xử lý hết các mục trên trang này. Tải lại để xem trang tiếp theo.', true);
    }
  }

  async function call(path, body, row, actionType) {
    row.querySelectorAll('button').forEach(function _callback2(b  ){return(b.disabled=true);});
    try {
      const res = await api(path, { method: 'POST', body });
      notify(res.message ||( res.success ? 'Thành công.' : 'Có lỗi xảy ra.'), res.success);
      if (res.success) {
        removeRow(row, actionType || (body && body.action) || '');
        return true;
      }
    } catch (err) {
      notify('Không kết nối được máy chủ. Thử lại sau.', false);
    }
    row.querySelectorAll('button').forEach(function _callback3(b   ){return(b.disabled=false);});
    return false;
  }

  table.addEventListener('click', function _callback4(ev){
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const row = btn.closest('tr');
    const id = row.dataset.id;
    const act = btn.dataset.act;

    if (tab === 'pending' || (tab === 'all' && (act === 'approve' || act === 'reject' || act === 'delete'))) {
      if (act === 'reject') {
        rejectRow = row;
        reasonEl.value = '';
        reasonErr.textContent = '';
        modal.show();
      } else if (act === 'delete') {
        if (confirm('Xóa vĩnh viễn tin #' + id + '? Không thể hoàn tác.')) {
          call('admin/properties/moderate/' + id, { action: 'delete' }, row, 'delete');
        }
      } else if (act === 'approve') {
        call('admin/properties/moderate/' + id, { action: 'approve' }, row, 'approve');
      }
    } else {
      if (act === 'dismiss') {
        call('admin/properties/report/' + id, { action: 'dismiss' }, row);
      } else if (act === 'resolve') {
        call('admin/properties/report/' + id, { action: 'resolve' }, row);
      } else if (act === 'resolve-hide') {
        if (confirm('Ẩn tin bị báo cáo khỏi trang công khai?')) {
          call('admin/properties/report/' + id, { action: 'resolve', hide: '1' }, row);
        }
      }
    }
  });

  document.getElementById('reject-confirm').addEventListener('click', async function _callback5(){
    const reason = reasonEl.value.trim();
    if (!reason) {
      reasonErr.textContent = 'Vui lòng nhập lý do từ chối.';
      return;
    }
    const row = rejectRow;
    modal.hide();
    await call('admin/properties/moderate/' + row.dataset.id, { action: 'reject', reason: reason }, row);
  });
}          )();

// [D] Quốc: trang quản lý người dùng - lọc, phân trang, khóa/mở khóa bằng AJAX.
          (function _callback6(){
  const body = document.getElementById('users-body');
  if (!body) return;

  const form = document.getElementById('users-filter');
  const alertBox = document.getElementById('users-alert');
  const pager = document.getElementById('users-pager');
  const totalEl = document.getElementById('users-total');
  const roleLabels = { admin: 'Quản trị', agent: 'Môi giới', customer: 'Khách' };
  let currentPage = 1;

  const initial = new URLSearchParams(location.search);
  ['q', 'role', 'status'].forEach(function _callback7(name){
    if (!initial.has(name)) return;
    const field = form.elements[name];
    field.value = initial.get(name);
    if (field.value !== initial.get(name)) field.value = '';
  });
  currentPage = Math.max(1, parseInt(initial.get('page'), 10) || 1);

  function notify(message, ok) {
    alertBox.className = 'alert alert-' +( ok ? 'success' : 'danger');
    alertBox.textContent = message;
  }

  function cell(text, className) {
    const td = document.createElement('td');
    td.textContent = text;
    if (className) td.className = className;
    return td;
  }

  function formatDate(value) {
    return String(value).slice(0, 10).split('-').reverse().join('/');
  }

  function renderRows(items) {
    body.replaceChildren();
    if (!items.length) {
      const tr = document.createElement('tr');
      const td = cell('Không có người dùng phù hợp.', 'text-muted');
      td.colSpan = 8;
      tr.append(td);
      body.append(tr);
      return;
    }
    items.forEach(function _callback8(u){
      const tr = document.createElement('tr');
      tr.dataset.id = u.id;

      const badge = document.createElement('span');
      badge.className = 'badge ' +( u.status === 'locked' ? 'text-bg-danger' : 'text-bg-success');
      badge.textContent = u.status === 'locked' ? 'Đã khóa' : 'Hoạt động';
      const statusTd = document.createElement('td');
      statusTd.append(badge);

      const actionTd = document.createElement('td');
      actionTd.className = 'text-end text-nowrap';
      if (u.can_toggle) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.act = u.status === 'locked' ? 'unlock' : 'lock';
        btn.className = 'btn btn-sm ' +( u.status === 'locked' ? 'btn-success' : 'btn-outline-danger');
        btn.textContent = u.status === 'locked' ? 'Mở khóa' : 'Khóa';
        actionTd.append(btn);
      } else {
        actionTd.textContent = '-';
        actionTd.title = 'Không thể khóa tài khoản admin';
      }

      tr.append(
        cell(u.id), cell(u.full_name), cell(u.email), cell(u.phone || '-'),
        cell(roleLabels[u.role] || u.role), statusTd, cell(formatDate(u.created_at)), actionTd
      );
      body.append(tr);
    });
  }

  function renderPager(total, perPage, page) {
    pager.replaceChildren();
    const pages = Math.max(1, Math.ceil(total / perPage));
    if (pages <= 1) return;
    for (let i = 1; i <= pages; i++) {
      const li = document.createElement('li');
      li.className = 'page-item' +( i === page ? ' active' : '');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'page-link';
      btn.dataset.page = i;
      btn.textContent = i;
      li.append(btn);
      pager.append(li);
    }
  }

  async function load(page) {
    currentPage = page || 1;
    const params = new URLSearchParams({
      q: form.elements['q'].value.trim(),
      role: form.elements['role'].value,
      status: form.elements['status'].value,
      page: currentPage
    });
    history.replaceState(null, '', '?' + params.toString());
    try {
      const res = await api('admin/users/data?' + params.toString());
      if (!res.success) throw new Error(res.message);
      renderRows(res.data.items);
      renderPager(res.data.total, res.data.per_page, res.data.page);
      totalEl.textContent = 'Tổng: ' + res.data.total + ' người dùng.';
    } catch (err) {
      notify('Không tải được danh sách người dùng. Thử lại sau.', false);
    }
  }

  form.addEventListener('submit', function _callback9(ev){ev.preventDefault();load(1);});
  form.elements['role'].addEventListener('change', function _callback0(){load(1);});
  form.elements['status'].addEventListener('change', function _callback1(){load(1);});

  pager.addEventListener('click', function _callback10(ev){
    const btn = ev.target.closest('button[data-page]');
    if (btn) load(parseInt(btn.dataset.page, 10));
  });

  body.addEventListener('click', async function _callback11(ev){
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const row = btn.closest('tr');
    const act = btn.dataset.act;
    if (act === 'lock' && !confirm('Khóa tài khoản #' + row.dataset.id + '? Người này sẽ bị đăng xuất ở lần thao tác tiếp theo.')) return;

    btn.disabled = true;
    try {
      const res = await api('admin/users/toggle/' + row.dataset.id, { method: 'POST', body: { action: act } });
      notify(res.message ||( res.success ? 'Thành công.' : 'Có lỗi xảy ra.'), res.success);
      await load(currentPage);
    } catch (err) {
      notify('Không kết nối được máy chủ. Thử lại sau.', false);
      btn.disabled = false;
    }
  });

  load(currentPage);
}          )();