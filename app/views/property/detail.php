<!-- Leaflet 1.9 CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
  #propertyMap { height: 360px; width: 100%; border-radius: 8px; z-index: 1; }
  .gallery-main { height: 420px; object-fit: cover; border-radius: 8px; width: 100%; }
  .gallery-thumb { height: 80px; object-fit: cover; border-radius: 6px; cursor: pointer; transition: opacity .2s; }
  .gallery-thumb:hover, .gallery-thumb.active { opacity: .75; border: 2px solid #0d6efd; }
  .gallery-open { cursor: zoom-in; }
  .gallery-modal-img { height: min(70vh, 650px); object-fit: contain; width: 100%; }
  @media (max-width: 991.98px) {
    .property-detail-page { padding-bottom: 85px; }
    .property-mobile-actions { padding: 8px 12px calc(8px + env(safe-area-inset-bottom)); }
  }
  .property-spec-item { padding: 12px; background-color: #f8f9fa; border-radius: 8px; }
</style>

<div class="container py-2 property-detail-page">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="<?= e(base_url('property/search')) ?>">Tìm kiếm</a></li>
      <li class="breadcrumb-item"><a href="<?= e(base_url('property/search?district_id=' . $property['district_id'])) ?>"><?= e($property['district_name']) ?></a></li>
      <li class="breadcrumb-item active text-truncate" style="max-width: 320px;" aria-current="page"><?= e($property['title']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- Cột trái: Thông tin chính, gallery, tiện ích, bản đồ -->
    <div class="col-lg-8">
      <!-- Tiêu đề & Tag -->
      <div class="mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
          <span class="badge text-bg-<?= $property['listing_type'] === 'sale' ? 'success' : 'info' ?> fs-6">
            <?= $property['listing_type'] === 'sale' ? 'Bán' : 'Cho thuê' ?>
          </span>
          <span class="badge text-bg-primary fs-6"><?= e($property['type_name']) ?></span>
          <?php if (!empty($property['is_verified'])): ?>
            <span class="badge text-bg-warning text-dark fs-6">✓ Đã xác thực</span>
          <?php endif; ?>
          <span class="badge text-bg-light border text-muted">👁 <?= e($property['views']) ?> lượt xem</span>
          <span class="badge text-bg-light border text-muted">Mã tin: #<?= (int)$property['id'] ?></span>
          <span class="badge text-bg-light border text-muted">Cập nhật: <?= date('d/m/Y', strtotime($property['updated_at'] ?: $property['created_at'])) ?></span>
        </div>
        <h1 class="h3 fw-bold mb-2"><?= e($property['title']) ?></h1>
        <p class="text-muted mb-0">
          📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['district_name']) ?>, <?= e($property['province_name']) ?>
        </p>
        <div class="mt-2">
          <?php if (Auth::check()): ?>
            <?php if (!empty($hasReported)): ?>
              <button class="btn btn-outline-warning btn-sm w-100" disabled>
                🚩 Bạn đã báo cáo tin đăng này
              </button>
            <?php elseif ((int)Auth::id() !== (int)$property['user_id']): ?>
              <button class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#reportModal">
                🚩 Báo cáo tin đăng vi phạm
              </button>
            <?php endif; ?>
          <?php else: ?>
            <a href="<?= e(base_url('auth/login')) ?>" class="btn btn-outline-danger btn-sm w-100">
              🚩 Đăng nhập để báo cáo vi phạm
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Mức giá & Diện tích nổi bật -->
      <div class="p-3 bg-light rounded-3 border mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <span class="text-muted small d-block">Mức giá:</span>
          <span class="text-danger fw-bold fs-3">
            <?= format_price($property['price']) ?><?= $property['listing_type'] === 'rent' ? '/tháng' : '' ?>
          </span>
          <?php if ($property['listing_type'] === 'rent' && !empty($property['deposit'])): ?>
            <span class="text-muted small d-block">Tiền cọc: <?= format_price($property['deposit']) ?></span>
          <?php endif; ?>
        </div>
        <div class="text-md-end">
          <span class="text-muted small d-block">Diện tích:</span>
          <span class="fw-bold fs-4 text-dark"><?= e($property['area']) ?> m²</span>
          <?php if ($property['area'] > 0 && $property['listing_type'] === 'sale'): ?>
            <span class="text-muted small d-block">~<?= number_format($property['price'] / $property['area'] / 1e6, 1) ?> triệu/m²</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Gallery hình ảnh -->
      <div class="mb-4">
        <?php if (!empty($images)): ?>
          <div class="mb-2">
            <button type="button" class="border-0 bg-transparent p-0 w-100 gallery-open" data-bs-toggle="modal" data-bs-target="#galleryModal" aria-label="Xem ảnh lớn">
              <img id="mainGalleryImg" src="<?= e(base_url($images[0]['image_url'])) ?>" alt="<?= e($property['title']) ?>" class="gallery-main shadow-sm border">
            </button>
          </div>
          <?php if (count($images) > 1): ?>
            <div class="d-flex gap-2 overflow-auto pb-2">
              <?php foreach ($images as $idx => $img): ?>
                <img src="<?= e(base_url($img['image_url'])) ?>" data-gallery-url="<?= e(base_url($img['image_url'])) ?>" alt="Ảnh <?= $idx + 1 ?>" class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="gallery-main bg-secondary-subtle d-flex flex-column align-items-center justify-content-center text-muted border rounded-3">
            <span class="fs-1 mb-2">🏠</span>
            <span>Chưa có hình ảnh đăng tải</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Đặc điểm bất động sản -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-3 border-bottom pb-2">Đặc điểm bất động sản</h5>
          <div class="row g-3">
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Phòng ngủ</span>
                <span class="fw-bold"><?= (int)$property['bedrooms'] > 0 ? (int)$property['bedrooms'] . ' phòng' : 'Chưa cập nhật' ?></span>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Phòng tắm / WC</span>
                <span class="fw-bold"><?= (int)$property['bathrooms'] > 0 ? (int)$property['bathrooms'] . ' phòng' : 'Chưa cập nhật' ?></span>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Hướng nhà</span>
                <span class="fw-bold"><?= e($property['direction'] ?: 'Không xác định') ?></span>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="property-spec-item">
                <span class="text-muted small d-block">Pháp lý</span>
                <span class="fw-bold"><?= e($property['legal_status'] ?: 'Đang chờ sổ / Khác') ?></span>
              </div>
            </div>
            <?php if ($property['listing_type'] === 'rent'): ?>
              <div class="col-6 col-md-4">
                <div class="property-spec-item">
                  <span class="text-muted small d-block">Tình trạng nội thất</span>
                  <span class="fw-bold">
                    <?php
                      switch ($property['furniture']) {
                        case 'full': echo 'Đầy đủ (Full)'; break;
                        case 'basic': echo 'Cơ bản'; break;
                        default: echo 'Không có (Trống)'; break;
                      }
                    ?>
                  </span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="property-spec-item">
                  <span class="text-muted small d-block">Nuôi thú cưng</span>
                  <span class="fw-bold"><?= !empty($property['pets_allowed']) ? '✓ Cho phép' : '✕ Không cho phép' ?></span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Mô tả chi tiết -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-3 border-bottom pb-2">Thông tin mô tả</h5>
          <div class="lh-lg text-secondary">
            <?= nl2br(e($property['description'] ?: 'Chưa có thông tin mô tả chi tiết cho bất động sản này.')) ?>
          </div>
        </div>
      </div>

      <!-- Danh sách tiện ích -->
      <?php if (!empty($amenities)): ?>
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-body">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Tiện ích đi kèm</h5>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($amenities as $am): ?>
                <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fs-6">
                  <span class="text-success me-1">✓</span> <?= e($am['name']) ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Vị trí trên bản đồ Leaflet OpenStreetMap -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold mb-0">Vị trí trên bản đồ</h5>
            <span class="text-muted small">OpenStreetMap</span>
          </div>
          <?php if (empty($property['is_coord_fallback'])): ?>
            <div id="propertyMap" class="border"></div>
          <?php else: ?>
            <div class="alert alert-warning mb-0">Tin này chưa có tọa độ được xác nhận, nên chưa thể hiển thị vị trí trên bản đồ.</div>
          <?php endif; ?>
          <p class="small text-muted mt-2 mb-0">
            📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['district_name']) ?>
          </p>
        </div>
      </div>

      <!-- Đánh giá & Nhận xét [C - Thiện] -->
      <div class="card shadow-sm border-0 mb-4" id="reviewsSection">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold mb-0">Đánh giá & Nhận xét</h5>
            <div class="text-end">
              <?php if (!empty($reviewStats['total_reviews'])): ?>
                <span class="fs-5 fw-bold text-warning">★ <?= number_format((float)$reviewStats['avg_rating'], 1) ?></span>
                <span class="text-muted small">/ 5 (<?= (int)$reviewStats['total_reviews'] ?> đánh giá)</span>
              <?php else: ?>
                <span class="text-muted small">Chưa có đánh giá</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Form gửi / cập nhật đánh giá -->
          <?php if (Auth::check()): ?>
            <?php if ((int)Auth::id() === (int)$property['user_id']): ?>
              <div class="alert alert-light border small text-muted mb-4">
                <i class="bi bi-info-circle me-1"></i> Bạn là người đăng tin bất động sản này nên không thể tự đánh giá.
              </div>
            <?php elseif ($userReview): ?>
              <div class="alert alert-light border">Bạn đã đánh giá tin này.</div>
            <?php else: ?>
              <div class="bg-light p-3 rounded mb-4 border">
                <h6 class="fw-bold mb-2">Để lại đánh giá của bạn</h6>
                <form method="post" action="<?= e(base_url('review/submit')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                  <div class="mb-2">
                    <label class="form-label small fw-semibold me-2">Số sao:</label>
                    <div class="d-inline-flex gap-3">
                      <?php for ($s = 5; $s >= 1; $s--): ?>
                        <div class="form-check form-check-inline m-0">
                          <input class="form-check-input" type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>" <?= ($userReview && (int)$userReview['rating'] === $s) || (!$userReview && $s === 5) ? 'checked' : '' ?> required>
                          <label class="form-check-label text-warning fw-bold" for="star<?= $s ?>"><?= $s ?> ★</label>
                        </div>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <div class="mb-2">
                    <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Chia sẻ cảm nhận của bạn về bất động sản này, vị trí, giá cả..."><?= e($userReview['comment'] ?? '') ?></textarea>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                      Gửi đánh giá
                    </button>
                    <?php if ($userReview): ?>
                      <button type="submit" formaction="<?= e(base_url('review/delete/' . (int)$userReview['id'])) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                        Xóa đánh giá
                      </button>
                    <?php endif; ?>
                  </div>
                </form>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-light border text-center p-3 mb-4">
              <p class="small text-muted mb-2">Vui lòng đăng nhập để gửi đánh giá và nhận xét về bất động sản này.</p>
              <a href="<?= e(base_url('auth/login')) ?>" class="btn btn-sm btn-outline-primary fw-semibold">Đăng nhập ngay</a>
            </div>
          <?php endif; ?>

          <!-- Danh sách nhận xét -->
          <?php if (empty($reviews)): ?>
            <p class="text-muted small text-center my-3">Chưa có đánh giá nào cho bất động sản này. Hãy là người đầu tiên để lại nhận xét!</p>
          <?php else: ?>
            <div class="review-list d-flex flex-column gap-3">
              <?php foreach ($reviews as $rev): ?>
                <div class="border-bottom pb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                      <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold small" style="width: 32px; height: 32px;">
                        <?= mb_substr(e($rev['user_name'] ?: 'U'), 0, 1) ?>
                      </div>
                      <span class="fw-semibold text-dark small"><?= e($rev['user_name']) ?></span>
                    </div>
                    <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></span>
                  </div>
                  <div class="text-warning small mb-1">
                    <?= str_repeat('★', (int)$rev['rating']) ?><?= str_repeat('☆', 5 - (int)$rev['rating']) ?>
                  </div>
                  <?php if (!empty($rev['comment'])): ?>
                    <p class="text-secondary small mb-0"><?= nl2br(e($rev['comment'])) ?></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Cột phải: Thông tin người đăng / môi giới & hành động -->
    <div class="col-lg-4">
      <div class="position-sticky" style="top: 20px;">
        <!-- Card Môi giới -->
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body text-center p-4">
            <div class="mb-3">
              <?php if (!empty($property['agent_avatar'])): ?>
                <img src="<?= e(base_url($property['agent_avatar'])) ?>" alt="<?= e($property['agent_name']) ?>" class="rounded-circle border" width="80" height="80">
              <?php else: ?>
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-3 border" style="width: 80px; height: 80px;">
                  <?= mb_substr(e($property['agent_name'] ?: 'U'), 0, 1) ?>
                </div>
              <?php endif; ?>
            </div>
            <h5 class="fw-bold mb-1"><?= e($property['agent_name'] ?: 'Môi giới BĐS') ?></h5>
            <p class="text-muted small mb-3">Đại diện đăng tin bất động sản</p>

            <div class="d-grid gap-2">
              <?php if (!empty($property['agent_phone'])): ?>
                <a href="tel:<?= e($property['agent_phone']) ?>" class="btn btn-success fw-bold py-2">
                  📞 Gọi <?= e($property['agent_phone']) ?>
                </a>
              <?php else: ?>
                <button class="btn btn-secondary disabled py-2">Chưa cập nhật SĐT</button>
              <?php endif; ?>

              <?php if (!empty($property['agent_email'])): ?>
                <a href="mailto:<?= e($property['agent_email']) ?>?subject=Quan tâm tin đăng: <?= rawurlencode($property['title']) ?>" class="btn btn-outline-primary py-2">
                  ✉️ Gửi Email liên hệ
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Card Hẹn xem nhà & Tương tác [D - Quốc] -->
        <div class="card shadow-sm border-0 mb-3" id="appointmentSection">
          <div class="card-body p-3">
            <h6 class="fw-bold mb-2">Đặt lịch xem nhà</h6>
            <p class="small text-muted mb-3">Chọn thời gian thuận tiện nhất, môi giới sẽ xác nhận và đón bạn đến xem bất động sản.</p>
            <?php if (Auth::check()): ?>
              <?php if ((int)Auth::id() === (int)$property['user_id']): ?>
                <div class="alert alert-secondary small mb-0 py-2">
                  Đây là tin đăng của bạn. Bạn có thể xem danh sách lịch hẹn khách đặt trong trang quản lý.
                </div>
              <?php else: ?>
                <form method="post" action="<?= e(base_url('appointment/book')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                  <div class="mb-2">
                    <label class="form-label small fw-semibold">Thời gian muốn xem:</label>
                    <input type="datetime-local" name="appointment_date" class="form-control form-control-sm" required min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Ghi chú cho môi giới:</label>
                    <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Ví dụ: Tôi có thể xem vào buổi chiều, gọi trước 15 phút..."></textarea>
                  </div>
                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Xác nhận đặt lịch</button>
                  </div>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <div class="alert alert-light border small text-center p-2 mb-0">
                <p class="text-muted mb-2">Vui lòng đăng nhập để đặt lịch hẹn xem nhà trực tiếp.</p>
                <a href="<?= e(base_url('auth/login')) ?>" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Đăng nhập đặt lịch</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Chia sẻ, Yêu thích & Báo cáo vi phạm -->
        <div class="d-flex flex-column gap-2">
          <div class="d-flex gap-2">
            <button id="btnFavorite" class="btn <?= !empty($isFavorited) ? 'btn-danger' : 'btn-outline-danger' ?> flex-grow-1" onclick="handleToggleFavorite(<?= (int)$property['id'] ?>)">
              <span id="favIcon"><?= !empty($isFavorited) ? '❤️' : '🤍' ?></span>
              <span id="favText"><?= !empty($isFavorited) ? 'Đã lưu tin' : 'Lưu tin' ?></span>
            </button>
            <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link tin đăng!');">
              🔗 Chia sẻ
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Tin đăng tương tự -->
  <?php if (!empty($similar)): ?>
    <div class="mt-5 pt-3 border-top">
      <h4 class="fw-bold mb-3">Bất động sản cùng phân khúc</h4>
      <div class="row g-3">
        <?php foreach ($similar as $item): ?>
          <div class="col-sm-6 col-lg-4">
            <div class="card h-100 shadow-sm property-card">
              <?php if (!empty($item['cover_url'])): ?>
                <img src="<?= e(base_url($item['cover_url'])) ?>" class="card-img-top" alt="<?= e($item['title']) ?>" style="height: 180px; object-fit: cover;">
              <?php else: ?>
                <div class="card-img-top bg-secondary-subtle d-flex align-items-center justify-content-center text-muted" style="height: 180px;">Chưa có ảnh</div>
              <?php endif; ?>
              <div class="card-body">
                <span class="badge text-bg-<?= $item['listing_type'] === 'sale' ? 'success' : 'info' ?>"><?= $item['listing_type'] === 'sale' ? 'Bán' : 'Cho thuê' ?></span>
                <span class="badge text-bg-primary"><?= e($item['type_name']) ?></span>
                <h6 class="card-title mt-2">
                  <a class="text-decoration-none text-dark" href="<?= e(base_url('property/detail/' . $item['id'])) ?>">
                    <?= e($item['title']) ?>
                  </a>
                </h6>
                <p class="text-danger fw-bold mb-1"><?= format_price($item['price']) ?><?= $item['listing_type'] === 'rent' ? '/tháng' : '' ?></p>
                <p class="small text-muted mb-0"><?= e($item['area']) ?> m² · <?= e($item['ward_name']) ?>, <?= e($item['district_name']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($images)): ?>
<div class="modal fade" id="galleryModal" tabindex="-1" aria-label="Xem ảnh bất động sản" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h2 class="modal-title fs-5">Ảnh bất động sản</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
      <div class="modal-body p-2">
        <div id="propertyGalleryCarousel" class="carousel slide" data-bs-interval="false">
          <div class="carousel-inner">
            <?php foreach ($images as $index => $image): ?>
              <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <img src="<?= e(base_url($image['image_url'])) ?>" class="gallery-modal-img" alt="Ảnh <?= $index + 1 ?> của <?= e($property['title']) ?>">
                <div class="text-center py-2"><?= $index + 1 ?> / <?= count($images) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#propertyGalleryCarousel" data-bs-slide="prev" aria-label="Ảnh trước"><span class="carousel-control-prev-icon bg-dark rounded" aria-hidden="true"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#propertyGalleryCarousel" data-bs-slide="next" aria-label="Ảnh tiếp theo"><span class="carousel-control-next-icon bg-dark rounded" aria-hidden="true"></span></button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="property-mobile-actions d-lg-none fixed-bottom bg-white border-top shadow d-flex gap-2">
  <?php if (!empty($property['agent_phone'])): ?>
    <a href="tel:<?= e($property['agent_phone']) ?>" class="btn btn-success flex-fill">Gọi ngay</a>
  <?php else: ?>
    <button type="button" class="btn btn-secondary flex-fill" disabled>Chưa có SĐT</button>
  <?php endif; ?>
  <a href="#appointmentSection" class="btn btn-primary flex-fill">Đặt lịch</a>
  <button type="button" id="mobileFavorite" class="btn btn-outline-danger flex-fill" onclick="document.getElementById('btnFavorite').click()"><?= !empty($isFavorited) ? 'Đã lưu' : 'Lưu tin' ?></button>
</div>

<!-- Modal Báo cáo vi phạm [D - Quốc] -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= e(base_url('report/submit')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger" id="reportModalLabel">🚩 Báo cáo vi phạm tin đăng</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">Nếu bạn phát hiện thông tin không chính xác, lừa đảo hoặc vi phạm quy định, vui lòng gửi phản ánh để ban quản trị xử lý.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Chọn lý do phổ biến</label>
            <select class="form-select form-select-sm mb-2" onchange="if(this.value){document.getElementById('reportReason').value = this.value;}">
              <option value="">-- Chọn lý do thường gặp --</option>
              <option value="Tin giả mạo / Bất động sản không có thật">Tin giả mạo / Bất động sản không có thật</option>
              <option value="Thông tin sai lệch (giá, diện tích, vị trí thực tế)">Thông tin sai lệch (giá, diện tích, vị trí thực tế)</option>
              <option value="Bất động sản đã bán hoặc đã cho thuê từ trước">Bất động sản đã bán hoặc đã cho thuê từ trước</option>
              <option value="Không liên lạc được người đăng tin / Số điện thoại ảo">Không liên lạc được người đăng tin / Số điện thoại ảo</option>
              <option value="Nội dung phản cảm, hình ảnh không đúng chuẩn mực">Nội dung phản cảm, hình ảnh không đúng chuẩn mực</option>
            </select>
            <label class="form-label fw-semibold small">Mô tả cụ thể <span class="text-danger">*</span></label>
            <textarea id="reportReason" name="reason" class="form-control form-control-sm" rows="3" placeholder="Chi tiết lý do vi phạm (tối thiểu 5 ký tự)..." required minlength="5"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm fw-semibold">Gửi báo cáo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Script Leaflet & Map.js -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?= e(base_url('assets/js/map.js')) ?>"></script>
<script>
  function switchGalleryImg(url, el) {
    const mainImg = document.getElementById('mainGalleryImg');
    if (mainImg) mainImg.src = url;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
  }

  async function handleToggleFavorite(propertyId) {
    const btn = document.getElementById('btnFavorite');
    const icon = document.getElementById('favIcon');
    const text = document.getElementById('favText');
    if (btn) btn.disabled = true;

    try {
      const res = await api('favorite/toggle/' + propertyId, { method: 'POST' });
      if (res.success) {
        const liked = res.data.liked;
        const mobileFavorite = document.getElementById('mobileFavorite');
        if (mobileFavorite) mobileFavorite.textContent = liked ? 'Đã lưu' : 'Lưu tin';
        if (liked) {
          btn.classList.remove('btn-outline-danger');
          btn.classList.add('btn-danger');
          if (icon) icon.textContent = '❤️';
          if (text) text.textContent = 'Đã lưu tin';
        } else {
          btn.classList.remove('btn-danger');
          btn.classList.add('btn-outline-danger');
          if (icon) icon.textContent = '🤍';
          if (text) text.textContent = 'Lưu tin';
        }
      } else {
        if (res.data && res.data.require_login) {
          if (confirm('Vui lòng đăng nhập để lưu tin yêu thích. Bạn có muốn chuyển đến trang đăng nhập ngay không?')) {
            window.location.href = '<?= e(base_url('auth/login')) ?>';
          }
        } else {
          alert(res.message || 'Không thể thực hiện thao tác.');
        }
      }
    } catch (err) {
      alert('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-gallery-url]').forEach(function (thumbnail) {
      thumbnail.addEventListener('click', function () {
        switchGalleryImg(thumbnail.dataset.galleryUrl, thumbnail);
      });
    });

    const galleryModal = document.getElementById('galleryModal');
    if (galleryModal) {
      galleryModal.addEventListener('show.bs.modal', function () {
        const thumbs = Array.from(document.querySelectorAll('.gallery-thumb'));
        const selected = thumbs.indexOf(document.querySelector('.gallery-thumb.active'));
        const carousel = document.getElementById('propertyGalleryCarousel');
        if (carousel && selected >= 0) bootstrap.Carousel.getOrCreateInstance(carousel).to(selected);
      });
    }

    // Tọa độ BĐS từ DB hoặc tọa độ mặc định TP.HCM
    const lat = <?= !empty($property['latitude']) ? (float)$property['latitude'] : 10.776889 ?>;
    const lng = <?= !empty($property['longitude']) ? (float)$property['longitude'] : 106.700806 ?>;
    const title = <?= json_for_script($property['title']) ?>;

    if (document.getElementById('propertyMap') && window.BdsMap && typeof window.BdsMap.initViewer === 'function') {
      const popupHtml = '<div style="min-width:180px; font-family:system-ui, sans-serif;">' +
        '<div class="fw-bold text-dark mb-1">' + title + '</div>' +
        '<div class="text-danger fw-bold mb-1">' + <?= json_for_script(format_price($property['price'])) ?> + ' - ' + <?= json_for_script($property['area'] . ' m²') ?> + '</div>' +
        '<div class="small text-muted">📍 ' + <?= json_for_script(($property['address_detail'] ? $property['address_detail'] . ', ' : '') . $property['ward_name'] . ', ' . $property['district_name']) ?> + '</div>' +
        '</div>';

      window.BdsMap.initViewer({
        mapId: 'propertyMap',
        lat: lat,
        lng: lng,
        zoom: 15,
        popupText: popupHtml
      });
    }
  });
</script>
