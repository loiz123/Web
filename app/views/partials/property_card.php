<?php /** @var array $p một dòng kết quả từ Property::latest()/search() */ ?>
<div class="col-sm-6 col-lg-3">
  <div class="card h-100 shadow-sm property-card position-relative">
    <?php if (!empty($p['cover_url'])): ?>
      <img src="<?= e(base_url($p['cover_url'])) ?>" class="card-img-top" alt="<?= e($p['title']) ?>" style="height: 180px; object-fit: cover;">
    <?php else: ?>
      <div class="card-img-top bg-secondary-subtle d-flex align-items-center justify-content-center text-muted" style="height: 180px;">Chưa có ảnh</div>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle shadow-sm btn-fav-card"
            style="width: 32px; height: 32px; padding: 0; line-height: 32px; z-index: 2; opacity: 0.9;"
            title="Lưu tin yêu thích"
            onclick="toggleCardFavorite(event, <?= (int)$p['id'] ?>, this)">
      🤍
    </button>
    <div class="card-body">
      <span class="badge <?= $p['listing_type'] === 'rent' ? 'text-bg-info' : 'text-bg-success' ?>"><?= $p['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?></span>
      <?php if ($p['is_verified']): ?><span class="badge text-bg-primary">Đã xác thực</span><?php endif; ?>
      <h6 class="card-title mt-2"><a class="text-decoration-none text-dark" href="<?= e(base_url('property/detail/' . $p['id'])) ?>"><?= e($p['title']) ?></a></h6>
      <p class="text-danger fw-bold mb-1"><?= e(format_price($p['price'])) ?><?= $p['listing_type'] === 'rent' ? '/tháng' : '' ?></p>
      <p class="small text-muted mb-0"><?= e($p['area']) ?> m² · <?= (int)$p['bedrooms'] ?> PN · <?= e($p['district_name']) ?></p>
    </div>
  </div>
</div>
