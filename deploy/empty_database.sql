-- Import ONLY into a new empty database selected in phpMyAdmin.
-- No user accounts are created.
SET NAMES utf8mb4;


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


INSERT INTO roles (id, name) VALUES (1,'admin'),(2,'agent'),(3,'customer');

INSERT INTO provinces (id, name) VALUES (1,'TP. Hồ Chí Minh'),(2,'Hà Nội'),(3,'Đà Nẵng');
INSERT INTO districts (id, province_id, name) VALUES
(1,1,'Quận 1'),(2,1,'Quận 3'),(3,1,'TP. Thủ Đức'),(4,2,'Quận Ba Đình'),(5,2,'Quận Cầu Giấy'),(6,3,'Quận Hải Châu');
INSERT INTO wards (id, district_id, name) VALUES
(1,1,'Phường Bến Nghé'),(2,1,'Phường Bến Thành'),(3,1,'Phường Đa Kao'),
(4,2,'Phường Võ Thị Sáu'),(5,3,'Phường Thảo Điền'),(6,4,'Phường Điện Biên'),
(7,5,'Phường Dịch Vọng'),(8,6,'Phường Hải Châu 1');

INSERT INTO property_types (name) VALUES ('Căn hộ'),('Nhà phố'),('Biệt thự'),('Đất nền'),('Văn phòng'),('Condotel');
INSERT INTO amenities (name) VALUES ('Hồ bơi'),('Phòng gym'),('Bảo vệ 24/7'),('Chỗ đậu xe'),('Thang máy'),('Ban công');


-- location_reads.sql
-- Import vào database đã chọn. Không tạo hoặc xóa bảng.
DELIMITER $$

CREATE PROCEDURE sp_location_provinces()
BEGIN
  SELECT id, name FROM provinces ORDER BY name;
END$$

CREATE PROCEDURE sp_location_districts(IN p_province_id INT)
BEGIN
  SELECT id, name FROM districts WHERE province_id = p_province_id ORDER BY name;
END$$

CREATE PROCEDURE sp_location_wards(IN p_district_id INT)
BEGIN
  SELECT id, name FROM wards WHERE district_id = p_district_id ORDER BY name;
END$$

CREATE PROCEDURE sp_location_province_by_district(IN p_district_id INT)
BEGIN
  SELECT province_id FROM districts WHERE id = p_district_id;
END$$

CREATE PROCEDURE sp_location_valid_ward(IN p_province_id INT, IN p_district_id INT, IN p_ward_id INT)
BEGIN
  SELECT w.id FROM wards w JOIN districts d ON d.id = w.district_id
  WHERE w.id = p_ward_id AND d.id = p_district_id AND d.province_id = p_province_id;
END$$

DELIMITER ;



-- appointment.sql
-- Import vào database đã chọn; các procedure không xóa bảng hoặc dữ liệu.
DELIMITER $$
CREATE PROCEDURE sp_appointment_book(IN p_property_id INT, IN p_customer_id INT, IN p_scheduled_at DATETIME, IN p_note VARCHAR(255))
BEGIN
  INSERT INTO viewing_appointments (property_id, customer_id, scheduled_at, note)
  SELECT id, p_customer_id, p_scheduled_at, p_note FROM properties WHERE id = p_property_id AND status = 'approved';
  SELECT IF(ROW_COUNT() > 0, LAST_INSERT_ID(), 0) AS id;
END$$
CREATE PROCEDURE sp_appointment_has_active(IN p_property_id INT, IN p_customer_id INT)
BEGIN
  SELECT 1 FROM viewing_appointments WHERE property_id = p_property_id AND customer_id = p_customer_id AND status IN ('pending', 'confirmed') LIMIT 1;
END$$
CREATE PROCEDURE sp_appointment_for_customer(IN p_customer_id INT)
BEGIN
  SELECT va.*, p.title AS property_title FROM viewing_appointments va JOIN properties p ON p.id = va.property_id
  WHERE va.customer_id = p_customer_id ORDER BY va.scheduled_at DESC;
END$$
CREATE PROCEDURE sp_appointment_for_agent(IN p_agent_id INT)
BEGIN
  SELECT va.*, p.title AS property_title, u.full_name AS customer_name, u.phone AS customer_phone
  FROM viewing_appointments va JOIN properties p ON p.id = va.property_id JOIN users u ON u.id = va.customer_id
  WHERE p.user_id = p_agent_id ORDER BY va.scheduled_at DESC;
END$$
CREATE PROCEDURE sp_appointment_agent_status(IN p_id INT, IN p_agent_id INT, IN p_status VARCHAR(20))
BEGIN
  UPDATE viewing_appointments va JOIN properties p ON p.id = va.property_id SET va.status = p_status
  WHERE va.id = p_id AND p.user_id = p_agent_id AND va.status IN ('pending', 'confirmed') AND p_status IN ('confirmed', 'cancelled', 'done');
  SELECT ROW_COUNT() AS affected;
