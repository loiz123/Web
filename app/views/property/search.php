<h1 class="h4 mb-3">Kết quả tìm kiếm</h1>
<div class="mb-4"><?php require APP . '/views/partials/search_form.php'; ?></div>
<?php if (!$properties): ?>
  <p class="text-muted">Không tìm thấy bất động sản phù hợp.</p>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($properties as $p) require APP . '/views/partials/property_card.php'; ?>
  </div>
<?php endif; ?>
