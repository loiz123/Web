<?php /** @var array $p một dòng kết quả từ Property::latest()/search() */ ?>
<div class="col-sm-6 col-lg-3">
  <div class="card h-100 property-card position-relative" data-property-id="<?= (int)$p['id'] ?>">
    <!-- Ảnh bìa BĐS với hiệu ứng zoom mượt mà -->
    <div class="property-card-img-wrap">
      <a href="<?= e(base_url('property/detail/' . $p['id'])) ?>" class="d-block h-100 w-100">
        <?php if (!empty($p['cover_url'])): ?>
          <img src="<?= e(image_url($p['cover_url'])) ?>" class="card-img-top" alt="<?= e($p['title']) ?>" loading="lazy">
        <?php else: ?>
          <div class="card-img-top bg-secondary-subtle d-flex flex-column align-items-center justify-content-center text-muted h-100">
            <i class="bi bi-image fs-3 mb-1"></i>
            <span class="small">Chưa có ảnh</span>
          </div>
        <?php endif; ?>
      </a>

      <!-- Nút So sánh BĐS (Góc trên bên trái) -->
      <button type="button" class="btn btn-sm btn-light position-absolute top-0 start-0 m-2 rounded-circle btn-compare-card"
              title="Thêm vào danh sách so sánh"
              data-compare-id="<?= (int)$p['id'] ?>"
              onclick="toggleCardCompare(event, <?= (int)$p['id'] ?>, this, <?= e(json_encode([
                  'id'              => (int)$p['id'],
                  'title'           => $p['title'],
                  'price'           => format_price($p['price']) . ($p['listing_type'] === 'rent' ? '/tháng' : ''),
                  'raw_price'       => (float)$p['price'],
                  'area'            => $p['area'] . ' m²',
                  'raw_area'        => (float)$p['area'],
                  'bedrooms'        => (int)$p['bedrooms'],
                  'district_name'   => $p['district_name'] ?? '',
                  'listing_type'    => $p['listing_type'],
                  'cover_url'       => !empty($p['cover_url']) ? image_url($p['cover_url']) : '',
                  'detail_url'      => base_url('property/detail/' . $p['id'])
              ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)) ?>)">
        <i class="bi bi-arrow-left-right"></i>
      </button>

      <?php $isFav = !empty($p['is_favorite']) || !empty($isFavoritePage); ?>
      <!-- Nút Lưu tin yêu thích (Góc trên bên phải) -->
      <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle btn-fav-card"
              title="<?= $isFav ? 'Bỏ lưu tin' : 'Lưu tin yêu thích' ?>"
              onclick="toggleCardFavorite(event, <?= (int)$p['id'] ?>, this)">
        <?= $isFav ? '❤️' : '🤍' ?>
      </button>
    </div>

    <!-- Nội dung Card -->
    <div class="card-body d-flex flex-column">
      <!-- Badges -->
      <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
        <span class="badge <?= $p['listing_type'] === 'rent' ? 'badge-rent' : 'badge-sale' ?>">
          <?= $p['listing_type'] === 'rent' ? 'Cho thuê' : 'Bán' ?>
        </span>
        <?php if (!empty($p['type_name'])): ?>
          <span class="badge badge-type"><?= e($p['type_name']) ?></span>
        <?php endif; ?>
        <?php if (!empty($p['is_verified'])): ?>
          <span class="badge badge-verified">
            <i class="bi bi-patch-check-fill me-1"></i>Xác thực
          </span>
        <?php endif; ?>
      </div>

      <!-- Tiêu đề BĐS -->
      <h6 class="card-title">
        <a class="text-decoration-none" href="<?= e(base_url('property/detail/' . $p['id'])) ?>" title="<?= e($p['title']) ?>">
          <?= e($p['title']) ?>
        </a>
      </h6>

      <!-- Mức giá & Đơn vị tính -->
      <div class="d-flex align-items-baseline gap-2 mb-2 mt-auto">
        <span class="property-price">
          <?= e(format_price($p['price'])) ?><?= $p['listing_type'] === 'rent' ? '<small class="fs-6 fw-normal text-muted">/tháng</small>' : '' ?>
        </span>
        <?php if ($p['listing_type'] === 'sale' && !empty($p['area']) && (float)$p['area'] > 0): ?>
          <span class="property-unit-price">
            ~<?= number_format($p['price'] / $p['area'] / 1e6, 1) ?> tr/m²
          </span>
        <?php endif; ?>
      </div>

      <!-- Thông số Diện tích, Phòng ngủ, Khu vực -->
      <div class="property-specs pt-2 border-top">
        <span><i class="bi bi-aspect-ratio text-muted"></i> <?= e($p['area']) ?> m²</span>
        <span>•</span>
        <span><i class="bi bi-door-closed text-muted"></i> <?= (int)$p['bedrooms'] ?> PN</span>
        <span>•</span>
        <span class="text-truncate" style="max-width: 105px;" title="<?= e($p['district_name'] ?? '') ?>">
          <i class="bi bi-geo-alt text-muted"></i> <?= e($p['district_name'] ?? '') ?>
        </span>
      </div>
    </div>
  </div>
</div>