END$$
CREATE PROCEDURE sp_appointment_customer_cancel(IN p_id INT, IN p_customer_id INT)
BEGIN
  UPDATE viewing_appointments SET status = 'cancelled' WHERE id = p_id AND customer_id = p_customer_id AND status IN ('pending', 'confirmed');
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;



-- account_and_saved_search.sql
-- Import vào database đã chọn; không tạo hoặc xóa bảng.
DELIMITER $$
CREATE PROCEDURE sp_user_by_email(IN p_email VARCHAR(100))
BEGIN
  SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = p_email;
END$$
CREATE PROCEDURE sp_user_by_id(IN p_id INT)
BEGIN
  SELECT * FROM users WHERE id = p_id;
END$$
CREATE PROCEDURE sp_user_session(IN p_id INT)
BEGIN
  SELECT u.id, u.full_name, u.email, u.status, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = p_id;
END$$
CREATE PROCEDURE sp_user_register(IN p_name VARCHAR(100), IN p_email VARCHAR(100), IN p_phone VARCHAR(15), IN p_hash VARCHAR(255), IN p_role VARCHAR(20))
BEGIN
  INSERT INTO users (role_id, full_name, email, phone, password_hash)
  SELECT id, p_name, p_email, p_phone, p_hash FROM roles WHERE name = p_role;
  SELECT IF(ROW_COUNT() > 0, LAST_INSERT_ID(), 0) AS id;
END$$
CREATE PROCEDURE sp_user_update_profile(IN p_id INT, IN p_name VARCHAR(100), IN p_phone VARCHAR(15))
BEGIN
  UPDATE users SET full_name = p_name, phone = p_phone WHERE id = p_id;
END$$
CREATE PROCEDURE sp_user_set_password(IN p_id INT, IN p_hash VARCHAR(255))
BEGIN
  UPDATE users SET password_hash = p_hash WHERE id = p_id;
END$$
CREATE PROCEDURE sp_saved_search_create(IN p_user INT, IN p_name VARCHAR(100), IN p_filters LONGTEXT)
BEGIN
  INSERT INTO saved_searches (user_id, name, filters, created_at) VALUES (p_user, p_name, p_filters, NOW());
  SELECT LAST_INSERT_ID() AS id;
END$$
CREATE PROCEDURE sp_saved_search_list(IN p_user INT)
BEGIN
  SELECT * FROM saved_searches WHERE user_id = p_user ORDER BY created_at DESC;
END$$
CREATE PROCEDURE sp_saved_search_find(IN p_id INT, IN p_user INT)
BEGIN
  SELECT * FROM saved_searches WHERE id = p_id AND user_id = p_user LIMIT 1;
END$$
CREATE PROCEDURE sp_saved_search_delete(IN p_id INT, IN p_user INT)
BEGIN
  DELETE FROM saved_searches WHERE id = p_id AND user_id = p_user;
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;



-- interactions.sql
DELIMITER $$
CREATE PROCEDURE sp_favorite_exists(IN p_user INT, IN p_property INT)
BEGIN SELECT 1 FROM favorites WHERE user_id=p_user AND property_id=p_property LIMIT 1; END$$
CREATE PROCEDURE sp_favorite_add(IN p_user INT, IN p_property INT)
BEGIN INSERT IGNORE INTO favorites (user_id,property_id) VALUES (p_user,p_property); SELECT ROW_COUNT() AS affected; END$$
CREATE PROCEDURE sp_favorite_remove(IN p_user INT, IN p_property INT)
BEGIN DELETE FROM favorites WHERE user_id=p_user AND property_id=p_property; SELECT ROW_COUNT() AS affected; END$$
CREATE PROCEDURE sp_favorite_list(IN p_user INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.is_verified,p.status,f.created_at AS favorited_at,
 t.name AS type_name,w.name AS ward_name,d.name AS district_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM favorites f JOIN properties p ON p.id=f.property_id JOIN property_types t ON t.id=p.type_id
 JOIN wards w ON w.id=p.ward_id JOIN districts d ON d.id=w.district_id
 WHERE f.user_id=p_user AND p.status='approved' ORDER BY f.created_at DESC;
END$$
CREATE PROCEDURE sp_favorite_count(IN p_user INT)
BEGIN SELECT COUNT(*) AS total FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=p_user AND p.status='approved'; END$$
CREATE PROCEDURE sp_favorite_ids(IN p_user INT)
BEGIN SELECT property_id FROM favorites WHERE user_id=p_user; END$$
CREATE PROCEDURE sp_review_for_property(IN p_property INT)
BEGIN SELECT r.*,u.full_name AS user_name,u.avatar AS user_avatar,u.role_id FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.property_id=p_property ORDER BY r.created_at DESC; END$$
CREATE PROCEDURE sp_review_stats(IN p_property INT)
BEGIN SELECT COUNT(*) AS total,AVG(rating) AS avg_rating FROM reviews WHERE property_id=p_property; END$$
CREATE PROCEDURE sp_review_by_user(IN p_property INT, IN p_user INT)
BEGIN SELECT * FROM reviews WHERE property_id=p_property AND user_id=p_user LIMIT 1; END$$
CREATE PROCEDURE sp_review_save(IN p_property INT, IN p_user INT, IN p_rating INT, IN p_comment TEXT)
BEGIN
 IF EXISTS(SELECT 1 FROM properties WHERE id=p_property) THEN
  INSERT INTO reviews (property_id,user_id,rating,comment) VALUES (p_property,p_user,p_rating,p_comment)
  ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),created_at=CURRENT_TIMESTAMP;
  SELECT ROW_COUNT() AS affected;
 ELSE SELECT 0 AS affected; END IF;
