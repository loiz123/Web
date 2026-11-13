-- Part 1/5: 50 procedures. Select the target database before import.
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS sp_latest_properties;

DELIMITER $$
CREATE PROCEDURE sp_latest_properties(IN p_limit INT)
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
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_search_properties;

DELIMITER $$
CREATE PROCEDURE sp_search_properties(
  IN p_keyword VARCHAR(100), IN p_listing_type VARCHAR(10), IN p_district_id INT,
  IN p_min_price DECIMAL(18,2), IN p_max_price DECIMAL(18,2),
  IN p_limit INT, IN p_offset INT)
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
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%'))
    AND (p_listing_type IS NULL OR p.listing_type = p_listing_type)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (p_min_price IS NULL OR p.price >= p_min_price)
    AND (p_max_price IS NULL OR p.price <= p_max_price)
  ORDER BY p.created_at DESC
  LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_search_properties_v2;

DELIMITER $$
CREATE PROCEDURE sp_search_properties_v2(
  IN p_keyword VARCHAR(100),
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
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%') OR w.name LIKE CONCAT('%', p_keyword, '%') OR d.name LIKE CONCAT('%', p_keyword, '%') OR pr.name LIKE CONCAT('%', p_keyword, '%') OR t.name LIKE CONCAT('%', p_keyword, '%'))
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
    p.created_at DESC, p.id DESC
  LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_count_search_properties_v2;

DELIMITER $$
CREATE PROCEDURE sp_count_search_properties_v2(
  IN p_keyword VARCHAR(100),
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
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%') OR w.name LIKE CONCAT('%', p_keyword, '%') OR d.name LIKE CONCAT('%', p_keyword, '%') OR pr.name LIKE CONCAT('%', p_keyword, '%') OR t.name LIKE CONCAT('%', p_keyword, '%'))
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

DROP PROCEDURE IF EXISTS sp_user_by_email;

DELIMITER $$
CREATE PROCEDURE sp_user_by_email(IN p_email VARCHAR(100))
BEGIN
  SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = p_email;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_by_id;

DELIMITER $$
CREATE PROCEDURE sp_user_by_id(IN p_id INT)
BEGIN
  SELECT * FROM users WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_session;

DELIMITER $$
CREATE PROCEDURE sp_user_session(IN p_id INT)
BEGIN
  SELECT u.id, u.full_name, u.email, u.password_hash, u.avatar, u.bio, u.status, u.role_id,
         r.name AS role_name
  FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_register;

DELIMITER $$
CREATE PROCEDURE sp_user_register(IN p_name VARCHAR(100), IN p_email VARCHAR(100), IN p_phone VARCHAR(15), IN p_hash VARCHAR(255), IN p_role VARCHAR(20))
BEGIN
  INSERT INTO users (role_id, full_name, email, phone, password_hash)
  SELECT id, p_name, p_email, p_phone, p_hash FROM roles WHERE name = p_role;
  SELECT IF(ROW_COUNT() > 0, LAST_INSERT_ID(), 0) AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_update_profile;

DELIMITER $$
CREATE PROCEDURE sp_user_update_profile(IN p_id INT, IN p_name VARCHAR(100), IN p_phone VARCHAR(15))
BEGIN
  UPDATE users SET full_name = p_name, phone = p_phone WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_set_password;

DELIMITER $$
CREATE PROCEDURE sp_user_set_password(IN p_id INT, IN p_hash VARCHAR(255))
BEGIN
  UPDATE users SET password_hash = p_hash WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_saved_search_create;

DELIMITER $$
CREATE PROCEDURE sp_saved_search_create(IN p_user INT, IN p_name VARCHAR(100), IN p_filters LONGTEXT)
BEGIN
  INSERT INTO saved_searches (user_id, name, filters, created_at) VALUES (p_user, p_name, p_filters, NOW());
  SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_saved_search_list;

DELIMITER $$
CREATE PROCEDURE sp_saved_search_list(IN p_user INT)
BEGIN
  SELECT * FROM saved_searches WHERE user_id = p_user ORDER BY created_at DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_saved_search_find;

DELIMITER $$
CREATE PROCEDURE sp_saved_search_find(IN p_id INT, IN p_user INT)
BEGIN
  SELECT * FROM saved_searches WHERE id = p_id AND user_id = p_user LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_saved_search_delete;

DELIMITER $$
CREATE PROCEDURE sp_saved_search_delete(IN p_id INT, IN p_user INT)
BEGIN
  DELETE FROM saved_searches WHERE id = p_id AND user_id = p_user;
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_admin_users_count;

DELIMITER $$
CREATE PROCEDURE sp_admin_users_count(IN p_role VARCHAR(20), IN p_status VARCHAR(20), IN p_keyword VARCHAR(200))
BEGIN
 SELECT COUNT(*) AS total FROM users u JOIN roles r ON r.id=u.role_id
 WHERE (p_role IS NULL OR r.name=p_role) AND (p_status IS NULL OR u.status=p_status)
 AND (p_keyword IS NULL OR u.full_name LIKE p_keyword OR u.email LIKE p_keyword);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_admin_users_list;

DELIMITER $$
CREATE PROCEDURE sp_admin_users_list(IN p_role VARCHAR(20), IN p_status VARCHAR(20), IN p_keyword VARCHAR(200), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT u.id,u.full_name,u.email,u.phone,u.status,u.created_at,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id
 WHERE (p_role IS NULL OR r.name=p_role) AND (p_status IS NULL OR u.status=p_status)
 AND (p_keyword IS NULL OR u.full_name LIKE p_keyword OR u.email LIKE p_keyword) ORDER BY u.id DESC LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_admin_user_find;

DELIMITER $$
CREATE PROCEDURE sp_admin_user_find(IN p_id INT)
BEGIN SELECT u.id,u.status,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_admin_user_status;

DELIMITER $$
CREATE PROCEDURE sp_admin_user_status(IN p_id INT, IN p_status VARCHAR(20))
BEGIN
 UPDATE users u JOIN roles r ON r.id=u.role_id SET u.status=p_status
 WHERE u.id=p_id AND p_status IN ('active','locked') AND (p_status<>'locked' OR r.name<>'admin');
 SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_appointment_book;

DELIMITER $$
CREATE PROCEDURE sp_appointment_book(IN p_property_id INT, IN p_customer_id INT, IN p_scheduled_at DATETIME, IN p_note VARCHAR(255))
BEGIN
  INSERT INTO viewing_appointments (property_id, customer_id, scheduled_at, note)
  SELECT id, p_customer_id, p_scheduled_at, p_note FROM properties WHERE id = p_property_id AND status = 'approved';
  SELECT IF(ROW_COUNT() > 0, LAST_INSERT_ID(), 0) AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_appointment_has_active;

DELIMITER $$
CREATE PROCEDURE sp_appointment_has_active(IN p_property_id INT, IN p_customer_id INT)
BEGIN
  SELECT 1 FROM viewing_appointments WHERE property_id = p_property_id AND customer_id = p_customer_id AND status IN ('pending', 'confirmed') LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_appointment_for_customer;

DELIMITER $$
CREATE PROCEDURE sp_appointment_for_customer(IN p_customer_id INT)
BEGIN
  SELECT va.*, p.title AS property_title FROM viewing_appointments va JOIN properties p ON p.id = va.property_id
  WHERE va.customer_id = p_customer_id ORDER BY va.scheduled_at DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_appointment_for_agent;

DELIMITER $$
CREATE PROCEDURE sp_appointment_for_agent(IN p_agent_id INT)
BEGIN
  SELECT va.*, p.title AS property_title, u.full_name AS customer_name, u.phone AS customer_phone
  FROM viewing_appointments va JOIN properties p ON p.id = va.property_id JOIN users u ON u.id = va.customer_id
  WHERE p.user_id = p_agent_id ORDER BY va.scheduled_at DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_appointment_agent_status;

DELIMITER $$
CREATE PROCEDURE sp_appointment_agent_status(IN p_id INT, IN p_agent_id INT, IN p_status VARCHAR(20))
BEGIN
  UPDATE viewing_appointments va JOIN properties p ON p.id = va.property_id SET va.status = p_status
  WHERE va.id = p_id AND p.user_id = p_agent_id AND va.status IN ('pending', 'confirmed') AND p_status IN ('confirmed', 'cancelled', 'done');
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_appointment_customer_cancel;

DELIMITER $$
CREATE PROCEDURE sp_appointment_customer_cancel(IN p_id INT, IN p_customer_id INT)
BEGIN
  UPDATE viewing_appointments SET status = 'cancelled' WHERE id = p_id AND customer_id = p_customer_id AND status IN ('pending', 'confirmed');
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_find;

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
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_create;

DELIMITER $$
CREATE PROCEDURE sp_catalog_create(IN t VARCHAR(30),IN v_name VARCHAR(100),IN parent_id INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN INSERT INTO provinces(name) VALUES(v_name);
 WHEN 'districts' THEN INSERT INTO districts(name,province_id) VALUES(v_name,parent_id);
 WHEN 'wards' THEN INSERT INTO wards(name,province_id,district_id) VALUES(v_name,parent_id,NULL);
 WHEN 'amenities' THEN INSERT INTO amenities(name) VALUES(v_name);
 WHEN 'property_types' THEN INSERT INTO property_types(name) VALUES(v_name);
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
 SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_update;

DELIMITER $$
CREATE PROCEDURE sp_catalog_update(IN t VARCHAR(30),IN n INT,IN v_name VARCHAR(100),IN parent_id INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN UPDATE provinces SET name=v_name WHERE id=n;
 WHEN 'districts' THEN UPDATE districts SET name=v_name,province_id=parent_id WHERE id=n;
 WHEN 'wards' THEN UPDATE wards SET name=v_name,province_id=parent_id WHERE id=n;
 WHEN 'amenities' THEN UPDATE amenities SET name=v_name WHERE id=n;
 WHEN 'property_types' THEN UPDATE property_types SET name=v_name WHERE id=n;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
 SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_delete;

DELIMITER $$
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
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_name_exists;

DELIMITER $$
CREATE PROCEDURE sp_catalog_name_exists(IN t VARCHAR(30),IN v_name VARCHAR(100),IN parent_id INT,IN except_id INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT COUNT(*) AS total FROM provinces WHERE name=v_name AND id<>except_id;
 WHEN 'districts' THEN SELECT COUNT(*) AS total FROM districts WHERE name=v_name AND id<>except_id AND province_id=parent_id;
 WHEN 'wards' THEN SELECT COUNT(*) AS total FROM wards WHERE BINARY name=v_name AND id<>except_id AND province_id=parent_id;
 WHEN 'amenities' THEN SELECT COUNT(*) AS total FROM amenities WHERE name=v_name AND id<>except_id;
 WHEN 'property_types' THEN SELECT COUNT(*) AS total FROM property_types WHERE name=v_name AND id<>except_id;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_usage;

DELIMITER $$
CREATE PROCEDURE sp_catalog_usage(IN t VARCHAR(30),IN n INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT COUNT(*) AS total FROM properties p JOIN wards w ON w.id=p.ward_id WHERE w.province_id=n;
 WHEN 'districts' THEN SELECT COUNT(*) AS total FROM properties p JOIN wards w ON w.id=p.ward_id WHERE w.district_id=n;
 WHEN 'wards' THEN SELECT COUNT(*) AS total FROM properties WHERE ward_id=n;
 WHEN 'amenities' THEN SELECT COUNT(*) AS total FROM property_amenities WHERE amenity_id=n;
 WHEN 'property_types' THEN SELECT COUNT(*) AS total FROM properties WHERE type_id=n;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_children;

DELIMITER $$
CREATE PROCEDURE sp_catalog_children(IN t VARCHAR(30),IN n INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT COUNT(*) AS total FROM wards WHERE province_id=n;
 WHEN 'districts' THEN SELECT COUNT(*) AS total FROM wards WHERE district_id=n;
 WHEN 'wards' THEN SELECT 0 AS total;
 WHEN 'amenities' THEN SELECT 0 AS total;
 WHEN 'property_types' THEN SELECT 0 AS total;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_admin_list;

DELIMITER $$
CREATE PROCEDURE sp_catalog_admin_list(IN t VARCHAR(30), IN province_filter INT, IN district_filter INT)
BEGIN
 CASE t
 WHEN 'provinces' THEN SELECT pr.id,pr.name,NULL AS parent_id,NULL AS parent_name,(SELECT COUNT(*) FROM wards w WHERE w.province_id=pr.id) AS extra,(SELECT COUNT(*) FROM properties p JOIN wards w ON w.id=p.ward_id WHERE w.province_id=pr.id) AS used FROM provinces pr ORDER BY pr.name;
 WHEN 'districts' THEN SELECT d.id,d.name,d.province_id AS parent_id,pr.name AS parent_name,(SELECT COUNT(*) FROM wards w WHERE w.district_id=d.id) AS extra,(SELECT COUNT(*) FROM properties p JOIN wards w ON w.id=p.ward_id WHERE w.district_id=d.id) AS used FROM districts d JOIN provinces pr ON pr.id=d.province_id WHERE province_filter=0 OR d.province_id=province_filter ORDER BY pr.name,d.name;
 WHEN 'wards' THEN SELECT w.id,w.name,w.province_id AS parent_id,pr.name AS parent_name,NULL AS grand_id,NULL AS grand_name,NULL AS extra,(SELECT COUNT(*) FROM properties p WHERE p.ward_id=w.id) AS used FROM wards w JOIN provinces pr ON pr.id=w.province_id WHERE province_filter=0 OR pr.id=province_filter ORDER BY pr.name,w.name LIMIT 300;
 WHEN 'amenities' THEN SELECT a.id,a.name,NULL AS parent_id,NULL AS parent_name,NULL AS extra,(SELECT COUNT(*) FROM property_amenities pa WHERE pa.amenity_id=a.id) AS used FROM amenities a ORDER BY a.name;
 WHEN 'property_types' THEN SELECT t1.id,t1.name,NULL AS parent_id,NULL AS parent_name,NULL AS extra,(SELECT COUNT(*) FROM properties p WHERE p.type_id=t1.id) AS used FROM property_types t1 ORDER BY t1.name;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_catalog_sorted;

DELIMITER $$
CREATE PROCEDURE sp_catalog_sorted(IN t VARCHAR(30))
BEGIN
 CASE t WHEN 'amenities' THEN SELECT id,name FROM amenities ORDER BY name;
 WHEN 'property_types' THEN SELECT id,name FROM property_types ORDER BY name;
 WHEN 'provinces' THEN SELECT id,name FROM provinces ORDER BY name;
 WHEN 'districts' THEN SELECT id,name FROM districts ORDER BY name;
 WHEN 'wards' THEN SELECT id,name FROM wards ORDER BY name;
 ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid catalog'; END CASE;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_dashboard_counts;

DELIMITER $$
CREATE PROCEDURE sp_dashboard_counts()
SQL SECURITY INVOKER
BEGIN
SELECT (SELECT COUNT(*) FROM users) AS users_total,
(SELECT COUNT(*) FROM properties) AS properties_total,
(SELECT COUNT(*) FROM properties WHERE status='approved') AS approved_total,
(SELECT COUNT(*) FROM properties WHERE status='pending') AS pending_total,
(SELECT COUNT(*) FROM reports WHERE status IN ('open','pending')) AS reports_total;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_dashboard_by_status;

DELIMITER $$
CREATE PROCEDURE sp_dashboard_by_status()
BEGIN SELECT status,COUNT(*) AS total FROM properties GROUP BY status; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_dashboard_by_district;

DELIMITER $$
CREATE PROCEDURE sp_dashboard_by_district()
BEGIN
 SELECT pv.id AS id,pv.name AS district_name,pv.name AS province_name,
        COALESCE(SUM(pr.status='approved'),0) AS total,
        COALESCE(SUM(pr.status='approved'),0) AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id
 JOIN provinces pv ON pv.id=w.province_id
 WHERE pr.status='approved'
 GROUP BY pv.id,pv.name ORDER BY approved DESC,pv.name ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_dashboard_by_province;

DELIMITER $$
CREATE PROCEDURE sp_dashboard_by_province()
BEGIN
 SELECT pv.id AS province_id,pv.name AS province_name,
        COALESCE(SUM(pr.status='approved'),0) AS total,
        COALESCE(SUM(pr.status='approved'),0) AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id
 JOIN provinces pv ON pv.id=w.province_id
 WHERE pr.status='approved'
 GROUP BY pv.id,pv.name ORDER BY approved DESC,pv.name ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_get_agent_properties;

DELIMITER $$
CREATE PROCEDURE sp_get_agent_properties(
    IN p_user_id INT UNSIGNED,
    IN p_status VARCHAR(10)
)
BEGIN
    SELECT p.id, p.title, p.status, p.listing_type, p.price, p.area, p.created_at,
           p.expired_at, p.reject_reason, t.name AS type_name,
           (SELECT image_url FROM property_images i
            WHERE i.property_id = p.id
            ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
    FROM properties p
    JOIN property_types t ON t.id = p.type_id
    WHERE p.user_id = p_user_id
      AND (p_status IS NULL OR p.status = p_status)
    ORDER BY p.created_at DESC, p.id DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_set_property_status;

DELIMITER $$
CREATE PROCEDURE sp_set_property_status(
    IN p_id INT UNSIGNED,
    IN p_user_id INT UNSIGNED,
    IN p_status VARCHAR(10)
)
BEGIN
    DECLARE v_status VARCHAR(10) DEFAULT NULL;

    SELECT status INTO v_status
    FROM properties
    WHERE id = p_id AND user_id = p_user_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin không tồn tại hoặc không thuộc về bạn.';
    ELSEIF p_status = 'hidden' AND v_status <> 'approved' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ có thể ẩn tin đã được duyệt.';
    ELSEIF p_status = 'pending' AND v_status NOT IN ('hidden', 'closed') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin không thể gửi duyệt lại ở trạng thái hiện tại.';
    ELSEIF p_status = 'closed' AND v_status <> 'approved' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ có thể đóng tin đã được duyệt.';
    ELSE
        UPDATE properties
        SET status = p_status, reject_reason = NULL
        WHERE id = p_id AND user_id = p_user_id;
        SELECT 1 AS changed;
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_favorite_exists;

DELIMITER $$
CREATE PROCEDURE sp_favorite_exists(IN p_user INT, IN p_property INT)
BEGIN SELECT 1 FROM favorites WHERE user_id=p_user AND property_id=p_property LIMIT 1; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_favorite_add;

DELIMITER $$
CREATE PROCEDURE sp_favorite_add(IN p_user INT, IN p_property INT)
BEGIN INSERT IGNORE INTO favorites (user_id,property_id) VALUES (p_user,p_property); SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_favorite_remove;

DELIMITER $$
CREATE PROCEDURE sp_favorite_remove(IN p_user INT, IN p_property INT)
BEGIN DELETE FROM favorites WHERE user_id=p_user AND property_id=p_property; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_favorite_list;

DELIMITER $$
CREATE PROCEDURE sp_favorite_list(IN p_user INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.is_verified,p.status,f.created_at AS favorited_at,
 t.name AS type_name,w.name AS ward_name,COALESCE(d.name,'') AS district_name,pr.name AS province_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM favorites f JOIN properties p ON p.id=f.property_id JOIN property_types t ON t.id=p.type_id
 JOIN wards w ON w.id=p.ward_id LEFT JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=w.province_id
 WHERE f.user_id=p_user AND p.status='approved' ORDER BY f.created_at DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_favorite_count;

DELIMITER $$
CREATE PROCEDURE sp_favorite_count(IN p_user INT)
BEGIN SELECT COUNT(*) AS total FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=p_user AND p.status='approved'; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_favorite_ids;

DELIMITER $$
CREATE PROCEDURE sp_favorite_ids(IN p_user INT)
BEGIN SELECT property_id FROM favorites WHERE user_id=p_user; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_review_for_property;

DELIMITER $$
CREATE PROCEDURE sp_review_for_property(IN p_property INT)
BEGIN SELECT r.*,u.full_name AS user_name,u.avatar AS user_avatar,u.role_id FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.property_id=p_property ORDER BY r.created_at DESC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_review_stats;

DELIMITER $$
CREATE PROCEDURE sp_review_stats(IN p_property INT)
BEGIN SELECT COUNT(*) AS total,AVG(rating) AS avg_rating FROM reviews WHERE property_id=p_property; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_review_by_user;

DELIMITER $$
CREATE PROCEDURE sp_review_by_user(IN p_property INT, IN p_user INT)
BEGIN SELECT * FROM reviews WHERE property_id=p_property AND user_id=p_user LIMIT 1; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_review_save;

DELIMITER $$
CREATE PROCEDURE sp_review_save(IN p_property INT, IN p_user INT, IN p_rating INT, IN p_comment TEXT)
BEGIN
 IF EXISTS(SELECT 1 FROM properties WHERE id=p_property) THEN
  INSERT INTO reviews (property_id,user_id,rating,comment) VALUES (p_property,p_user,p_rating,p_comment)
  ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),created_at=CURRENT_TIMESTAMP;
  SELECT ROW_COUNT() AS affected;
 ELSE SELECT 0 AS affected; END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_review_delete;

DELIMITER $$
CREATE PROCEDURE sp_review_delete(IN p_id INT, IN p_user INT)
BEGIN DELETE FROM reviews WHERE id=p_id AND user_id=p_user; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;
