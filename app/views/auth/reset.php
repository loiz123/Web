<?php /** @var string $token @var string $email */ ?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm border-0 rounded-4 p-4">
      <div class="text-center mb-3">
        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-2" style="width: 56px; height: 56px;">
          <i class="bi bi-shield-lock-fill fs-3"></i>
        </div>
        <h1 class="h4 fw-bold mb-1">Đặt lại mật khẩu mới</h1>
        <p class="text-muted small mb-0">Thiết lập mật khẩu mới cho tài khoản <strong><?= e($email ?? '') ?></strong></p>
      </div>

      <form method="post" action="<?= e(base_url('auth/reset?token=' . urlencode($token))) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="mb-3">
          <label class="form-label fw-semibold small">Mật khẩu mới (6–72 ký tự)</label>
          <input type="password" name="password" class="form-control" minlength="6" maxlength="72" autocomplete="new-password" placeholder="Nhập mật khẩu mới..." required autofocus>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold small">Xác nhận mật khẩu mới</label>
          <input type="password" name="password_confirm" class="form-control" minlength="6" maxlength="72" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới..." required>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          <i class="bi bi-check2-circle me-1"></i>Lưu mật khẩu mới
        </button>
      </form>

      <div class="text-center mt-3 pt-2 border-top">
        <a href="<?= e(base_url('auth/login')) ?>" class="small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i>Quay lại trang Đăng nhập
        </a>
      </div>
    </div>
  </div>
</div>
