-- =====================================================================
-- Web kinh doanh bất động sản - schema (MySQL 5.7+ / MariaDB 10.3+)
-- Chạy theo thứ tự: schema.sql -> seed.sql -> procedures.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS batdongsan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE batdongsan;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS saved_searches, reports, viewing_appointments, reviews, favorites,
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
  district_id INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  CONSTRAINT fk_wards_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
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
  reporter_id INT UNSIGNED NOT NULL,
  reason      VARCHAR(255) NOT NULL,
  status      ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rep_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_rep_user FOREIGN KEY (reporter_id) REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB;