END$$
CREATE PROCEDURE sp_review_delete(IN p_id INT, IN p_user INT)
BEGIN DELETE FROM reviews WHERE id=p_id AND user_id=p_user; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;



-- property_images.sql
DELIMITER $$
CREATE PROCEDURE sp_property_images(IN p_property INT)
BEGIN SELECT id,property_id,image_url,is_cover FROM property_images WHERE property_id=p_property ORDER BY is_cover DESC,id ASC; END$$
CREATE PROCEDURE sp_property_image_create(IN p_property INT, IN p_url VARCHAR(255), IN p_cover BOOLEAN)
BEGIN INSERT INTO property_images (property_id,image_url,is_cover) VALUES (p_property,p_url,p_cover); SELECT LAST_INSERT_ID() AS id; END$$
CREATE PROCEDURE sp_property_images_delete(IN p_property INT)
BEGIN DELETE FROM property_images WHERE property_id=p_property; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;



-- report.sql
DELIMITER $$
CREATE PROCEDURE sp_report_exists(IN p_property INT, IN p_user INT)
BEGIN SELECT 1 FROM reports WHERE property_id=p_property AND reporter_id=p_user AND status='open' LIMIT 1; END$$
CREATE PROCEDURE sp_report_create(IN p_property INT, IN p_user INT, IN p_reason VARCHAR(255))
BEGIN
 INSERT INTO reports (property_id,reporter_id,reason)
 SELECT p.id,p_user,p_reason FROM properties p WHERE p.id=p_property AND p.status='approved'
 AND NOT EXISTS (SELECT 1 FROM reports r WHERE r.property_id=p_property AND r.reporter_id=p_user AND r.status='open');
 SELECT IF(ROW_COUNT()>0,LAST_INSERT_ID(),0) AS id;
