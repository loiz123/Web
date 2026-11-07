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

      <!-- Vị trí trên bản đồ Leaflet OpenStreetMap & Tiện ích xung quanh (POI) [C - Thiện] -->
      <div class="card shadow-sm border-0 mb-4" id="mapSection">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 border-bottom pb-2">
            <div>
              <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger"></i> Vị trí & Tiện ích xung quanh
              </h5>
              <span class="text-muted small">Bản đồ Leaflet OpenStreetMap • Khám phá tiện ích bán kính 1 - 2km</span>
            </div>
            <!-- Nút tắt / đặt lại tiện ích -->
            <button type="button" id="btnResetPoi" class="btn btn-outline-secondary btn-sm d-none" onclick="resetNearbyPoi()">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Xóa tiện ích
            </button>
          </div>

          <!-- Thanh nút bấm lọc tiện ích xung quanh -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted mb-2">
              <i class="bi bi-compass me-1"></i> Chọn tiện ích muốn xem quanh bất động sản:
            </label>
            <div class="d-flex flex-wrap gap-2" id="poiFilterGroup">
              <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn" data-poi="school">
                🏫 Trường học
              </button>
              <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn" data-poi="hospital">
                🏥 Bệnh viện / Y tế
              </button>
              <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn" data-poi="market">
                🛒 Siêu thị / Chợ
              </button>
              <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn text-dark" data-poi="bus">
                🚌 Trạm xe buýt
              </button>
              <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-3 py-1 fw-semibold poi-btn text-dark" data-poi="park">
                🌳 Công viên
              </button>
            </div>
          </div>

          <!-- Trạng thái đang tải tiện ích -->
          <div id="poiLoadingNotice" class="alert alert-light border small text-muted py-2 px-3 d-none align-items-center gap-2 mb-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span>Đang tra cứu dữ liệu tiện ích xung quanh từ OpenStreetMap...</span>
          </div>

          <!-- Bản đồ Leaflet -->
          <div id="propertyMap" class="border rounded-3" style="height: 380px;"></div>

          <div class="d-flex justify-content-between align-items-center mt-2">
            <p class="small text-muted mb-0">
              📍 <?= e($property['address_detail'] ? $property['address_detail'] . ', ' : '') ?><?= e($property['ward_name']) ?>, <?= e($property['district_name']) ?>, <?= e($property['province_name']) ?>
            </p>
            <span class="badge bg-light text-muted border small">Tọa độ: <?= round((float)($property['latitude'] ?? 10.776889), 4) ?>, <?= round((float)($property['longitude'] ?? 106.700806), 4) ?></span>
          </div>

          <!-- Danh sách kết quả tiện ích gần nhất -->
          <div id="poiResultsWrap" class="mt-3 pt-3 border-top d-none">
            <h6 class="fw-bold small text-dark mb-2 d-flex align-items-center justify-content-between">
              <span id="poiResultTitle">Tiện ích xung quanh</span>
              <span class="badge bg-primary-subtle text-primary" id="poiResultCount">0 địa điểm</span>
            </h6>
            <div class="row g-2" id="poiItemsList">
              <!-- Rendered via JS -->
            </div>
          </div>
        </div>
      </div>

      <!-- CÔNG CỤ TÍNH LÃI SUẤT VAY MUA NHÀ (MORTGAGE / LOAN CALCULATOR) [C - Thiện] -->
      <div class="card shadow-sm border-0 mb-4 mortgage-calc-card rounded-4 overflow-hidden" id="mortgageSection">
        <div class="card-header bg-white border-bottom p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div class="bg-primary-subtle text-primary p-2 rounded-3">
                <i class="bi bi-calculator-fill fs-5"></i>
              </div>
              <div>
                <h5 class="fw-bold text-dark mb-0">Tính lãi suất vay mua nhà</h5>
                <span class="text-muted small">Ước tính lịch trả nợ & số tiền thanh toán hàng tháng tức thì</span>
              </div>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
              <i class="bi bi-check-circle me-1"></i> Dự toán tài chính
            </span>
          </div>
        </div>

        <div class="card-body p-3 p-md-4">
          <div class="row g-4">
            <!-- Cột trái: Thông số khoản vay -->
            <div class="col-lg-7">
              <!-- 1. Giá trị bất động sản -->
              <div class="mb-3">
                <label class="form-label small fw-semibold text-dark d-flex justify-content-between">
                  <span>Giá trị bất động sản (VNĐ):</span>
                  <span class="text-primary fw-bold" id="displayHomePriceFormatted"><?= e(format_price($property['price'])) ?></span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-house-door"></i></span>
                  <input type="number" id="calcHomePrice" class="form-control" value="<?= (float)($property['price'] ?? 2000000000) ?>" min="100000000" step="10000000">
                  <span class="input-group-text bg-light text-muted">VNĐ</span>
                </div>
              </div>

              <!-- 2. Tỷ lệ vay & Số tiền vay -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-dark mb-0">Tỷ lệ vay ngân hàng:</label>
                  <span class="fw-bold text-primary"><span id="calcLoanRatioText">70</span>% (Vay <span id="displayLoanAmount">0 đ</span>)</span>
                </div>
                <input type="range" class="form-range" id="calcLoanRatio" min="20" max="85" step="5" value="70">
                <div class="d-flex gap-2 mt-1">
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-ratio-btn" data-ratio="50">50%</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-ratio-btn active" data-ratio="70">70% (Phổ biến)</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-ratio-btn" data-ratio="80">80%</button>
                </div>
              </div>

              <!-- 3. Thời hạn vay -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-dark mb-0">Thời hạn vay vốn:</label>
                  <span class="fw-bold text-primary"><span id="calcLoanTermText">20</span> năm (<span id="calcLoanMonthsText">240</span> tháng)</span>
                </div>
                <input type="range" class="form-range" id="calcLoanTerm" min="5" max="35" step="1" value="20">
                <div class="d-flex gap-2 mt-1">
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-term-btn" data-term="10">10 năm</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-term-btn" data-term="15">15 năm</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-term-btn active" data-term="20">20 năm</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-term-btn" data-term="25">25 năm</button>
                </div>
              </div>

              <!-- 4. Lãi suất cố định năm -->
              <div class="mb-3">
                <label class="form-label small fw-semibold text-dark d-flex justify-content-between">
                  <span>Lãi suất vay ưu đãi (%/năm):</span>
                  <span class="text-muted small">Gợi ý từ các ngân hàng lớn</span>
                </label>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-light"><i class="bi bi-percent"></i></span>
                  <input type="number" id="calcInterestRate" class="form-control" value="8.5" min="3.0" max="20.0" step="0.1">
                  <span class="input-group-text bg-light text-muted">%/năm</span>
                </div>
                <!-- Presets ngân hàng -->
                <div class="d-flex flex-wrap gap-1">
                  <button type="button" class="btn btn-light border btn-sm py-0 px-2 quick-bank-btn" data-rate="7.5">Vietcombank: 7.5%</button>
                  <button type="button" class="btn btn-light border btn-sm py-0 px-2 quick-bank-btn" data-rate="7.8">BIDV: 7.8%</button>
                  <button type="button" class="btn btn-light border btn-sm py-0 px-2 quick-bank-btn" data-rate="8.2">Techcombank: 8.2%</button>
                  <button type="button" class="btn btn-light border btn-sm py-0 px-2 quick-bank-btn active" data-rate="8.5">MB Bank: 8.5%</button>
                </div>
              </div>

              <!-- 5. Phương thức tính lãi -->
              <div>
                <label class="form-label small fw-semibold text-dark mb-2">Phương thức trả lãi:</label>
                <div class="d-flex flex-column flex-sm-row gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="calcMethod" id="methodReducing" value="reducing" checked>
                    <label class="form-check-label small" for="methodReducing">
                      <strong>Dư nợ giảm dần</strong> <span class="text-muted">(Gốc đều, lãi giảm dần)</span>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="calcMethod" id="methodFixed" value="fixed">
                    <label class="form-check-label small" for="methodFixed">
                      <strong>Trả góp đều</strong> <span class="text-muted">(Niên kim cố định hàng tháng)</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cột phải: Kết quả thanh toán & Biểu đồ phân bổ -->
            <div class="col-lg-5">
              <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                <div>
                  <div class="text-muted small fw-semibold mb-1" id="labelMonthlyPayment">Số tiền trả tháng đầu tiên:</div>
                  <div class="fs-2 fw-bold text-danger mb-3" id="resMonthlyPayment">0 đ</div>

                  <div class="d-flex flex-column gap-2 mb-3 pb-3 border-bottom small">
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">Tiền gốc hàng tháng:</span>
                      <strong class="text-dark" id="resPrincipalMonthly">0 đ</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span class="text-muted" id="labelInterestFirst">Tiền lãi tháng đầu:</span>
                      <strong class="text-dark" id="resInterestFirst">0 đ</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">Tổng tiền lãi phải trả:</span>
                      <strong class="text-danger" id="resTotalInterest">0 đ</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span class="text-muted">Tổng gốc + lãi phải trả:</span>
                      <strong class="text-dark" id="resTotalPayment">0 đ</strong>
                    </div>
                  </div>

                  <!-- Biểu đồ phân bổ trực quan -->
                  <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                      <span>Cơ cấu tài chính</span>
                      <span class="fw-semibold text-dark">Vốn tự có / Gốc / Lãi</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                      <div id="barEquity" class="progress-bar bg-success" role="progressbar" style="width: 30%;" title="Vốn tự có"></div>
                      <div id="barPrincipal" class="progress-bar bg-primary" role="progressbar" style="width: 50%;" title="Gốc vay"></div>
                      <div id="barInterest" class="progress-bar bg-warning text-dark" role="progressbar" style="width: 20%;" title="Tiền lãi"></div>
                    </div>
                    <div class="d-flex justify-content-between gap-1 mt-2 small text-muted" style="font-size: 0.75rem;">
                      <span><span class="d-inline-block rounded-circle bg-success me-1" style="width: 8px; height: 8px;"></span>Vốn tự có</span>
                      <span><span class="d-inline-block rounded-circle bg-primary me-1" style="width: 8px; height: 8px;"></span>Gốc vay</span>
                      <span><span class="d-inline-block rounded-circle bg-warning me-1" style="width: 8px; height: 8px;"></span>Tổng lãi</span>
                    </div>
                  </div>
                </div>

                <div>
                  <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" data-bs-toggle="collapse" data-bs-target="#scheduleCollapse">
                    <i class="bi bi-calendar-table me-1"></i> Xem lịch trả nợ 12 tháng đầu
                  </button>
                  <p class="text-muted small mb-0 text-center" style="font-size: 0.725rem;">
                    * Lưu ý: Kết quả mang tính tham khảo. Lãi suất thực tế áp dụng theo biểu phí của từng ngân hàng.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Bảng lịch trả nợ 12 tháng đầu thu gọn -->
          <div class="collapse mt-4" id="scheduleCollapse">
            <div class="card card-body bg-light border-0 p-3">
              <h6 class="fw-bold small mb-2"><i class="bi bi-clock-history me-1"></i> Bảng dự tính trả nợ 12 tháng đầu tiên:</h6>
              <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white small mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="text-center">Kỳ</th>
                      <th>Dư nợ đầu kỳ</th>
                      <th>Tiền gốc</th>
                      <th>Tiền lãi</th>
                      <th class="text-danger">Tổng phải trả</th>
                      <th>Dư nợ còn lại</th>
                    </tr>
                  </thead>
                  <tbody id="scheduleTableBody">
                    <!-- Rendered via JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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
            <?php else: ?>
              <div class="bg-light p-3 rounded mb-4 border">
                <h6 class="fw-bold mb-2"><?= $userReview ? 'Cập nhật đánh giá của bạn' : 'Để lại đánh giá của bạn' ?></h6>
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
                      <?= $userReview ? 'Lưu thay đổi' : 'Gửi đánh giá' ?>
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

        <!-- Card Hẹn xem nhà & Tương tác [D - Quốc] -->
        <div class="card shadow-sm border-0 mb-3">
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

        <!-- Chia sẻ, Yêu thích, So sánh & Báo cáo vi phạm -->
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

          <!-- Nút So sánh BĐS này & Tính lãi suất vay [C - Thiện] -->
          <div class="d-flex gap-2">
            <button type="button" id="btnDetailCompare" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1 py-2 fw-semibold" onclick="handleToggleDetailCompare()">
              <i class="bi bi-arrow-left-right"></i>
              <span id="detailCompareText">So sánh tin này</span>
            </button>
            <a href="#mortgageSection" class="btn btn-outline-success btn-sm d-flex align-items-center justify-content-center gap-1 py-2 px-3 fw-semibold" title="Tính khoản vay mua nhà">
              <i class="bi bi-calculator"></i> Tính vay
            </a>
          </div>

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

  // Dữ liệu BĐS phục vụ tính năng so sánh [C - Thiện]
  const currentPropertyData = {
    id: <?= (int)$property['id'] ?>,
    title: <?= json_for_script($property['title']) ?>,
    price: <?= (float)($property['price'] ?? 0) ?>,
    price_formatted: <?= json_for_script(format_price($property['price'])) ?>,
    area: <?= (float)($property['area'] ?? 0) ?>,
    avatar: <?= json_for_script(!empty($images[0]['image_url']) ? base_url($images[0]['image_url']) : property_avatar_url($property['avatar'] ?? null)) ?>,
    listing_type: <?= json_for_script($property['listing_type'] ?? 'sale') ?>,
    address: <?= json_for_script($property['ward_name'] . ', ' . $property['district_name']) ?>,
    url: window.location.href
  };

  function updateDetailCompareBtn() {
    const btn = document.getElementById('btnDetailCompare');
    const text = document.getElementById('detailCompareText');
    if (!btn || !window.PropertyCompare) return;

    if (window.PropertyCompare.has(currentPropertyData.id)) {
      btn.classList.remove('btn-outline-primary');
      btn.classList.add('btn-primary');
      if (text) text.innerHTML = '<i class="bi bi-check2"></i> Đã thêm so sánh';
    } else {
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-outline-primary');
      if (text) text.innerHTML = '<i class="bi bi-arrow-left-right"></i> So sánh tin này';
    }
  }

  function handleToggleDetailCompare() {
    if (!window.PropertyCompare) return;
    window.PropertyCompare.toggle(currentPropertyData);
    updateDetailCompareBtn();
  }

  // Quản lý bản đồ & tiện ích xung quanh POI [C - Thiện]
  let detailMapInstance = null;
  let activePoiCat = null;

  async function handlePoiCategoryClick(catKey, btn) {
    if (!detailMapInstance || !detailMapInstance.loadPoi) return;

    if (activePoiCat === catKey) {
      resetNearbyPoi();
      return;
    }

    activePoiCat = catKey;
    document.querySelectorAll('.poi-btn').forEach(b => {
      b.classList.remove('btn-navy', 'text-white');
    });
    btn.classList.add('btn-navy', 'text-white');

    const notice = document.getElementById('poiLoadingNotice');
    const resetBtn = document.getElementById('btnResetPoi');
    const resultsWrap = document.getElementById('poiResultsWrap');
    const itemsList = document.getElementById('poiItemsList');
    const titleEl = document.getElementById('poiResultTitle');
    const countEl = document.getElementById('poiResultCount');

    if (notice) notice.classList.remove('d-none');
    if (resetBtn) resetBtn.classList.remove('d-none');

    const districtName = <?= json_for_script($property['district_name'] ?? '') ?>;
    const items = await detailMapInstance.loadPoi(catKey, districtName);

    if (notice) notice.classList.add('d-none');

    if (resultsWrap && itemsList) {
      resultsWrap.classList.remove('d-none');
      const conf = (detailMapInstance.POI_CONFIG && detailMapInstance.POI_CONFIG[catKey]) ? detailMapInstance.POI_CONFIG[catKey] : { label: 'Tiện ích', icon: '📍' };
      if (titleEl) titleEl.innerHTML = conf.icon + ' ' + conf.label + ' gần bất động sản:';
      if (countEl) countEl.textContent = items.length + ' địa điểm';

      itemsList.innerHTML = items.map(it => {
        const walkMin = Math.max(1, Math.round(it.distance / 75));
        return '<div class="col-md-6">' +
          '<div class="p-2 border rounded-3 bg-white h-100 d-flex align-items-center justify-content-between poi-item-card" onclick="panToPoi(' + it.lat + ', ' + it.lng + ')" style="cursor: pointer;">' +
            '<div class="d-flex align-items-center gap-2 overflow-hidden me-2">' +
              '<span class="fs-5">' + it.icon + '</span>' +
              '<div class="text-truncate">' +
                '<div class="fw-semibold text-dark small text-truncate" title="' + it.name + '">' + it.name + '</div>' +
                '<div class="text-muted" style="font-size: 0.75rem;">' + it.type + ' • ~' + walkMin + ' phút đi bộ</div>' +
              </div>' +
            '</div>' +
            '<span class="badge bg-danger-subtle text-danger fw-bold flex-shrink-0">' + it.formattedDistance + '</span>' +
          '</div>' +
        '</div>';
      }).join('');
    }
  }

  function panToPoi(poiLat, poiLng) {
    if (!detailMapInstance || !detailMapInstance.map) return;
    detailMapInstance.map.setView([poiLat, poiLng], 16, { animate: true });
    if (detailMapInstance.poiLayer) {
      detailMapInstance.poiLayer.eachLayer(function (layer) {
        if (layer.getLatLng) {
          const pos = layer.getLatLng();
          if (Math.abs(pos.lat - poiLat) < 0.0001 && Math.abs(pos.lng - poiLng) < 0.0001) {
            layer.openPopup();
          }
        }
      });
    }
  }

  function resetNearbyPoi() {
    activePoiCat = null;
    document.querySelectorAll('.poi-btn').forEach(b => b.classList.remove('btn-navy', 'text-white'));
    const resetBtn = document.getElementById('btnResetPoi');
    if (resetBtn) resetBtn.classList.add('d-none');
    const resultsWrap = document.getElementById('poiResultsWrap');
    if (resultsWrap) resultsWrap.classList.add('d-none');
    if (detailMapInstance && detailMapInstance.clearPoi) {
      detailMapInstance.clearPoi();
    }
  }

  // CÔNG CỤ TÍNH LÃI SUẤT VAY MUA NHÀ [C - Thiện]
  function initMortgageCalculator() {
    const priceInput = document.getElementById('calcHomePrice');
    const ratioSlider = document.getElementById('calcLoanRatio');
    const ratioText = document.getElementById('calcLoanRatioText');
    const displayLoanAmount = document.getElementById('displayLoanAmount');
    const termSlider = document.getElementById('calcLoanTerm');
    const termText = document.getElementById('calcLoanTermText');
    const monthsText = document.getElementById('calcLoanMonthsText');
    const interestInput = document.getElementById('calcInterestRate');
    const displayPriceFormatted = document.getElementById('displayHomePriceFormatted');

    // Kết quả
    const resMonthly = document.getElementById('resMonthlyPayment');
    const resPrincipal = document.getElementById('resPrincipalMonthly');
    const resInterest = document.getElementById('resInterestFirst');
    const resTotalInterest = document.getElementById('resTotalInterest');
    const resTotalPayment = document.getElementById('resTotalPayment');
    const labelMonthly = document.getElementById('labelMonthlyPayment');
    const labelInterest = document.getElementById('labelInterestFirst');

    const barEquity = document.getElementById('barEquity');
    const barPrincipal = document.getElementById('barPrincipal');
    const barInterest = document.getElementById('barInterest');
    const scheduleBody = document.getElementById('scheduleTableBody');

    if (!priceInput || !ratioSlider || !termSlider || !interestInput) return;

    function formatVND(num) {
      return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';
    }

    function calculate() {
      const homePrice = parseFloat(priceInput.value) || 0;
      const ratio = parseFloat(ratioSlider.value) || 70;
      const loanAmount = homePrice * (ratio / 100);
      const years = parseInt(termSlider.value) || 20;
      const months = years * 12;
      const annualRate = parseFloat(interestInput.value) || 8.5;
      const monthlyRate = (annualRate / 100) / 12;
      const method = document.querySelector('input[name="calcMethod"]:checked') ? document.querySelector('input[name="calcMethod"]:checked').value : 'reducing';

      if (ratioText) ratioText.textContent = ratio;
      if (displayLoanAmount) displayLoanAmount.textContent = formatVND(loanAmount);
      if (termText) termText.textContent = years;
      if (monthsText) monthsText.textContent = months;
      if (displayPriceFormatted) displayPriceFormatted.textContent = formatVND(homePrice);

      if (loanAmount <= 0 || months <= 0) return;

      const monthlyPrincipal = loanAmount / months;
      const firstInterest = loanAmount * monthlyRate;
      let monthlyPayment = 0;
      let totalInterest = 0;
      let totalPayment = 0;

      if (method === 'reducing') {
        if (labelMonthly) labelMonthly.textContent = 'Số tiền trả tháng đầu tiên:';
        if (labelInterest) labelInterest.textContent = 'Tiền lãi tháng đầu:';
        monthlyPayment = monthlyPrincipal + firstInterest;
        totalInterest = (loanAmount + monthlyPrincipal) / 2 * months * monthlyRate;
        totalPayment = loanAmount + totalInterest;
      } else {
        if (labelMonthly) labelMonthly.textContent = 'Số tiền trả đều mỗi tháng:';
        if (labelInterest) labelInterest.textContent = 'Lãi ước tính tháng 1:';
        if (monthlyRate > 0) {
          monthlyPayment = (loanAmount * monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);
          totalPayment = monthlyPayment * months;
          totalInterest = totalPayment - loanAmount;
        } else {
          monthlyPayment = monthlyPrincipal;
          totalPayment = loanAmount;
          totalInterest = 0;
        }
      }

      if (resMonthly) resMonthly.textContent = formatVND(monthlyPayment);
      if (resPrincipal) resPrincipal.textContent = formatVND(monthlyPrincipal);
      if (resInterest) resInterest.textContent = formatVND(firstInterest);
      if (resTotalInterest) resTotalInterest.textContent = formatVND(totalInterest);
      if (resTotalPayment) resTotalPayment.textContent = formatVND(totalPayment);

      // Phân bổ thanh toán trực quan
      const equity = Math.max(0, homePrice - loanAmount);
      const grandSum = equity + totalPayment;
      if (grandSum > 0) {
        const pEq = Math.round((equity / grandSum) * 100);
        const pPr = Math.round((loanAmount / grandSum) * 100);
        const pIn = Math.max(0, 100 - pEq - pPr);
        if (barEquity) { barEquity.style.width = pEq + '%'; barEquity.title = 'Vốn tự có: ' + formatVND(equity); }
        if (barPrincipal) { barPrincipal.style.width = pPr + '%'; barPrincipal.title = 'Gốc vay: ' + formatVND(loanAmount); }
        if (barInterest) { barInterest.style.width = pIn + '%'; barInterest.title = 'Tiền lãi: ' + formatVND(totalInterest); }
      }

      // Render 12 tháng đầu bảng trả nợ
      if (scheduleBody) {
        let remaining = loanAmount;
        let rowsHtml = '';
        for (let m = 1; m <= Math.min(12, months); m++) {
          const mInterest = remaining * monthlyRate;
          let mPrincipal = 0;
          let mTotal = 0;

          if (method === 'reducing') {
            mPrincipal = monthlyPrincipal;
            mTotal = mPrincipal + mInterest;
          } else {
            mTotal = monthlyPayment;
            mPrincipal = mTotal - mInterest;
          }

          const endRemaining = Math.max(0, remaining - mPrincipal);
          rowsHtml += '<tr>' +
            '<td class="text-center fw-bold">Tháng ' + m + '</td>' +
            '<td>' + formatVND(remaining) + '</td>' +
            '<td>' + formatVND(mPrincipal) + '</td>' +
            '<td>' + formatVND(mInterest) + '</td>' +
            '<td class="text-danger fw-bold">' + formatVND(mTotal) + '</td>' +
            '<td>' + formatVND(endRemaining) + '</td>' +
          '</tr>';
          remaining = endRemaining;
        }
        scheduleBody.innerHTML = rowsHtml;
      }
    }

    priceInput.addEventListener('input', calculate);
    ratioSlider.addEventListener('input', calculate);
    termSlider.addEventListener('input', calculate);
    interestInput.addEventListener('input', calculate);
    document.querySelectorAll('input[name="calcMethod"]').forEach(r => r.addEventListener('change', calculate));

    // Nút chọn nhanh tỷ lệ vay
    document.querySelectorAll('.quick-ratio-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.quick-ratio-btn').forEach(b => b.classList.remove('active', 'btn-secondary', 'text-white'));
        this.classList.add('active');
        ratioSlider.value = this.dataset.ratio;
        calculate();
      });
    });

    // Nút chọn nhanh thời hạn vay
    document.querySelectorAll('.quick-term-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.quick-term-btn').forEach(b => b.classList.remove('active', 'btn-secondary', 'text-white'));
        this.classList.add('active');
        termSlider.value = this.dataset.term;
        calculate();
      });
    });

    // Nút chọn nhanh ngân hàng
    document.querySelectorAll('.quick-bank-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.quick-bank-btn').forEach(b => b.classList.remove('active', 'btn-secondary', 'text-white'));
        this.classList.add('active');
        interestInput.value = this.dataset.rate;
        calculate();
      });
    });

    calculate();
  }

  document.addEventListener('DOMContentLoaded', function () {
    // 1. Image gallery
    document.querySelectorAll('[data-gallery-url]').forEach(function (thumbnail) {
      thumbnail.addEventListener('click', function () {
        switchGalleryImg(thumbnail.dataset.galleryUrl, thumbnail);
      });
    });

    // 2. Leaflet viewer + POI
    const lat = <?= !empty($property['latitude']) ? (float)$property['latitude'] : 10.776889 ?>;
    const lng = <?= !empty($property['longitude']) ? (float)$property['longitude'] : 106.700806 ?>;
    const title = <?= json_for_script($property['title']) ?>;

    if (window.BdsMap && typeof window.BdsMap.initViewer === 'function') {
      const popupHtml = '<div style="min-width:180px; font-family:system-ui, sans-serif;">' +
        '<div class="fw-bold text-dark mb-1">' + title + '</div>' +
        '<div class="text-danger fw-bold mb-1">' + <?= json_for_script(format_price($property['price'])) ?> + ' - ' + <?= json_for_script($property['area'] . ' m²') ?> + '</div>' +
        '<div class="small text-muted">📍 ' + <?= json_for_script(($property['address_detail'] ? $property['address_detail'] . ', ' : '') . $property['ward_name'] . ', ' . $property['district_name']) ?> + '</div>' +
        '</div>';

      detailMapInstance = window.BdsMap.initViewer({
        mapId: 'propertyMap',
        lat: lat,
        lng: lng,
        zoom: 15,
        popupText: popupHtml
      });

      // Lắng nghe sự kiện click các nút lọc POI
      document.querySelectorAll('.poi-btn').forEach(btn => {
        btn.addEventListener('click', function () {
          const cat = this.dataset.poi;
          handlePoiCategoryClick(cat, this);
        });
      });
    }

    // 3. Khởi tạo Mortgage Calculator
    initMortgageCalculator();

    // 4. Đồng bộ nút So sánh tin này
    updateDetailCompareBtn();
    window.addEventListener('storage', updateDetailCompareBtn);
  });
</script>
