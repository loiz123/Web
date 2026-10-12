<?php /** @var string $content */ $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="base-url" content="<?= e(rtrim(base_url(), '/')) ?>">
  <meta name="csrf" content="<?= e(csrf_token()) ?>">
  <title><?= e(($title ?? '') ? $title . ' | ' : '') . e(config('app_name')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e(base_url()) ?>"><?= e(config('app_name')) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('property/search')) ?>">Tìm kiếm</a></li>
        <?php if (Auth::hasRole('agent', 'admin')): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('agent')) ?>">Tin của tôi</a></li>
        <?php endif; ?>
        <?php if (Auth::hasRole('admin')): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('admin')) ?>">Quản trị</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($user): ?>
          <li class="nav-item"><span class="navbar-text me-3">Xin chào, <?= e($user['full_name']) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('auth/logout')) ?>">Đăng xuất</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('auth/login')) ?>">Đăng nhập</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('auth/register')) ?>">Đăng ký</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4 flex-grow-1">
  <?php foreach (pull_flash() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<footer class="bg-light border-top py-3 text-center text-muted small">&copy; <?= date('Y') ?> <?= e(config('app_name')) ?> - Đồ án môn Lập trình web</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