END$$
CREATE PROCEDURE sp_report_count(IN p_status VARCHAR(20))
BEGIN SELECT COUNT(*) AS total FROM reports WHERE p_status IS NULL OR status=p_status; END$$
CREATE PROCEDURE sp_report_list(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT r.*,p.title AS property_title,p.status AS property_status,u.full_name AS reporter_name FROM reports r
 JOIN properties p ON p.id=r.property_id JOIN users u ON u.id=r.reporter_id
 WHERE p_status IS NULL OR r.status=p_status ORDER BY r.created_at DESC LIMIT p_limit OFFSET p_offset;
END$$
CREATE PROCEDURE sp_report_resolve(IN p_id INT, IN p_status VARCHAR(20), IN p_hide BOOLEAN)
BEGIN
 DECLARE v_affected INT DEFAULT 0;
 UPDATE reports SET status=p_status WHERE id=p_id AND status='open' AND p_status IN ('resolved','dismissed');
 SET v_affected=ROW_COUNT();
 IF v_affected>0 AND p_hide=1 AND p_status='resolved' THEN
  UPDATE properties p JOIN reports r ON r.property_id=p.id SET p.status='hidden' WHERE r.id=p_id AND p.status='approved';
 END IF;
 SELECT v_affected AS affected;
END$$
DELIMITER ;



-- dashboard.sql
DELIMITER $$
CREATE PROCEDURE sp_dashboard_counts()
BEGIN
 SELECT (SELECT COUNT(*) FROM users) AS users_total,
        (SELECT COUNT(*) FROM properties) AS properties_total,
        (SELECT COUNT(*) FROM properties WHERE status='pending') AS pending_total,
        (SELECT COUNT(*) FROM reports WHERE status='open') AS reports_total;
END$$
CREATE PROCEDURE sp_dashboard_by_status()
BEGIN SELECT status,COUNT(*) AS total FROM properties GROUP BY status; END$$
CREATE PROCEDURE sp_dashboard_by_district()
BEGIN
 SELECT d.id,d.name AS district_name,pv.name AS province_name,COUNT(pr.id) AS total,SUM(pr.status='approved') AS approved
 FROM districts d JOIN provinces pv ON pv.id=d.province_id JOIN wards w ON w.district_id=d.id
 JOIN properties pr ON pr.ward_id=w.id GROUP BY d.id,d.name,pv.name ORDER BY total DESC,d.name;
END$$
DELIMITER ;



-- admin_users.sql
DELIMITER $$
CREATE PROCEDURE sp_admin_users_count(IN p_role VARCHAR(20), IN p_status VARCHAR(20), IN p_keyword VARCHAR(200))
BEGIN
 SELECT COUNT(*) AS total FROM users u JOIN roles r ON r.id=u.role_id
 WHERE (p_role IS NULL OR r.name=p_role) AND (p_status IS NULL OR u.status=p_status)
 AND (p_keyword IS NULL OR u.full_name LIKE p_keyword OR u.email LIKE p_keyword);
END$$
CREATE PROCEDURE sp_admin_users_list(IN p_role VARCHAR(20), IN p_status VARCHAR(20), IN p_keyword VARCHAR(200), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT u.id,u.full_name,u.email,u.phone,u.status,u.created_at,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id
 WHERE (p_role IS NULL OR r.name=p_role) AND (p_status IS NULL OR u.status=p_status)
 AND (p_keyword IS NULL OR u.full_name LIKE p_keyword OR u.email LIKE p_keyword) ORDER BY u.id DESC LIMIT p_limit OFFSET p_offset;
END$$
CREATE PROCEDURE sp_admin_user_find(IN p_id INT)
BEGIN SELECT u.id,u.status,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=p_id; END$$
CREATE PROCEDURE sp_admin_user_status(IN p_id INT, IN p_status VARCHAR(20))
BEGIN
 UPDATE users u JOIN roles r ON r.id=u.role_id SET u.status=p_status
 WHERE u.id=p_id AND p_status IN ('active','locked') AND (p_status<>'locked' OR r.name<>'admin');
 SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;



-- moderation.sql
DELIMITER $$
CREATE PROCEDURE sp_moderation_count(IN p_status VARCHAR(20))
BEGIN SELECT COUNT(*) AS total FROM properties WHERE status=p_status; END$$
CREATE PROCEDURE sp_moderation_list(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.address_detail,p.status,p.reject_reason,p.created_at,u.full_name AS agent_name
 FROM properties p JOIN users u ON u.id=p.user_id WHERE p.status=p_status
 ORDER BY p.created_at ASC,p.id ASC LIMIT p_limit OFFSET p_offset;
END$$
CREATE PROCEDURE sp_moderation_apply(IN p_id INT, IN p_action VARCHAR(10), IN p_reason VARCHAR(255))
BEGIN
 DECLARE v_affected INT DEFAULT 0;
 IF p_action='approve' THEN
  UPDATE properties SET status='approved',reject_reason=NULL WHERE id=p_id AND status='pending';
 ELSEIF p_action='reject' AND p_reason IS NOT NULL AND TRIM(p_reason)<>'' THEN
  UPDATE properties SET status='rejected',reject_reason=TRIM(p_reason) WHERE id=p_id AND status='pending';
 ELSEIF p_action='delete' THEN
  DELETE FROM properties WHERE id=p_id;
 ELSE
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid moderation action';
 END IF;
 SET v_affected=ROW_COUNT();
 SELECT v_affected AS affected,EXISTS(SELECT 1 FROM properties WHERE id=p_id) AS exists_after;
END$$
DELIMITER ;



-- catalog.sql
DELIMITER $$
CREATE PROCEDURE sp_catalog_find(IN t VARCHAR(30), IN n INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT * FROM provinces WHERE id=n;
 WHEN 'districts' THEN SELECT * FROM districts WHERE id=n;
 WHEN 'wards' THEN SELECT * FROM wards WHERE id=n;
 WHEN 'amenities' THEN SELECT * FROM amenities WHERE id=n;
 WHEN 'property_types' THEN SELECT * FROM property_types WHERE id=n;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
CREATE PROCEDURE sp_catalog_create(IN t VARCHAR(30),IN v_name VARCHAR(100),IN parent_id INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN INSERT INTO provinces(name) VALUES(v_name);
 WHEN 'districts' THEN INSERT INTO districts(name,province_id) VALUES(v_name,parent_id);
 WHEN 'wards' THEN INSERT INTO wards(name,district_id) VALUES(v_name,parent_id);
 WHEN 'amenities' THEN INSERT INTO amenities(name) VALUES(v_name);
 WHEN 'property_types' THEN INSERT INTO property_types(name) VALUES(v_name);
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
 SELECT LAST_INSERT_ID() AS id;
END$$
CREATE PROCEDURE sp_catalog_update(IN t VARCHAR(30),IN n INT,IN v_name VARCHAR(100),IN parent_id INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN UPDATE provinces SET name=v_name WHERE id=n;
 WHEN 'districts' THEN UPDATE districts SET name=v_name,province_id=parent_id WHERE id=n;
 WHEN 'wards' THEN UPDATE wards SET name=v_name,district_id=parent_id WHERE id=n;
 WHEN 'amenities' THEN UPDATE amenities SET name=v_name WHERE id=n;
 WHEN 'property_types' THEN UPDATE property_types SET name=v_name WHERE id=n;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
 SELECT ROW_COUNT() AS affected;
END$$
CREATE PROCEDURE sp_catalog_delete(IN t VARCHAR(30),IN n INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN DELETE FROM provinces WHERE id=n;
 WHEN 'districts' THEN DELETE FROM districts WHERE id=n;
 WHEN 'wards' THEN DELETE FROM wards WHERE id=n;
 WHEN 'amenities' THEN DELETE FROM amenities WHERE id=n;
 WHEN 'property_types' THEN DELETE FROM property_types WHERE id=n;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
 SELECT ROW_COUNT() AS affected;
END$$
CREATE PROCEDURE sp_catalog_name_exists(IN t VARCHAR(30),IN v_name VARCHAR(100),IN parent_id INT,IN except_id INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT COUNT(*) AS total FROM provinces WHERE name=v_name AND id<>except_id;
 WHEN 'districts' THEN SELECT COUNT(*) AS total FROM districts WHERE name=v_name AND id<>except_id AND province_id=parent_id;
 WHEN 'wards' THEN SELECT COUNT(*) AS total FROM wards WHERE name=v_name AND id<>except_id AND district_id=parent_id;
 WHEN 'amenities' THEN SELECT COUNT(*) AS total FROM amenities WHERE name=v_name AND id<>except_id;
 WHEN 'property_types' THEN SELECT COUNT(*) AS total FROM property_types WHERE name=v_name AND id<>except_id;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
CREATE PROCEDURE sp_catalog_usage(IN t VARCHAR(30),IN n INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT COUNT(*) AS total FROM properties p JOIN wards w ON w.id=p.ward_id JOIN districts d ON d.id=w.district_id WHERE d.province_id=n;
 WHEN 'districts' THEN SELECT COUNT(*) AS total FROM properties p JOIN wards w ON w.id=p.ward_id WHERE w.district_id=n;
 WHEN 'wards' THEN SELECT COUNT(*) AS total FROM properties WHERE ward_id=n;
 WHEN 'amenities' THEN SELECT COUNT(*) AS total FROM property_amenities WHERE amenity_id=n;
 WHEN 'property_types' THEN SELECT COUNT(*) AS total FROM properties WHERE type_id=n;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
CREATE PROCEDURE sp_catalog_children(IN t VARCHAR(30),IN n INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT COUNT(*) AS total FROM districts WHERE province_id=n;
 WHEN 'districts' THEN SELECT COUNT(*) AS total FROM wards WHERE district_id=n;
 WHEN 'wards' THEN SELECT 0 AS total;
 WHEN 'amenities' THEN SELECT 0 AS total;
 WHEN 'property_types' THEN SELECT 0 AS total;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
CREATE PROCEDURE sp_catalog_admin_list(IN t VARCHAR(30), IN province_filter INT, IN district_filter INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT pr.id,pr.name,NULL AS parent_id,NULL AS parent_name,(SELECT COUNT(*) FROM districts d WHERE d.province_id=pr.id) AS extra,(SELECT COUNT(*) FROM properties p JOIN wards w ON w.id=p.ward_id JOIN districts d ON d.id=w.district_id WHERE d.province_id=pr.id) AS used FROM provinces pr ORDER BY pr.name;
 WHEN 'districts' THEN SELECT d.id,d.name,d.province_id AS parent_id,pr.name AS parent_name,(SELECT COUNT(*) FROM wards w WHERE w.district_id=d.id) AS extra,(SELECT COUNT(*) FROM properties p JOIN wards w ON w.id=p.ward_id WHERE w.district_id=d.id) AS used FROM districts d JOIN provinces pr ON pr.id=d.province_id WHERE province_filter=0 OR d.province_id=province_filter ORDER BY pr.name,d.name;
 WHEN 'wards' THEN SELECT w.id,w.name,w.district_id AS parent_id,d.name AS parent_name,d.province_id AS grand_id,pr.name AS grand_name,NULL AS extra,(SELECT COUNT(*) FROM properties p WHERE p.ward_id=w.id) AS used FROM wards w JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=d.province_id WHERE (district_filter<>0 AND w.district_id=district_filter) OR (district_filter=0 AND (province_filter=0 OR d.province_id=province_filter)) ORDER BY pr.name,d.name,w.name LIMIT 300;
 WHEN 'amenities' THEN SELECT a.id,a.name,NULL AS parent_id,NULL AS parent_name,NULL AS extra,(SELECT COUNT(*) FROM property_amenities pa WHERE pa.amenity_id=a.id) AS used FROM amenities a ORDER BY a.name;
 WHEN 'property_types' THEN SELECT t1.id,t1.name,NULL AS parent_id,NULL AS parent_name,NULL AS extra,(SELECT COUNT(*) FROM properties p WHERE p.type_id=t1.id) AS used FROM property_types t1 ORDER BY t1.name;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
CREATE PROCEDURE sp_catalog_sorted(IN t VARCHAR(30))
BEGIN
 CASE t WHEN 'amenities' THEN SELECT id,name FROM amenities ORDER BY name;
 WHEN 'property_types' THEN SELECT id,name FROM property_types ORDER BY name;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
DELIMITER ;



-- property_detail.sql
DELIMITER $$
CREATE PROCEDURE sp_property_detail(IN p_id INT)
BEGIN
 SELECT p.*,t.name AS type_name,w.name AS ward_name,d.id AS district_id,d.name AS district_name,
 pr.id AS province_id,pr.name AS province_name,u.full_name AS agent_name,u.phone AS agent_phone,u.email AS agent_email,u.avatar AS agent_avatar
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=d.province_id JOIN users u ON u.id=p.user_id
 WHERE p.id=p_id LIMIT 1;
END$$
CREATE PROCEDURE sp_property_detail_images(IN p_id INT)
BEGIN SELECT id,image_url,is_cover FROM property_images WHERE property_id=p_id ORDER BY is_cover DESC,id ASC; END$$
CREATE PROCEDURE sp_property_detail_amenities(IN p_id INT)
BEGIN SELECT a.id,a.name FROM property_amenities pa JOIN amenities a ON a.id=pa.amenity_id WHERE pa.property_id=p_id ORDER BY a.name ASC; END$$
CREATE PROCEDURE sp_property_increment_views(IN p_id INT)
BEGIN UPDATE properties SET views=views+1,updated_at=updated_at WHERE id=p_id; END$$
CREATE PROCEDURE sp_property_same_type(IN p_id INT, IN p_type INT, IN p_limit INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.direction,p.is_verified,p.latitude,p.longitude,p.created_at,
 t.id AS type_id,t.name AS type_name,w.id AS ward_id,w.name AS ward_name,d.id AS district_id,d.name AS district_name,
 pr.id AS province_id,pr.name AS province_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=d.province_id
 WHERE p.status='approved' AND p.id<>p_id AND p.type_id=p_type ORDER BY p.created_at DESC LIMIT p_limit;
END$$
DELIMITER ;



-- property_agent.sql
DELIMITER $$
CREATE PROCEDURE sp_agent_property_by_id(IN p_id INT, IN p_owner INT)
BEGIN SELECT * FROM properties WHERE id=p_id AND (p_owner IS NULL OR user_id=p_owner) LIMIT 1; END$$
CREATE PROCEDURE sp_agent_property_list(IN p_owner INT, IN p_status VARCHAR(20))
BEGIN
 SELECT p.id,p.title,p.status,p.listing_type,p.price,p.area,p.created_at,p.reject_reason,t.name AS type_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id WHERE p.user_id=p_owner AND (p_status IS NULL OR p.status=p_status)
 ORDER BY p.created_at DESC,p.id DESC;
END$$
CREATE PROCEDURE sp_agent_property_update(IN p_id INT, IN p_owner INT, IN p_type INT, IN p_title VARCHAR(200), IN p_description TEXT, IN p_listing VARCHAR(10), IN p_price DECIMAL(18,2), IN p_area FLOAT, IN p_bedrooms INT, IN p_bathrooms INT, IN p_address VARCHAR(200), IN p_lat DOUBLE, IN p_lng DOUBLE)
BEGIN
 UPDATE properties SET type_id=p_type,title=p_title,description=p_description,listing_type=p_listing,price=p_price,area=p_area,
 bedrooms=p_bedrooms,bathrooms=p_bathrooms,address_detail=p_address,latitude=p_lat,longitude=p_lng,status='pending',reject_reason=NULL
 WHERE id=p_id AND user_id=p_owner;
 SELECT ROW_COUNT() AS affected, EXISTS(SELECT 1 FROM properties WHERE id=p_id AND user_id=p_owner) AS owned;
END$$
CREATE PROCEDURE sp_agent_property_status(IN p_id INT, IN p_owner INT, IN p_status VARCHAR(20))
BEGIN
 UPDATE properties SET status=p_status,reject_reason=NULL WHERE id=p_id AND user_id=p_owner AND
 ((p_status IN ('hidden','closed') AND status='approved') OR (p_status='pending' AND status IN ('hidden','closed')));
 SELECT ROW_COUNT() AS affected;
END$$
CREATE PROCEDURE sp_agent_property_delete(IN p_id INT, IN p_owner INT)
BEGIN DELETE FROM properties WHERE id=p_id AND user_id=p_owner; SELECT ROW_COUNT() AS affected; END$$
CREATE PROCEDURE sp_agent_property_create(IN p_user INT,IN p_type INT,IN p_ward INT,IN p_title VARCHAR(200),IN p_description TEXT,IN p_listing VARCHAR(10),IN p_price DECIMAL(18,2),IN p_deposit DECIMAL(18,2),IN p_area FLOAT,IN p_bedrooms INT,IN p_bathrooms INT,IN p_direction VARCHAR(30),IN p_legal VARCHAR(100),IN p_furniture VARCHAR(20),IN p_pets BOOLEAN,IN p_address VARCHAR(200),IN p_lat DOUBLE,IN p_lng DOUBLE)
BEGIN
 INSERT INTO properties (user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,latitude,longitude,status)
 VALUES (p_user,p_type,p_ward,p_title,p_description,p_listing,p_price,p_deposit,p_area,p_bedrooms,p_bathrooms,p_direction,p_legal,p_furniture,p_pets,p_address,p_lat,p_lng,'pending');
 SELECT LAST_INSERT_ID() AS id;
END$$
CREATE PROCEDURE sp_agent_property_add_amenity(IN p_property INT, IN p_amenity INT)
BEGIN INSERT INTO property_amenities (property_id,amenity_id) VALUES (p_property,p_amenity); END$$
CREATE PROCEDURE sp_property_similar_ranked(IN p_id INT, IN p_district INT, IN p_type INT, IN p_price DECIMAL(18,2), IN p_limit INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.direction,p.is_verified,p.latitude,p.longitude,p.created_at,p.address_detail,
 t.id AS type_id,t.name AS type_name,w.id AS ward_id,w.name AS ward_name,d.id AS district_id,d.name AS district_name,
 pr.id AS province_id,pr.name AS province_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id ASC LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=d.province_id
 WHERE p.status='approved' AND p.id<>p_id
 ORDER BY CASE WHEN d.id=p_district AND p.type_id=p_type THEN 0 WHEN d.id=p_district THEN 1 WHEN p.type_id=p_type THEN 2 ELSE 3 END,
 ABS(p.price-p_price),p.created_at DESC LIMIT p_limit;
END$$
DELIMITER ;



-- property_search.sql
DELIMITER $$
CREATE PROCEDURE sp_latest_properties_v3(IN p_limit INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
         p.direction, p.is_verified, p.latitude, p.longitude, p.created_at,
         t.id AS type_id, t.name AS type_name,
         w.id AS ward_id, w.name AS ward_name,
         d.id AS district_id, d.name AS district_name,
         pr.id AS province_id, pr.name AS province_name,
         (SELECT image_url FROM property_images i
            WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
  FROM properties p
  JOIN property_types t ON t.id = p.type_id
  JOIN wards w          ON w.id = p.ward_id
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
  ORDER BY p.created_at DESC, p.id DESC
  LIMIT p_limit;
END$$
CREATE PROCEDURE sp_search_properties_v3(
  IN p_keyword TEXT,
  IN p_segment TEXT,
  IN p_listing_type VARCHAR(10),
  IN p_type_id INT,
  IN p_province_id INT,
  IN p_district_id INT,
  IN p_ward_id INT,
  IN p_min_price DECIMAL(18,2),
  IN p_max_price DECIMAL(18,2),
  IN p_min_area FLOAT,
  IN p_max_area FLOAT,
  IN p_bedrooms INT,
  IN p_sort_by VARCHAR(20),
  IN p_limit INT,
  IN p_offset INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
         p.direction, p.is_verified, p.latitude, p.longitude, p.created_at,
         t.id AS type_id, t.name AS type_name,
         w.id AS ward_id, w.name AS ward_name,
         d.id AS district_id, d.name AS district_name,
         pr.id AS province_id, pr.name AS province_name,
         (SELECT image_url FROM property_images i
            WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
  FROM properties p
  JOIN property_types t ON t.id = p.type_id
  JOIN wards w          ON w.id = p.ward_id
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%',p_keyword,'%') OR p.address_detail LIKE CONCAT('%',p_keyword,'%') OR w.name LIKE CONCAT('%',p_keyword,'%') OR d.name LIKE CONCAT('%',p_keyword,'%') OR pr.name LIKE CONCAT('%',p_keyword,'%') OR t.name LIKE CONCAT('%',p_keyword,'%') OR (p_segment IS NOT NULL AND (p.title LIKE CONCAT('%',p_segment,'%') OR p.address_detail LIKE CONCAT('%',p_segment,'%') OR w.name LIKE CONCAT('%',p_segment,'%') OR d.name LIKE CONCAT('%',p_segment,'%') OR pr.name LIKE CONCAT('%',p_segment,'%') OR t.name LIKE CONCAT('%',p_segment,'%'))))
    AND (p_listing_type IS NULL OR p.listing_type = p_listing_type)
    AND (p_type_id IS NULL OR p.type_id = p_type_id)
    AND (p_ward_id IS NULL OR p.ward_id = p_ward_id)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (p_province_id IS NULL OR pr.id = p_province_id)
    AND (p_min_price IS NULL OR p.price >= p_min_price)
    AND (p_max_price IS NULL OR p.price <= p_max_price)
    AND (p_min_area IS NULL OR p.area >= p_min_area)
    AND (p_max_area IS NULL OR p.area <= p_max_area)
    AND (p_bedrooms IS NULL OR (p_bedrooms = 5 AND p.bedrooms >= 5) OR (p_bedrooms < 5 AND p.bedrooms = p_bedrooms))
  ORDER BY
    CASE WHEN p_sort_by = 'oldest' THEN p.created_at END ASC,
    CASE WHEN p_sort_by = 'price_asc' THEN p.price END ASC,
    CASE WHEN p_sort_by = 'price_desc' THEN p.price END DESC,
    CASE WHEN p_sort_by = 'area_asc' THEN p.area END ASC,
    CASE WHEN p_sort_by = 'area_desc' THEN p.area END DESC,
    CASE WHEN p_sort_by IN ('oldest','price_asc','area_asc') THEN p.id END ASC,
    CASE WHEN p_sort_by IN ('price_desc','area_desc') THEN p.id END DESC,
    p.created_at DESC, p.id DESC
  LIMIT p_limit OFFSET p_offset;
END$$
CREATE PROCEDURE sp_count_search_properties_v3(
  IN p_keyword TEXT,
  IN p_segment TEXT,
  IN p_listing_type VARCHAR(10),
  IN p_type_id INT,
  IN p_province_id INT,
  IN p_district_id INT,
  IN p_ward_id INT,
  IN p_min_price DECIMAL(18,2),
  IN p_max_price DECIMAL(18,2),
  IN p_min_area FLOAT,
  IN p_max_area FLOAT,
  IN p_bedrooms INT)
BEGIN
  SELECT COUNT(*) AS total
  FROM properties p
  JOIN property_types t ON t.id = p.type_id
  JOIN wards w          ON w.id = p.ward_id
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%',p_keyword,'%') OR p.address_detail LIKE CONCAT('%',p_keyword,'%') OR w.name LIKE CONCAT('%',p_keyword,'%') OR d.name LIKE CONCAT('%',p_keyword,'%') OR pr.name LIKE CONCAT('%',p_keyword,'%') OR t.name LIKE CONCAT('%',p_keyword,'%') OR (p_segment IS NOT NULL AND (p.title LIKE CONCAT('%',p_segment,'%') OR p.address_detail LIKE CONCAT('%',p_segment,'%') OR w.name LIKE CONCAT('%',p_segment,'%') OR d.name LIKE CONCAT('%',p_segment,'%') OR pr.name LIKE CONCAT('%',p_segment,'%') OR t.name LIKE CONCAT('%',p_segment,'%'))))
    AND (p_listing_type IS NULL OR p.listing_type = p_listing_type)
    AND (p_type_id IS NULL OR p.type_id = p_type_id)
    AND (p_ward_id IS NULL OR p.ward_id = p_ward_id)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (p_province_id IS NULL OR pr.id = p_province_id)
    AND (p_min_price IS NULL OR p.price >= p_min_price)
    AND (p_max_price IS NULL OR p.price <= p_max_price)
    AND (p_min_area IS NULL OR p.area >= p_min_area)
    AND (p_max_area IS NULL OR p.area <= p_max_area)
    AND (p_bedrooms IS NULL OR (p_bedrooms = 5 AND p.bedrooms >= 5) OR (p_bedrooms < 5 AND p.bedrooms = p_bedrooms));
END$$
DELIMITER ;
