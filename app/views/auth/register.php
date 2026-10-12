<div class="row justify-content-center"><div class="col-md-6">
  <h1 class="h4 mb-3">Đăng ký</h1>
  <form method="post" action="<?= e(base_url('auth/register')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Họ tên</label><input name="full_name" class="form-control" value="<?= old('full_name') ?>" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= old('email') ?>" required></div>
    <div class="mb-3"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control" value="<?= old('phone') ?>"></div>
    <div class="mb-3"><label class="form-label">Mật khẩu (tối thiểu 6 ký tự)</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3">
      <label class="form-label d-block">Bạn là</label>
      <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="role" value="customer" id="r1" checked><label class="form-check-label" for="r1">Khách hàng</label></div>
      <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="role" value="agent" id="r2"><label class="form-check-label" for="r2">Môi giới</label></div>
    </div>
    <button class="btn btn-primary w-100">Tạo tài khoản</button>
  </form>
</div></div>
