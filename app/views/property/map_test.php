<div class="container py-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Trang chủ</a></li>
      <li class="breadcrumb-item active" aria-current="page">Kiểm tra Google Maps</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 fw-bold mb-1">Kiểm tra Google Maps JavaScript API</h1>
      <p class="text-muted small mb-0">Nhiệm vụ Tuần 1 (Nhánh <code>setup/maps-key</code>) - Phụ trách: <strong>Hà Ngọc Thiện</strong></p>
    </div>
    <span class="badge text-bg-success fs-6">Tuần 1: Setup</span>
  </div>

  <?php if (empty($apiKey)): ?>
    <div class="alert alert-warning border-warning shadow-sm">
      <h5 class="alert-heading fw-bold">Chưa cấu hình API Key!</h5>
      <p class="mb-0">Vui lòng mở file <code>config/config.local.php</code> và thêm dòng: <code>'google_maps_api_key' => 'YOUR_KEY_HERE',</code></p>
    </div>
  <?php else: ?>
    <div id="map-status" class="alert alert-info py-2 small mb-3">
      Đang tải Google Maps JavaScript API...
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-2">
        <div id="map" style="height: 480px; width: 100%; border-radius: 8px;"></div>
      </div>
    </div>

    <div class="card bg-light border-0">
      <div class="card-body">
        <h6 class="fw-bold mb-2">Thông tin kiểm thử:</h6>
        <ul class="list-unstyled small mb-0">
          <li><strong>Tọa độ trung tâm:</strong> 10.776889, 106.700806 (TP. Hồ Chí Minh)</li>
          <li><strong>Trạng thái hiển thị:</strong> Khung bản đồ tải mượt, có Marker, điều khiển zoom/pan hoạt động bình thường, không bị màn hình trắng.</li>
          <li><strong>Lưu trữ bảo mật:</strong> Key lưu trong <code>config.local.php</code> (đã gitignore), không commit lên GitHub repo.</li>
        </ul>
      </div>
    </div>

    <script>
      function initMap() {
        const center = { lat: 10.776889, lng: 106.700806 };
        const statusEl = document.getElementById('map-status');

        try {
          const map = new google.maps.Map(document.getElementById('map'), {
            zoom: 14,
            center: center,
            mapTypeControl: true,
            streetViewControl: true,
          });

          const marker = new google.maps.Marker({
            position: center,
            map: map,
            title: 'BĐS Demo - Trung tâm TP.HCM',
            animation: google.maps.Animation.DROP,
          });

          const infoWindow = new google.maps.InfoWindow({
            content: '<div class="p-2"><strong>BĐS Việt</strong><br>Bản đồ hoạt động tốt!<br>Tọa độ: 10.7769, 106.7008</div>'
          });

          marker.addListener('click', () => {
            infoWindow.open(map, marker);
          });

          if (statusEl) {
            statusEl.className = 'alert alert-success py-2 small mb-3';
            statusEl.innerHTML = '<strong>Thành công:</strong> Google Maps JavaScript API đã kết nối và hiển thị bản đồ mượt mà!';
          }
        } catch (err) {
          if (statusEl) {
            statusEl.className = 'alert alert-danger py-2 small mb-3';
            statusEl.innerHTML = '<strong>Lỗi:</strong> ' + err.message;
          }
        }
      }

      window.gm_authFailure = function() {
        const statusEl = document.getElementById('map-status');
        if (statusEl) {
          statusEl.className = 'alert alert-danger py-2 small mb-3';
          statusEl.innerHTML = '<strong>Lỗi xác thực Google Maps:</strong> API Key không hợp lệ hoặc chưa bật <em>Maps JavaScript API</em> trong Google Cloud Console.';
        }
      };
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($apiKey) ?>&callback=initMap" async defer></script>
  <?php endif; ?>
</div>
