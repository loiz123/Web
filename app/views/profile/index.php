<?php
/** @var array $profile @var string $role */
$roleLabel = ['admin' => 'Quản trị viên', 'agent' => 'Môi giới', 'customer' => 'Khách hàng'][$role] ?? $role;
?>
<h1 class="h4 mb-3">Hồ sơ cá nhân</h1>
<div class="row g-4">
  <div class="col-md-6">
    <div class="card shadow-sm"><div class="card-body">
      <h2 class="h6 fw-bold mb-3">Thông tin tài khoản</h2>
      <form method="post" action="<?= e(base_url('profile/update')) ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?= e($profile['email']) ?>" disabled></div>
        <div class="mb-3"><label class="form-label">Vai trò</label><input class="form-control" value="<?= e($roleLabel) ?>" disabled></div>
        <div class="mb-3"><label class="form-label">Họ tên</label><input name="full_name" class="form-control" maxlength="100" value="<?= e($profile['full_name']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control" maxlength="15" value="<?= e($profile['phone'] ?? '') ?>" placeholder="0901234567"></div>
        <button class="btn btn-primary">Lưu thay đổi</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm"><div class="card-body">
      <h2 class="h6 fw-bold mb-3">Đổi mật khẩu</h2>
      <form method="post" action="<?= e(base_url('profile/password')) ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Mật khẩu hiện tại</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
        <div class="mb-3"><label class="form-label">Mật khẩu mới (6–72 ký tự)</label><input type="password" name="new_password" class="form-control" autocomplete="new-password" required></div>
        <div class="mb-3"><label class="form-label">Nhập lại mật khẩu mới</label><input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password" required></div>
        <button class="btn btn-outline-primary">Đổi mật khẩu</button>
      </form>
    </div></div>
  </div>
</div>
