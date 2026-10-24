<!-- Leaflet 1.9 CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
  #propertyMap { height: 360px; width: 100%; border-radius: 8px; z-index: 1; }
  .gallery-main { height: 420px; object-fit: cover; border-radius: 8px; width: 100%; }
  .gallery-thumb { height: 80px; object-fit: cover; border-radius: 6px; cursor: pointer; transition: opacity .2s; }
  .gallery-thumb:hover, .gallery-thumb.active { opacity: .75; border: 2px solid #0d6efd; }
  .property-spec-item { padding: 12px; background-color: #f8f9fa; border-radius: 8px; }
</style>

<div class="container py-2">
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
          <span class="badge text-bg-light border text-muted">🕒 <?= date('d/m/Y', strtotime($property['created_at'])) ?></span>
        </div>
        <h1 class="h3 fw-bold mb-2"><?= e($property['title']) ?></h1>
        <p class="text-muted mb-0">
          📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['district_name']) ?>, <?= e($property['province_name']) ?>
        </p>
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
            <img id="mainGalleryImg" src="<?= e(base_url($images[0]['image_url'])) ?>" alt="<?= e($property['title']) ?>" class="gallery-main shadow-sm border">
          </div>
          <?php if (count($images) > 1): ?>
            <div class="d-flex gap-2 overflow-auto pb-2">
              <?php foreach ($images as $idx => $img): ?>
                <img src="<?= e(base_url($img['image_url'])) ?>" alt="Ảnh <?= $idx + 1 ?>" class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>" onclick="switchGalleryImg('<?= e(base_url($img['image_url'])) ?>', this)">
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
          <div id="propertyMap" class="border"></div>
          <p class="small text-muted mt-2 mb-0">
            📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['district_name']) ?>
          </p>
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
                  📞 <?= e($property['agent_phone']) ?>
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

        <!-- Card Hẹn xem nhà & Tương tác -->
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body p-3">
            <h6 class="fw-bold mb-2">Yêu cầu tư vấn</h6>
            <p class="small text-muted mb-3">Để lại thông tin để môi giới liên hệ trực tiếp hỗ trợ xem nhà miễn phí.</p>
            <form onsubmit="event.preventDefault(); alert('Cảm ơn bạn! Môi giới sẽ sớm liên hệ lại.');">
              <div class="mb-2">
                <input type="text" class="form-control form-control-sm" placeholder="Họ và tên của bạn" required>
              </div>
              <div class="mb-2">
                <input type="tel" class="form-control form-control-sm" placeholder="Số điện thoại" required>
              </div>
              <div class="mb-3">
                <textarea class="form-control form-control-sm" rows="2" placeholder="Nội dung lời nhắn hoặc thời gian rảnh..."></textarea>
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-sm fw-semibold">Gửi yêu cầu hẹn lịch</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Chia sẻ & Yêu thích -->
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
    // Tọa độ BĐS từ DB hoặc tọa độ mặc định TP.HCM
    const lat = <?= !empty($property['latitude']) ? (float)$property['latitude'] : 10.776889 ?>;
    const lng = <?= !empty($property['longitude']) ? (float)$property['longitude'] : 106.700806 ?>;
    const title = <?= json_encode($property['title']) ?>;

    if (window.BdsMap && typeof window.BdsMap.initViewer === 'function') {
      window.BdsMap.initViewer({
        mapId: 'propertyMap',
        lat: lat,
        lng: lng,
        zoom: 15,
        popupText: '<strong>' + title + '</strong><br>' + <?= json_encode($property['address_detail'] ?: $property['ward_name']) ?>
      });
    }
  });
</script>
