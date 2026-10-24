// [D] Quốc: thao tác duyệt tin và xử lý báo cáo bằng AJAX. Dùng hàm api() trong app.js.
(function () {
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
    alertBox.className = 'alert alert-' + (ok ? 'success' : 'danger');
    alertBox.textContent = message;
  }

  function removeRow(row) {
    row.remove();
    if (!table.querySelector('tbody tr')) {
      notify('Đã xử lý hết các mục trên trang này. Tải lại để xem trang tiếp theo.', true);
    }
  }

  async function call(path, body, row) {
    row.querySelectorAll('button').forEach(b => (b.disabled = true));
    try {
      const res = await api(path, { method: 'POST', body });
      notify(res.message || (res.success ? 'Thành công.' : 'Có lỗi xảy ra.'), res.success);
      if (res.success) {
        removeRow(row);
        return true;
      }
    } catch (err) {
      notify('Không kết nối được máy chủ. Thử lại sau.', false);
    }
    row.querySelectorAll('button').forEach(b => (b.disabled = false));
    return false;
  }

  table.addEventListener('click', function (ev) {
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const row = btn.closest('tr');
    const id = row.dataset.id;
    const act = btn.dataset.act;

    if (tab === 'pending') {
      if (act === 'reject') {
        rejectRow = row;
        reasonEl.value = '';
        reasonErr.textContent = '';
        modal.show();
      } else if (act === 'delete') {
        if (confirm('Xóa vĩnh viễn tin #' + id + '? Không thể hoàn tác.')) {
          call('admin/properties/moderate/' + id, { action: 'delete' }, row);
        }
      } else if (act === 'approve') {
        call('admin/properties/moderate/' + id, { action: 'approve' }, row);
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

  document.getElementById('reject-confirm').addEventListener('click', async function () {
    const reason = reasonEl.value.trim();
    if (!reason) {
      reasonErr.textContent = 'Vui lòng nhập lý do từ chối.';
      return;
    }
    const row = rejectRow;
    modal.hide();
    await call('admin/properties/moderate/' + row.dataset.id, { action: 'reject', reason: reason }, row);
  });
})();
