<?php
// app/views/property/compare.php
// Trang so sánh bất động sản đối đầu chi tiết [C - Thiện]
?>
<div class="container py-4">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= e(base_url()) ?>" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="<?= e(base_url('property/search')) ?>" class="text-decoration-none">Bất động sản</a></li>
      <li class="breadcrumb-item active" aria-current="page">So sánh bất động sản</li>
    </ol>
  </nav>

  <!-- Header Banner -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 border-bottom pb-3">
    <div>
      <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-arrow-left-right text-primary"></i> So sánh bất động sản đối đầu
      </h3>
      <p class="text-muted small mb-0">
        Phân tích chi tiết mức giá, diện tích, kết cấu và tiện nghi giữa các bất động sản bạn đang quan tâm.
      </p>
    </div>
    <?php if (!empty($properties)): ?>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> In so sánh
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllCompare()">
          <i class="bi bi-trash me-1"></i> Xóa tất cả
        </button>
        <?php if (count($properties) < 4): ?>
          <a href="<?= e(base_url('property/search')) ?>" class="btn btn-navy btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Thêm BĐS khác
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($properties)): ?>
    <!-- Trạng thái chưa có BĐS để so sánh -->
    <div class="card shadow-sm border-0 rounded-4 my-5 text-center p-5">
      <div class="card-body">
        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 80px; height: 80px;">
          <i class="bi bi-arrow-left-right fs-1"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2">Chưa có bất động sản nào trong danh sách so sánh</h4>
        <p class="text-muted mx-auto mb-4" style="max-width: 520px;">
          Bạn có thể so sánh tối đa <strong>3 - 4 bất động sản</strong> cùng lúc. Hãy nhấn vào biểu tượng 
          <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-arrow-left-right"></i> So sánh</span> 
          trên các tin đăng để bắt đầu phân tích đối đầu.
        </p>
        <div class="d-flex justify-content-center gap-2">
          <a href="<?= e(base_url('property/search')) ?>" class="btn btn-navy px-4 py-2">
            <i class="bi bi-search me-1"></i> Khám phá danh sách BĐS
          </a>
          <a href="<?= e(base_url()) ?>" class="btn btn-outline-secondary px-4 py-2">
            Về trang chủ
          </a>
        </div>
      </div>
    </div>

    <!-- Script tự động kiểm tra localStorage nếu người dùng truy cập trực tiếp /property/compare -->
    <script>
      document.addEventListener('DOMContentLoaded', function _callback(){
        if (window.PropertyCompare) {
          const list = typeof window.PropertyCompare.getAll === 'function'
            ? window.PropertyCompare.getAll()
            : window.PropertyCompare.get();
          const ids = (Array.isArray(list) ? list : [])
            .map(function _callback2(item){return Number(item&&item.id?item.id:item);})
            .filter(function _callback3(id){return Number.isFinite(id)&&id>0;});
          const currentUrl = new URL(window.location.href);
          if (ids.length > 0 && !currentUrl.searchParams.get('ids')) {
            currentUrl.searchParams.set('ids', ids.join(','));
            window.location.replace(currentUrl.toString());
          }
        }
      });</script>

  <?php else: ?>
    <!-- Bảng so sánh chi tiết đối đầu -->
    <?php
      // Tính toán giá trị min/max để highlight trực quan
      $prices = array_column($properties, 'price');
      $areas = array_column($properties, 'area');
      $minPrice = !empty($prices) ? min($prices) : 0;
      $maxArea = !empty($areas) ? max($areas) : 0;
      $colCount = count($properties);
      $colWidthPercent = round(80 / $colCount, 2);
    ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 comparison-matrix-table">
          <thead class="table-light">
            <tr>
              <th style="width: 20%; min-width: 180px;" class="bg-light fw-bold text-muted p-3 align-middle">
                Tiêu chí so sánh (<?= $colCount ?> BĐS)
              </th>
              <?php foreach ($properties as $prop): ?>
                <th style="width: <?= $colWidthPercent ?>%; min-width: 250px;" class="p-3 text-center position-relative">
                  <!-- Nút xóa tin này khỏi so sánh -->
                  <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle" 
                          title="Xóa khỏi so sánh" onclick="removeCompareItem(<?= (int)$prop['id'] ?>)">
                    <i class="bi bi-x-lg"></i>
                  </button>

                  <!-- Ảnh đại diện BĐS -->
                  <div class="position-relative mb-2 rounded-3 overflow-hidden shadow-sm" style="height: 160px;">
                    <img src="<?= e(property_avatar_url($prop['cover_url'] ?? property_avatar_url(null))) ?>" 
                         alt="<?= e($prop['title']) ?>" 
                         class="w-100 h-100 object-fit-cover">
                    <span class="badge position-absolute top-0 start-0 m-2 <?= ($prop['listing_type'] ?? '') === 'rent' ? 'bg-primary' : 'bg-danger' ?>">
                      <?= ($prop['listing_type'] ?? '') === 'rent' ? 'Cho thuê' : 'Bán' ?>
                    </span>
                    <?php if (!empty($prop['is_vip'])): ?>
                      <span class="badge bg-warning text-dark position-absolute bottom-0 start-0 m-2">
                        ⭐ VIP
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Tiêu đề BĐS -->
                  <h6 class="fw-bold mb-1 text-truncate-2 text-start">
                    <a href="<?= e(base_url('property/detail/' . (int)$prop['id'])) ?>" class="text-dark text-decoration-none">
                      <?= e($prop['title']) ?>
                    </a>
                  </h6>
                  <div class="text-muted small text-start mb-2">Mã tin: #<?= (int)$prop['id'] ?></div>

                  <!-- Nút xem chi tiết -->
                  <a href="<?= e(base_url('property/detail/' . (int)$prop['id'])) ?>" class="btn btn-navy btn-sm w-100">
                    <i class="bi bi-eye me-1"></i> Xem chi tiết
                  </a>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <!-- NHÓM 1: MỨC GIÁ & CHI PHÍ -->
            <tr class="table-group-header">
              <td colspan="<?= $colCount + 1 ?>" class="bg-light-subtle fw-bold text-primary p-2">
                <i class="bi bi-cash-stack me-1"></i> 1. MỨC GIÁ & CHI PHÍ
              </td>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Mức giá niêm yết</td>
              <?php foreach ($properties as $prop): ?>
                <?php $isBestPrice = ((float)$prop['price'] === (float)$minPrice && $colCount > 1); ?>
                <td class="text-center text-nowrap <?= $isBestPrice ? 'bg-success-subtle' : '' ?>">
                  <div class="fs-5 fw-bold text-danger text-nowrap" style="white-space: nowrap !important; font-variant-numeric: tabular-nums;"><?= e(format_price($prop['price'])) ?></div>
                  <?php if ($isBestPrice): ?>
                    <span class="badge bg-success small"><i class="bi bi-star-fill me-1"></i>Giá tốt nhất</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Đơn giá ước tính</td>
              <?php foreach ($properties as $prop): ?>
                <?php
                  $p = (float)($prop['price'] ?? 0);
                  $a = (float)($prop['area'] ?? 0);
                  $unitRate = ($p > 0 && $a > 0) ? round($p / $a / 1000000, 1) : null;
                ?>
                <td class="text-center">
                  <?php if ($unitRate): ?>
                    <span class="fw-semibold text-dark">~<?= $unitRate ?> tr/m²</span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Tiền cọc</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <?= !empty($prop['deposit']) ? e(format_price($prop['deposit'])) : '<span class="text-muted">Thỏa thuận</span>' ?>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- NHÓM 2: DIỆN TÍCH & KẾT CẤU -->
            <tr class="table-group-header">
              <td colspan="<?= $colCount + 1 ?>" class="bg-light-subtle fw-bold text-primary p-2">
                <i class="bi bi-aspect-ratio me-1"></i> 2. DIỆN TÍCH & KẾT CẤU PHÒNG
              </td>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Diện tích sử dụng</td>
              <?php foreach ($properties as $prop): ?>
                <?php $isMaxArea = ((float)$prop['area'] === (float)$maxArea && $colCount > 1); ?>
                <td class="text-center <?= $isMaxArea ? 'bg-primary-subtle' : '' ?>">
                  <span class="fw-bold fs-6 text-dark"><?= e($prop['area']) ?> m²</span>
                  <?php if ($isMaxArea): ?>
                    <div><span class="badge bg-primary small">Rộng nhất</span></div>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Phòng ngủ (PN)</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <span class="fw-bold"><?= (int)($prop['bedrooms'] ?? 0) ?></span> phòng
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Phòng tắm / WC</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <span class="fw-bold"><?= (int)($prop['bathrooms'] ?? 0) ?></span> phòng
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Loại bất động sản</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <span class="badge bg-light text-dark border"><?= e($prop['type_name'] ?? 'BĐS') ?></span>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Tình trạng nội thất</td>
              <?php
                $furnitureLabels = [
                    'none'  => 'Không nội thất',
                    'basic' => 'Nội thất cơ bản',
                    'full'  => 'Đầy đủ nội thất',
                ];
              ?>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <?php
                    $furnVal = $prop['furniture'] ?? '';
                    $furnLabel = $furnitureLabels[$furnVal] ?? $furnVal;
                  ?>
                  <?= $furnLabel !== '' ? e($furnLabel) : '<span class="text-muted">Nội thất cơ bản</span>' ?>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- NHÓM 3: VỊ TRÍ & PHÁP LÝ -->
            <tr class="table-group-header">
              <td colspan="<?= $colCount + 1 ?>" class="bg-light-subtle fw-bold text-primary p-2">
                <i class="bi bi-geo-alt me-1"></i> 3. VỊ TRÍ & PHÁP LÝ
              </td>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Hướng nhà / Ban công</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <?= !empty($prop['direction']) ? e($prop['direction']) : '<span class="text-muted">Chưa rõ</span>' ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Tình trạng pháp lý</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center">
                  <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-shield-check me-1"></i><?= e($prop['legal_status'] ?? 'Sổ đỏ/Sổ hồng') ?>
                  </span>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Khu vực / Địa chỉ</td>
              <?php foreach ($properties as $prop): ?>
                <td class="text-center small">
                  <?= e($prop['ward_name'] ?? '') ?>, <?= e($prop['district_name'] ?? '') ?>, <?= e($prop['province_name'] ?? '') ?>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- NHÓM 4: TIỆN ÍCH ĐI KÈM (AMENITIES MATRIX) -->
            <tr class="table-group-header">
              <td colspan="<?= $colCount + 1 ?>" class="bg-light-subtle fw-bold text-primary p-2">
                <i class="bi bi-check2-all me-1"></i> 4. TIỆN ÍCH NỘI NGOẠI KHU
              </td>
            </tr>
            <?php if (!empty($allAmenitiesMap)): ?>
              <?php foreach ($allAmenitiesMap as $amId => $amName): ?>
                <tr>
                  <td class="fw-semibold text-muted bg-light"><?= e($amName) ?></td>
                  <?php foreach ($properties as $prop): ?>
                    <?php
                      $hasAmenity = false;
                      if (!empty($prop['amenities'])) {
                        foreach ($prop['amenities'] as $a) {
                          if ((int)$a['id'] === (int)$amId) {
                            $hasAmenity = true;
                            break;
                          }
                        }
                      }
                    ?>
                    <td class="text-center">
                      <?php if ($hasAmenity): ?>
                        <span class="text-success fw-bold fs-6"><i class="bi bi-check-circle-fill"></i> Có</span>
                      <?php else: ?>
                        <span class="text-muted opacity-50"><i class="bi bi-dash-lg"></i></span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td class="fw-semibold text-muted bg-light">Tiện nghi nội khu</td>
                <?php foreach ($properties as $prop): ?>
                  <td class="text-center text-muted">Đang cập nhật</td>
                <?php endforeach; ?>
              </tr>
            <?php endif; ?>

            <!-- NHÓM 5: LIÊN HỆ & MÔI GIỚI -->
            <tr class="table-group-header">
              <td colspan="<?= $colCount + 1 ?>" class="bg-light-subtle fw-bold text-primary p-2">
                <i class="bi bi-person-lines-fill me-1"></i> 5. THÔNG TIN LIÊN HỆ
              </td>
            </tr>
            <tr>
              <td class="fw-semibold text-muted bg-light">Người đăng / Môi giới</td>
              <?php foreach ($properties as $prop): ?>
                <?php $contactPhone = $prop['agent_phone'] ?? $prop['author_phone'] ?? ''; ?>
                <td class="text-center">
                  <div class="fw-bold"><?= e($prop['agent_name'] ?? $prop['author_name'] ?? 'Môi giới BĐS') ?></div>
                  <?php if ($contactPhone !== ''): ?>
                    <a href="tel:<?= e($contactPhone) ?>" class="btn btn-outline-success btn-sm mt-1">
                      <i class="bi bi-telephone me-1"></i> <?= e($contactPhone) ?>
                    </a>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  function removeCompareItem(id) {
    if (window.PropertyCompare) {
      window.PropertyCompare.remove(id);
    }
    const currentUrl = new URL(window.location.href);
    const rawIds = currentUrl.searchParams.get('ids') || '';
    const idArr = rawIds.split(',').filter(function _callback(x){return x&&parseInt(x)!==parseInt(id);});
    if (idArr.length > 0) {
      currentUrl.searchParams.set('ids', idArr.join(','));
      window.location.href = currentUrl.toString();
    } else {
      currentUrl.searchParams.delete('ids');
      window.location.href = currentUrl.toString();
    }
  }

  function clearAllCompare() {
    if (confirm('Bạn có chắc muốn xóa tất cả bất động sản khỏi bảng so sánh?')) {
      if (window.PropertyCompare) {
        window.PropertyCompare.clear();
      }
      window.location.href = '<?= e(base_url('property/compare')) ?>';
    }
  }</script>
