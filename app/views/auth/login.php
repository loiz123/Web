<div class="row justify-content-center"><div class="col-md-5">
  <div class="card shadow-sm border-0 rounded-4 p-4">
    <h1 class="h4 fw-bold mb-3">Đăng nhập</h1>
    <form method="post" action="<?= e(base_url('auth/login')) ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= old('email') ?>" required></div>
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <label class="form-label mb-0">Mật khẩu</label>
          <a href="<?= e(base_url('auth/forgot')) ?>" class="small text-decoration-none">Quên mật khẩu?</a>
        </div>
        <div class="input-group">
          <input type="password" name="password" class="form-control" required>
          <button class="btn btn-outline-secondary btn-toggle-password" type="button" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button class="btn btn-primary w-100 fw-semibold">Đăng nhập</button>
    </form>
    <p class="mt-3 mb-0 small text-center">Chưa có tài khoản? <a href="<?= e(base_url('auth/register')) ?>" class="fw-semibold text-decoration-none">Đăng ký ngay</a></p>
  </div>
</div></div>
