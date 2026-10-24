<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-2">
  <div>
    <h1 class="h3 fw-bold mb-1">❤️ Bất động sản đã lưu</h1>
    <p class="text-muted small mb-0">Danh sách các tin đăng bạn quan tâm và lưu lại để tiện theo dõi.</p>
  </div>
  <a href="<?= e(base_url('property/search')) ?>" class="btn btn-outline-primary btn-sm">
    🔍 Tìm kiếm thêm tin khác
  </a>
</div>

<?php if (empty($properties)): ?>
  <div class="card shadow-sm border-0 py-5 text-center my-4">
    <div class="card-body">
      <div class="display-3 mb-3 text-muted">📑</div>
      <h4 class="fw-bold text-secondary">Bạn chưa lưu tin bất động sản nào</h4>
      <p class="text-muted mx-auto" style="max-width: 480px;">
        Khi xem tin đăng trên hệ thống, hãy nhấn vào biểu tượng <strong>❤️ Lưu tin</strong> để dễ dàng xem lại bất cứ lúc nào tại đây.
      </p>
      <a href="<?= e(base_url('property/search')) ?>" class="btn btn-primary px-4 py-2 mt-2">
        Khám phá bất động sản ngay
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($properties as $p): ?>
      <?php require APP . '/views/partials/property_card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
