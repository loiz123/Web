<div class="row justify-content-center"><div class="col-md-5">
  <h1 class="h4 mb-3">Đăng nhập</h1>
  <form method="post" action="<?= e(base_url('auth/login')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= old('email') ?>" required></div>
    <div class="mb-3"><label class="form-label">Mật khẩu</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-primary w-100">Đăng nhập</button>
  </form>
  <p class="mt-3 small">Chưa có tài khoản? <a href="<?= e(base_url('auth/register')) ?>">Đăng ký</a></p>
</div></div>
