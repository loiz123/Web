-- =====================================================================
-- Web kinh doanh bất động sản - schema (MySQL 5.7+ / MariaDB 10.3+)
-- Chạy theo thứ tự: schema.sql -> seed.sql -> procedures.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS batdongsan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE batdongsan;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications, password_resets, saved_searches, reports, viewing_appointments, reviews, favorites,
  property_amenities, amenities, property_images, properties, property_types,
  wards, districts, provinces, users, roles;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- [A] Tài khoản & danh mục ----------
CREATE TABLE roles (
  id   TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(20) NOT NULL UNIQUE            -- admin | agent | customer
) ENGINE=InnoDB;

CREATE TABLE users (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_id       TINYINT UNSIGNED NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(100) NOT NULL UNIQUE,
  phone         VARCHAR(15),
  password_hash VARCHAR(255) NOT NULL,
  avatar        VARCHAR(255),
  bio           TEXT,
  status        ENUM('active','locked') NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE provinces (
  id   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE districts (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  province_id INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  CONSTRAINT fk_districts_province FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wards (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  province_id INT UNSIGNED NOT NULL DEFAULT 1,
  district_id INT UNSIGNED NULL DEFAULT NULL,
  name        VARCHAR(100) NOT NULL,
  CONSTRAINT fk_wards_province FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
  CONSTRAINT fk_wards_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
  INDEX idx_wards_province_name (province_id, name)
) ENGINE=InnoDB;

CREATE TABLE property_types (               -- Căn hộ, Nhà phố, Đất nền...
  id   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE amenities (                    -- Hồ bơi, Gym, Bảo vệ 24/7...
  id   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------- [B] Tin đăng ----------
CREATE TABLE properties (
  id             INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,                 -- môi giới đăng tin
  type_id        INT UNSIGNED NOT NULL,
  ward_id        INT UNSIGNED NOT NULL,
  title          VARCHAR(200) NOT NULL,
  slug           VARCHAR(255) NULL,
  description    TEXT,
  listing_type   ENUM('sale','rent') NOT NULL,
  price          DECIMAL(18,2) NOT NULL,
  deposit        DECIMAL(18,2) NULL,                    -- chỉ dùng khi cho thuê
  area           FLOAT NOT NULL,                        -- m2
  bedrooms       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bathrooms      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  direction      VARCHAR(30),
  legal_status   VARCHAR(100),                          -- Sổ hồng, Sổ đỏ...
  furniture      ENUM('none','basic','full') NULL,      -- chỉ dùng khi cho thuê
  pets_allowed   TINYINT(1) NOT NULL DEFAULT 0,
  address_detail VARCHAR(200),
  latitude       DECIMAL(10,7) NULL,
  longitude      DECIMAL(10,7) NULL,
  status         ENUM('pending','approved','rejected','hidden','closed') NOT NULL DEFAULT 'pending',
  reject_reason  VARCHAR(255) NULL,
  is_verified    TINYINT(1) NOT NULL DEFAULT 0,         -- tick xanh
  views          INT UNSIGNED NOT NULL DEFAULT 0,
  expired_at     DATETIME NULL,                         -- thời hạn hiển thị tin đăng (30 ngày từ lúc duyệt)
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prop_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_prop_type FOREIGN KEY (type_id) REFERENCES property_types(id),
  CONSTRAINT fk_prop_ward FOREIGN KEY (ward_id) REFERENCES wards(id),
  INDEX idx_prop_search (status, listing_type, price),
  INDEX idx_prop_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE property_images (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  image_url   VARCHAR(255) NOT NULL,                    -- đường dẫn tương đối trong public/
  is_cover    TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_img_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE property_amenities (
  property_id INT UNSIGNED NOT NULL,
  amenity_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (property_id, amenity_id),
  CONSTRAINT fk_pa_prop    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_amenity FOREIGN KEY (amenity_id)  REFERENCES amenities(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- [C] Lưu bộ lọc ----------
CREATE TABLE saved_searches (
  id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  name       VARCHAR(100) NOT NULL,
  filters    TEXT NOT NULL,                             -- JSON bộ lọc
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ss_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- [D] Tương tác & kiểm duyệt ----------
CREATE TABLE favorites (
  user_id     INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, property_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
  CONSTRAINT fk_fav_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,
  comment     VARCHAR(500),
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (property_id, user_id),
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_rev_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_user FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE viewing_appointments (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id  INT UNSIGNED NOT NULL,
  customer_id  INT UNSIGNED NOT NULL,
  scheduled_at DATETIME NOT NULL,
  note         VARCHAR(255),
  status       ENUM('pending','confirmed','cancelled','done') NOT NULL DEFAULT 'pending',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_va_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_va_user FOREIGN KEY (customer_id) REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reports (                       -- báo cáo tin giả/giá ảo
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  reporter_id INT UNSIGNED NULL,
  reason      VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status      ENUM('open','pending','resolved','dismissed') NOT NULL DEFAULT 'open',
  admin_notes TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_report_status (status),
  INDEX idx_report_property (property_id),
  CONSTRAINT fk_rep_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_rep_user FOREIGN KEY (reporter_id) REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW property_reports AS
SELECT id, property_id, reporter_id AS user_id, reason, description, status, admin_notes, created_at, updated_at
FROM reports;

CREATE TABLE password_resets (               -- khôi phục mật khẩu qua token hết hạn sau 30 phút
  email      VARCHAR(191) NOT NULL,
  token      VARCHAR(191) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_resets_email (email),
  INDEX idx_password_resets_token (token)
) ENGINE=InnoDB;

CREATE TABLE notifications (                 -- thông báo trong ứng dụng (In-app Notification)
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  title      VARCHAR(255) NOT NULL,
  message    TEXT,
  link       VARCHAR(255) NULL,
  type       VARCHAR(30) NOT NULL DEFAULT 'info',
  is_read    TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB;

-- Indexes bổ sung tối ưu truy vấn lọc, sắp xếp và lấy ảnh bìa
CREATE INDEX idx_prop_status_created ON properties (status, created_at DESC);
CREATE INDEX idx_prop_status_area ON properties (status, listing_type, area);
CREATE INDEX idx_prop_img_cover ON property_images (property_id, is_cover DESC, id);

-- Indexes tối ưu truy vấn phân cấp Địa giới hành chính (63 Tỉnh/Thành - Quận/Huyện - Phường/Xã)
CREATE INDEX idx_provinces_name ON provinces (name);
CREATE INDEX idx_districts_province_id ON districts (province_id);
CREATE INDEX idx_districts_province_name ON districts (province_id, name);
CREATE INDEX idx_wards_district_id ON wards (district_id);
CREATE INDEX idx_wards_district_name ON wards (district_id, name);


