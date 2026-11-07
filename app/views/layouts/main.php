<?php /** @var string $content */ $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="base-url" content="<?= e(rtrim(base_url(), '/')) ?>">
  <meta name="csrf" content="<?= e(csrf_token()) ?>">
  <title><?= e(($title ?? '') ? $title . ' | ' : '') ?>BĐS Việt - Cổng Thông Tin Bất Động Sản Trực Tuyến</title>

  <!-- Google Font: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 & Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Bản đồ tương tác trực quan -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">

  <!-- Main Custom CSS -->
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Modern White Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white navbar-modern sticky-top">
  <div class="container">
    <!-- Brand Logo -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url()) ?>" title="BĐS Việt - Cổng Thông Tin Bất Động Sản Trực Tuyến">
      <div class="brand-logo-icon">
        <i class="bi bi-buildings-fill"></i>
      </div>
      <div class="d-flex flex-column">
        <span class="brand-title">BĐS Việt</span>
        <span class="brand-subtitle">Cổng Thông Tin Bất Động Sản Trực Tuyến</span>
      </div>
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav Items -->
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-lg-3 gap-lg-1">
        <li class="nav-item">
          <a class="nav-link <?= empty($_GET['url']) ? 'active' : '' ?>" href="<?= e(base_url()) ?>">
            <i class="bi bi-house-door me-1 text-primary"></i> Trang chủ
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= (str_starts_with($_GET['url'] ?? '', 'property/search')) ? 'active' : '' ?>" href="<?= e(base_url('property/search')) ?>">
            <i class="bi bi-search me-1 text-danger"></i> Tìm kiếm
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= (str_starts_with($_GET['url'] ?? '', 'property/compare')) ? 'active' : '' ?>" href="<?= e(base_url('property/compare')) ?>">
            <i class="bi bi-arrow-left-right me-1 text-success"></i> So sánh
            <span class="badge rounded-pill bg-danger compare-nav-badge d-none ms-1" id="compareNavBadge">0</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= (str_starts_with($_GET['url'] ?? '', 'favorite')) ? 'active' : '' ?>" href="<?= e(base_url('favorite')) ?>">
            <i class="bi bi-heart me-1 text-danger"></i> Tin đã lưu
          </a>
        </li>
        <?php if (Auth::hasRole('agent', 'admin')): ?>
          <li class="nav-item">
            <a class="nav-link <?= (str_starts_with($_GET['url'] ?? '', 'agent')) ? 'active' : '' ?>" href="<?= e(base_url('agent')) ?>">
              <i class="bi bi-collection me-1 text-warning"></i> Tin của tôi
            </a>
          </li>
        <?php endif; ?>
        <?php if (Auth::hasRole('admin')): ?>
          <li class="nav-item">
            <a class="nav-link text-primary fw-semibold <?= (str_starts_with($_GET['url'] ?? '', 'admin')) ? 'active' : '' ?>" href="<?= e(base_url('admin')) ?>">
              <i class="bi bi-shield-check me-1"></i> Quản trị
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- User Actions & Post Button -->
      <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
        <?php if ($user): ?>
          <div class="dropdown">
            <button class="btn btn-light btn-sm rounded-pill dropdown-toggle d-flex align-items-center gap-2 px-3 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem;">
                <?= mb_substr(e($user['full_name']), 0, 1) ?>
              </div>
              <span class="fw-semibold small text-truncate" style="max-width: 140px;"><?= e($user['full_name']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
              <li class="px-3 py-2 border-bottom">
                <div class="fw-bold text-dark"><?= e($user['full_name']) ?></div>
                <div class="small text-muted"><?= e($user['email']) ?></div>
                <span class="badge bg-secondary-subtle text-secondary small mt-1"><?= ucfirst($user['role']) ?></span>
              </li>
              <li><a class="dropdown-item py-2 small" href="<?= e(base_url('profile')) ?>"><i class="bi bi-person me-2 text-muted"></i>Thông tin cá nhân</a></li>
              <li><a class="dropdown-item py-2 small" href="<?= e(base_url('appointment/my')) ?>"><i class="bi bi-calendar-check me-2 text-muted"></i>Lịch hẹn của tôi</a></li>
              <li><a class="dropdown-item py-2 small" href="<?= e(base_url('favorite')) ?>"><i class="bi bi-heart me-2 text-muted"></i>Danh sách tin đã lưu</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="post" action="<?= e(base_url('auth/logout')) ?>" class="m-0">
                  <?= csrf_field() ?>
                  <button class="dropdown-item py-2 small text-danger" type="submit">
                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                  </button>
                </form>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold" href="<?= e(base_url('auth/login')) ?>">
            <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
          </a>
          <a class="btn btn-light btn-sm px-3 rounded-pill fw-semibold border" href="<?= e(base_url('auth/register')) ?>">
            Đăng ký
          </a>
        <?php endif; ?>

        <!-- Nút Đăng tin nổi bật -->
        <a class="btn btn-post-property btn-sm px-3 rounded-pill" href="<?= e(base_url(Auth::check() ? 'agent/create' : 'auth/login')) ?>">
          <i class="bi bi-plus-circle-fill me-1"></i> Đăng tin
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- Main Container -->
<main class="container my-4 flex-grow-1">
  <?php foreach (pull_flash() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
      <div class="d-flex align-items-center">
        <?php if ($f['type'] === 'success'): ?>
          <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <?php elseif ($f['type'] === 'danger'): ?>
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <?php else: ?>
          <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <?php endif; ?>
        <div><?= e($f['message']) ?></div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<!-- Floating Bar So Sánh Bất Động Sản (Tối đa 3 tin) -->
<div id="floatingCompareBar" class="floating-compare-bar d-none">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <span class="compare-icon-badge">⚖️</span>
        <div>
          <span class="fw-bold text-white d-block" style="font-size: 0.95rem;">So sánh bất động sản</span>
          <span class="text-white-50 small" id="compareCountText">Đã chọn 0/3 tin</span>
        </div>
      </div>
      <!-- Thumbnails -->
      <div class="d-flex align-items-center gap-2" id="compareThumbnails"></div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="window.PropertyCompare && window.PropertyCompare.clear()">
        <i class="bi bi-trash3 me-1"></i>Xóa hết
      </button>
      <a href="<?= e(base_url('property/compare')) ?>" id="btnGoCompare" class="btn btn-sm btn-accent-gradient rounded-pill px-4 fw-bold shadow-sm">
        So sánh ngay <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</div>

<!-- Modern Professional Real Estate Footer -->
<footer class="footer-modern">
  <div class="container">
    <div class="row g-4">
      <!-- Cột 1: Thông tin thương hiệu -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="brand-logo-icon">
            <i class="bi bi-buildings-fill"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white fw-bold">BĐS Việt</h5>
            <span class="text-light text-opacity-75 small">Cổng Thông Tin Bất Động Sản Trực Tuyến</span>
          </div>
        </div>
        <p class="text-light text-opacity-75 small mb-3 lh-base">
          Hệ sinh thái kết nối giao dịch bất động sản minh bạch hàng đầu Việt Nam. Cung cấp bản đồ bất động sản trực quan, công cụ tính lãi suất vay mua nhà và so sánh chi tiết thông số dự án.
        </p>
        <div class="d-flex align-items-center">
          <a href="#" class="social-icon-btn" title="Facebook" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-icon-btn" title="YouTube" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
          <a href="#" class="social-icon-btn" title="Zalo" aria-label="Zalo"><i class="bi bi-chat-dots-fill"></i></a>
          <a href="#" class="social-icon-btn" title="LinkedIn" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>

      <!-- Cột 2: Danh mục Bất động sản -->
      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="text-white fw-bold">Khám phá BĐS</h6>
        <ul class="list-unstyled d-flex flex-column gap-2 small">
          <li><a href="<?= e(base_url('property/search?listing_type=sale&type_id=2')) ?>"><i class="bi bi-chevron-right me-1 small"></i>Nhà phố & Nhà riêng</a></li>
          <li><a href="<?= e(base_url('property/search?listing_type=sale&type_id=1')) ?>"><i class="bi bi-chevron-right me-1 small"></i>Căn hộ chung cư</a></li>
          <li><a href="<?= e(base_url('property/search?listing_type=sale&type_id=3')) ?>"><i class="bi bi-chevron-right me-1 small"></i>Biệt thự cao cấp</a></li>
          <li><a href="<?= e(base_url('property/search?listing_type=sale&type_id=4')) ?>"><i class="bi bi-chevron-right me-1 small"></i>Đất nền dự án</a></li>
          <li><a href="<?= e(base_url('property/search?listing_type=rent')) ?>"><i class="bi bi-chevron-right me-1 small"></i>Nhà đất cho thuê</a></li>
        </ul>
      </div>

      <!-- Cột 3: Tiện ích & Hỗ trợ -->
      <div class="col-lg-3 col-md-6 col-6">
        <h6 class="text-white fw-bold">Công cụ & Dịch vụ</h6>
        <ul class="list-unstyled d-flex flex-column gap-2 small">
          <li><a href="<?= e(base_url('property/search')) ?>"><i class="bi bi-map me-1"></i>Bản đồ bất động sản trực quan</a></li>
          <li><a href="<?= e(base_url('property/compare')) ?>"><i class="bi bi-arrow-left-right me-1"></i>So sánh chi tiết thông số dự án</a></li>
          <li><a href="<?= e(base_url('property/search')) ?>#calc"><i class="bi bi-calculator me-1"></i>Công cụ tính lãi suất vay mua nhà</a></li>
          <li><a href="<?= e(base_url('favorite')) ?>"><i class="bi bi-bookmark-heart me-1"></i>Danh sách bất động sản đã lưu</a></li>
          <li><a href="<?= e(base_url('agent/create')) ?>"><i class="bi bi-pencil-square me-1"></i>Đăng tin ký gửi nhà đất</a></li>
        </ul>
      </div>

      <!-- Cột 4: Liên hệ & Chăm sóc khách hàng -->
      <div class="col-lg-3 col-md-6">
        <h6 class="text-white fw-bold">Liên hệ & Hỗ trợ</h6>
        <div class="small text-light text-opacity-75 mb-2">
          <i class="bi bi-buildings-fill text-warning me-1"></i> BĐS Việt - Cổng Thông Tin Bất Động Sản Trực Tuyến
        </div>
        <div class="small text-light text-opacity-75 mb-2">
          <i class="bi bi-geo-alt-fill text-danger me-1"></i> Tòa nhà Tài chính, Quận 1, TP. Hồ Chí Minh
        </div>
        <div class="small text-light text-opacity-75 mb-2">
          <i class="bi bi-telephone-fill text-info me-1"></i> Hotline CSKH: 1900 6868 (8:00 - 21:00)
        </div>
        <div class="small text-light text-opacity-75">
          <i class="bi bi-envelope-fill text-success me-1"></i> Email: <a href="mailto:cskh@bdsviet.vn" class="text-light text-opacity-75 text-decoration-none">cskh@bdsviet.vn</a>
        </div>
      </div>
    </div>

    <!-- Bottom Copyright -->
    <div class="footer-bottom-bar d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div class="text-light text-opacity-75 small">
        &copy; <?= date('Y') ?> BĐS Việt - Cổng Thông Tin Bất Động Sản Trực Tuyến. Bảo lưu mọi quyền.
      </div>
      <div class="text-light text-opacity-75 small">
        Thông tin minh bạch • Định vị chính xác • Giao dịch an toàn
      </div>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/location.js')) ?>"></script>
</body>
</html>
