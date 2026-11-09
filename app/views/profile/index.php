<?php
/** @var array $profile @var string $role */
$roleLabel = ['admin' => 'Quản trị viên', 'agent' => 'Môi giới bất động sản', 'customer' => 'Khách hàng'][$role] ?? $role;
$avatarUrl = !empty($profile['avatar']) ? base_url($profile['avatar']) : '';
?>
<h1 class="h4 fw-bold mb-3">Hồ sơ cá nhân</h1>
<div class="row g-4">
  <div class="col-md-7">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-vcard text-primary me-2"></i>Thông tin tài khoản & Giới thiệu</h2>
        <form method="post" action="<?= e(base_url('profile/update')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <!-- Khu vực Ảnh đại diện (Avatar) & Preview -->
          <div class="d-flex flex-column flex-sm-row align-items-center gap-3 p-3 rounded-3 bg-light border mb-3">
            <div class="position-relative flex-shrink-0">
              <img id="avatarPreviewImg"
                   src="<?= e($avatarUrl) ?>"
                   alt="<?= e($profile['full_name']) ?>"
                   class="rounded-circle border shadow-xs object-fit-cover <?= $avatarUrl !== '' ? '' : 'd-none' ?>"
                   style="width: 84px; height: 84px;">
              <div id="avatarFallbackCircle"
                   class="bg-primary text-white rounded-circle align-items-center justify-content-center fw-bold fs-3 border shadow-xs <?= $avatarUrl !== '' ? 'd-none' : 'd-inline-flex' ?>"
                   style="width: 84px; height: 84px;">
                <?= mb_substr(e($profile['full_name'] ?: 'U'), 0, 1) ?>
              </div>
            </div>
            <div class="flex-grow-1 w-100">
              <label for="avatarInput" class="form-label fw-semibold small mb-1">Ảnh đại diện (Avatar)</label>
              <input type="file"
                     id="avatarInput"
                     name="avatar"
                     class="form-control form-control-sm"
                     accept="image/jpeg,image/png,image/webp">
              <div class="form-text small text-muted mt-1">
                Hỗ trợ JPG, PNG, WEBP (tối đa 5MB). Ảnh sẽ tự động được chuẩn hóa kích thước.
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Email đăng nhập</label>
              <input class="form-control bg-light" value="<?= e($profile['email']) ?>" disabled>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Vai trò tài khoản</label>
              <input class="form-control bg-light" value="<?= e($roleLabel) ?>" disabled>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
              <input name="full_name" class="form-control" maxlength="100" value="<?= e($profile['full_name']) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Số điện thoại liên hệ</label>
              <input name="phone" class="form-control" maxlength="15" value="<?= e($profile['phone'] ?? '') ?>" placeholder="0901234567">
            </div>
          </div>

          <div class="mb-3">
            <label for="bioInput" class="form-label small fw-semibold">Tiểu sử / Giới thiệu bản thân (Bio)</label>
            <textarea id="bioInput"
                      name="bio"
                      class="form-control"
                      rows="4"
                      maxlength="1000"
                      placeholder="Giới thiệu kinh nghiệm tư vấn, khu vực chuyên môn hoặc phương châm làm việc của bạn..."><?= e($profile['bio'] ?? '') ?></textarea>
            <div class="form-text small text-muted">Hiển thị trên trang chi tiết tin đăng để tăng độ tin cậy với khách hàng (tối đa 1000 ký tự).</div>
          </div>

          <button type="submit" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-check2-circle me-1"></i>Lưu thay đổi hồ sơ
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Đổi mật khẩu bảo mật</h2>
        <p class="text-muted small mb-3">Sau khi đổi mật khẩu, các phiên đăng nhập cũ trên những thiết bị khác sẽ tự động được đăng xuất để bảo vệ tài khoản.</p>
        <form method="post" action="<?= e(base_url('profile/password')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mật khẩu hiện tại</label>
            <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mật khẩu mới (6–72 ký tự)</label>
            <input type="password" name="new_password" class="form-control" autocomplete="new-password" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nhập lại mật khẩu mới</label>
            <input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password" required>
          </div>
          <button type="submit" class="btn btn-outline-primary fw-semibold w-100">
            <i class="bi bi-key me-1"></i>Cập nhật mật khẩu
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var avatarInput = document.getElementById('avatarInput');
  var previewImg = document.getElementById('avatarPreviewImg');
  var fallbackCircle = document.getElementById('avatarFallbackCircle');

  if (avatarInput && previewImg) {
    avatarInput.addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0];
      if (!file) return;
      if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
        alert('Vui lòng chọn tệp hình ảnh định dạng JPG, PNG hoặc WEBP.');
        avatarInput.value = '';
        return;
      }
      var reader = new FileReader();
      reader.onload = function (ev) {
        previewImg.src = ev.target.result;
        previewImg.classList.remove('d-none');
        if (fallbackCircle) {
          fallbackCircle.classList.remove('d-inline-flex');
          fallbackCircle.classList.add('d-none');
        }
      };
      reader.readAsDataURL(file);
    });
  }
});
</script>
