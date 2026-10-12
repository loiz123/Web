<section class="p-4 p-md-5 mb-4 bg-light rounded-3 border">
  <h1 class="h3 fw-bold mb-3">Tìm ngôi nhà phù hợp với bạn</h1>
  <?php require APP . '/views/partials/search_form.php'; ?>
</section>

<h2 class="h5 mb-3">Tin mới nhất</h2>
<?php if (!$properties): ?>
  <p class="text-muted">Chưa có tin đăng nào được duyệt.</p>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($properties as $p) require APP . '/views/partials/property_card.php'; ?>
  </div>
<?php endif; ?>
