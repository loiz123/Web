// [A] Dropdown phụ thuộc Tỉnh -> Quận -> Phường bằng AJAX (gọi api/districts/{id}, api/wards/{id}).
// Cách dùng trong HTML:
//   <select id="province" data-target="#district" data-level="districts">...</select>
//   <select id="district" name="district_id" data-target="#ward" data-level="wards" data-selected="5"></select>
//   <select id="ward" name="ward_id" data-selected="12"></select>
// data-level: mục con cần nạp ("districts" mặc định, hoặc "wards"); data-selected: giá trị chọn sẵn (form sửa).
const BdsLocation = (() => {
  const PLACEHOLDER = { districts: 'Quận/Huyện', wards: 'Phường/Xã' };

  /** Nạp danh sách con vào select theo id cha; selected = id chọn sẵn. */
  async function load(select, level, parentId, selected) {
    if (!select) return;
    const ph = select.dataset.placeholder || PLACEHOLDER[level] || '';
    if (!parentId) { fillSelect(select, [], ph); return; }
    select.disabled = true;
    try {
      const res = await api(`api/${level}/${encodeURIComponent(parentId)}`);
      fillSelect(select, res.success ? res.data : [], ph);
      if (selected) select.value = String(selected);
    } catch (err) {
      fillSelect(select, [], ph);
    }
    select.disabled = false;
  }

  function clearChain(select) {
    const next = select.dataset.target ? document.querySelector(select.dataset.target) : null;
    if (!next) return;
    fillSelect(next, [], next.dataset.placeholder || PLACEHOLDER[select.dataset.level === 'wards' ? 'wards' : 'districts'] || '');
    clearChain(next);
  }

  function bind(select) {
    select.addEventListener('change', async () => {
      const target = document.querySelector(select.dataset.target);
      if (!target) return;
      await load(target, select.dataset.level || 'districts', select.value);
      clearChain(target);
    });
  }

  /** Chọn sẵn giá trị khi trang vừa mở (form sửa, bộ lọc giữ trạng thái). */
  async function init(select) {
    const target = document.querySelector(select.dataset.target);
    if (!target || !select.value) return;
    await load(target, select.dataset.level || 'districts', select.value, target.dataset.selected);
    if (target.dataset.target) await init(target);
  }

  document.querySelectorAll('select[data-target]').forEach(s => { bind(s); init(s); });
  return { load };
})();
