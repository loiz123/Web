<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm border-0 rounded-4 p-4">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-2" style="width: 56px; height: 56px;">
          <i class="bi bi-envelope-paper-fill fs-3"></i>
        </div>
        <h1 class="h4 fw-bold mb-1">Khôi phục mật khẩu</h1>
        <p class="text-muted small mb-0">Nhập địa chỉ email đã đăng ký để nhận thư hướng dẫn đặt lại mật khẩu mới (hiệu lực trong 30 phút).</p>
      </div>

      <form method="post" action="<?= e(base_url('auth/forgot')) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Địa chỉ Email</label>
          <input type="email" name="email" class="form-control" placeholder="nhapemail@domain.com" value="<?= old('email') ?>" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          <i class="bi bi-send-fill me-1"></i>Gửi yêu cầu đặt lại mật khẩu
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
