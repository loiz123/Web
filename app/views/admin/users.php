<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Quản lý người dùng</h1>
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin')) ?>">&larr; Bảng điều khiển</a>
</div>

<form id="users-filter" class="row g-2 mb-3">
  <div class="col-12 col-md-4">
    <input type="search" name="q" class="form-control" placeholder="Tìm theo tên hoặc email" maxlength="100">
  </div>
  <div class="col-6 col-md-3">
    <select name="role" class="form-select">
      <option value="">Tất cả vai trò</option>
      <option value="admin">Quản trị</option>
      <option value="agent">Môi giới</option>
      <option value="customer">Khách</option>
    </select>
  </div>
  <div class="col-6 col-md-3">
    <select name="status" class="form-select">
      <option value="">Tất cả trạng thái</option>
      <option value="active">Hoạt động</option>
      <option value="locked">Đã khóa</option>
    </select>
  </div>
  <div class="col-12 col-md-2 d-grid">
    <button type="submit" class="btn btn-primary">Lọc</button>
  </div>
</form>

<div id="users-alert" class="alert d-none" role="alert"></div>

<div class="table-responsive">
  <table class="table table-hover align-middle" id="users-table">
    <thead>
      <tr><th>#</th><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th class="text-end">Thao tác</th></tr>
    </thead>
    <tbody id="users-body">
      <tr><td colspan="8" class="text-muted">Đang tải...</td></tr>
    </tbody>
  </table>
</div>

<nav><ul class="pagination flex-wrap" id="users-pager"></ul></nav>
<p class="text-muted small" id="users-total"></p>

<script src="<?= e(base_url('assets/js/admin.js')) ?>" defer></script>
