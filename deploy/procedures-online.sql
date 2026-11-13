-- BDS Viet: complete stored-procedure installation.
-- Select your existing database in phpMyAdmin, then import this file.
-- Does not create/drop tables, import seed data, or select a database name.
-- Requires CREATE ROUTINE / ALTER ROUTINE / EXECUTE privileges.
SET NAMES utf8mb4;


-- Source: database/procedures.sql
DROP PROCEDURE IF EXISTS sp_latest_properties;

DROP PROCEDURE IF EXISTS sp_search_properties;

DROP PROCEDURE IF EXISTS sp_search_properties_v2;

DROP PROCEDURE IF EXISTS sp_count_search_properties_v2;

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



-- Source: database/procedures/account_and_saved_search.sql
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



-- Source: database/procedures/admin_users.sql
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



-- Source: database/procedures/appointment.sql
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



-- Source: database/procedures/catalog.sql
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



-- Source: database/procedures/dashboard.sql
DROP PROCEDURE IF EXISTS sp_dashboard_counts;

DELIMITER $$
CREATE PROCEDURE sp_dashboard_counts()
BEGIN
 SELECT (SELECT COUNT(*) FROM users) AS users_total,
        (SELECT COUNT(*) FROM properties WHERE status='approved') AS properties_total,
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



-- Source: database/procedures/hai.sql
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



-- Source: database/procedures/interactions.sql
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



-- Source: database/procedures/location_reads.sql
DROP PROCEDURE IF EXISTS sp_location_provinces;

DELIMITER $$
CREATE PROCEDURE sp_location_provinces()
BEGIN
  SELECT id, name FROM provinces ORDER BY name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_location_districts;

DELIMITER $$
CREATE PROCEDURE sp_location_districts(IN p_province_id INT)
BEGIN
  SELECT id, name FROM districts WHERE province_id = p_province_id ORDER BY name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_location_wards;

DELIMITER $$
CREATE PROCEDURE sp_location_wards(IN p_district_id INT)
BEGIN
  SELECT id, name FROM wards WHERE district_id = p_district_id ORDER BY name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_location_province_by_district;

DELIMITER $$
CREATE PROCEDURE sp_location_province_by_district(IN p_district_id INT)
BEGIN
  SELECT province_id FROM districts WHERE id = p_district_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_location_valid_ward;

DELIMITER $$
CREATE PROCEDURE sp_location_valid_ward(IN p_province_id INT, IN p_district_id INT, IN p_ward_id INT)
BEGIN
  SELECT w.id FROM wards w
  LEFT JOIN districts d ON d.id = w.district_id
  JOIN provinces pr ON pr.id = w.province_id
  WHERE w.id = p_ward_id AND pr.id = p_province_id
    AND (p_district_id = 0 OR p_district_id IS NULL OR d.id = p_district_id);
END$$
DELIMITER ;



-- Source: database/procedures/moderation.sql
DROP PROCEDURE IF EXISTS sp_moderation_count;

DELIMITER $$
CREATE PROCEDURE sp_moderation_count(IN p_status VARCHAR(20))
BEGIN SELECT COUNT(*) AS total FROM properties WHERE status=p_status; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_moderation_list;

DELIMITER $$
CREATE PROCEDURE sp_moderation_list(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.address_detail,p.status,p.reject_reason,p.is_verified,p.created_at,u.full_name AS agent_name
 FROM properties p JOIN users u ON u.id=p.user_id WHERE p.status=p_status
 ORDER BY p.created_at DESC,p.id DESC LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_moderation_apply;

DELIMITER $$
CREATE PROCEDURE sp_moderation_apply(IN p_id INT, IN p_action VARCHAR(10), IN p_reason VARCHAR(255))
BEGIN
 DECLARE v_affected INT DEFAULT 0;
 IF p_action='approve' THEN
  UPDATE properties SET status='approved',reject_reason=NULL,expired_at=COALESCE(expired_at,DATE_ADD(NOW(),INTERVAL 30 DAY)),expired_at=COALESCE(expired_at,DATE_ADD(NOW(),INTERVAL 30 DAY)) WHERE id=p_id AND status IN ('pending','hidden','rejected','closed');
 ELSEIF p_action='reject' AND p_reason IS NOT NULL AND TRIM(p_reason)<>'' THEN
  UPDATE properties SET status='rejected',reject_reason=TRIM(p_reason) WHERE id=p_id AND status IN ('pending','approved');
 ELSEIF p_action='delete' THEN
  DELETE FROM properties WHERE id=p_id;
 ELSE
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid moderation action';
 END IF;
 SET v_affected=ROW_COUNT();
 SELECT v_affected AS affected,EXISTS(SELECT 1 FROM properties WHERE id=p_id) AS exists_after;
END$$
DELIMITER ;



-- Source: database/procedures/property_agent.sql
DROP PROCEDURE IF EXISTS sp_agent_property_by_id;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_by_id(IN p_id INT, IN p_owner INT)
BEGIN SELECT * FROM properties WHERE id=p_id AND (p_owner IS NULL OR user_id=p_owner) LIMIT 1; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agent_property_list;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_list(IN p_owner INT, IN p_status VARCHAR(20))
BEGIN
 SELECT p.id,p.title,p.status,p.listing_type,p.price,p.area,p.created_at,p.expired_at,p.reject_reason,t.name AS type_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id WHERE p.user_id=p_owner AND (p_status IS NULL OR p.status=p_status)
 ORDER BY p.created_at DESC,p.id DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agent_property_update;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_update(IN p_id INT, IN p_owner INT, IN p_type INT, IN p_title VARCHAR(200), IN p_description TEXT, IN p_listing VARCHAR(10), IN p_price DECIMAL(18,2), IN p_area FLOAT, IN p_bedrooms INT, IN p_bathrooms INT, IN p_address VARCHAR(200), IN p_lat DOUBLE, IN p_lng DOUBLE)
BEGIN
 UPDATE properties SET type_id=p_type,title=p_title,description=p_description,listing_type=p_listing,price=p_price,area=p_area,
 bedrooms=p_bedrooms,bathrooms=p_bathrooms,address_detail=p_address,latitude=p_lat,longitude=p_lng,status='pending',reject_reason=NULL
 WHERE id=p_id AND user_id=p_owner;
 SELECT ROW_COUNT() AS affected, EXISTS(SELECT 1 FROM properties WHERE id=p_id AND user_id=p_owner) AS owned;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agent_property_status;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_status(IN p_id INT, IN p_owner INT, IN p_status VARCHAR(20))
BEGIN
 UPDATE properties SET status=p_status,reject_reason=NULL WHERE id=p_id AND user_id=p_owner AND
 ((p_status IN ('hidden','closed') AND status='approved') OR (p_status='pending' AND status IN ('hidden','closed')));
 SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agent_property_delete;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_delete(IN p_id INT, IN p_owner INT)
BEGIN DELETE FROM properties WHERE id=p_id AND user_id=p_owner; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agent_property_create;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_create(IN p_user INT,IN p_type INT,IN p_ward INT,IN p_title VARCHAR(200),IN p_description TEXT,IN p_listing VARCHAR(10),IN p_price DECIMAL(18,2),IN p_deposit DECIMAL(18,2),IN p_area FLOAT,IN p_bedrooms INT,IN p_bathrooms INT,IN p_direction VARCHAR(30),IN p_legal VARCHAR(100),IN p_furniture VARCHAR(20),IN p_pets BOOLEAN,IN p_address VARCHAR(200),IN p_lat DOUBLE,IN p_lng DOUBLE)
BEGIN
 INSERT INTO properties (user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,latitude,longitude,status)
 VALUES (p_user,p_type,p_ward,p_title,p_description,p_listing,p_price,p_deposit,p_area,p_bedrooms,p_bathrooms,p_direction,p_legal,p_furniture,p_pets,p_address,p_lat,p_lng,'pending');
 SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agent_property_add_amenity;

DELIMITER $$
CREATE PROCEDURE sp_agent_property_add_amenity(IN p_property INT, IN p_amenity INT)
BEGIN INSERT INTO property_amenities (property_id,amenity_id) VALUES (p_property,p_amenity); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_similar_ranked;

DELIMITER $$
CREATE PROCEDURE sp_property_similar_ranked(IN p_id INT, IN p_district INT, IN p_type INT, IN p_price DECIMAL(18,2), IN p_limit INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.direction,p.is_verified,p.latitude,p.longitude,p.created_at,p.address_detail,
 t.id AS type_id,t.name AS type_name,w.id AS ward_id,w.name AS ward_name,d.id AS district_id,d.name AS district_name,
 pr.id AS province_id,pr.name AS province_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id ASC LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 LEFT JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=w.province_id
 WHERE p.status='approved' AND p.id<>p_id
 ORDER BY CASE WHEN d.id=p_district AND p.type_id=p_type THEN 0 WHEN d.id=p_district THEN 1 WHEN p.type_id=p_type THEN 2 ELSE 3 END,
 ABS(p.price-p_price),p.created_at DESC LIMIT p_limit;
END$$
DELIMITER ;



-- Source: database/procedures/property_detail.sql
DROP PROCEDURE IF EXISTS sp_property_detail;

DELIMITER $$
CREATE PROCEDURE sp_property_detail(IN p_id INT)
BEGIN
 SELECT p.*,t.name AS type_name,w.name AS ward_name,d.id AS district_id,COALESCE(d.name,w.name) AS district_name,
 pr.id AS province_id,pr.name AS province_name,u.full_name AS agent_name,u.phone AS agent_phone,u.email AS agent_email,u.avatar AS agent_avatar,u.bio AS agent_bio,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 LEFT JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=w.province_id JOIN users u ON u.id=p.user_id
 WHERE p.id=p_id LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_detail_images;

DELIMITER $$
CREATE PROCEDURE sp_property_detail_images(IN p_id INT)
BEGIN SELECT id,image_url,is_cover FROM property_images WHERE property_id=p_id ORDER BY is_cover DESC,id ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_detail_amenities;

DELIMITER $$
CREATE PROCEDURE sp_property_detail_amenities(IN p_id INT)
BEGIN SELECT a.id,a.name FROM property_amenities pa JOIN amenities a ON a.id=pa.amenity_id WHERE pa.property_id=p_id ORDER BY a.name ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_increment_views;

DELIMITER $$
CREATE PROCEDURE sp_property_increment_views(IN p_id INT)
BEGIN UPDATE properties SET views=views+1,updated_at=updated_at WHERE id=p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_same_type;

DELIMITER $$
CREATE PROCEDURE sp_property_same_type(IN p_id INT, IN p_type INT, IN p_limit INT)
BEGIN
 SELECT p.id,p.title,p.slug,p.address_detail,p.status,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.direction,p.is_verified,p.latitude,p.longitude,p.created_at,
 t.id AS type_id,t.name AS type_name,w.id AS ward_id,w.name AS ward_name,d.id AS district_id,d.name AS district_name,
 pr.id AS province_id,pr.name AS province_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 LEFT JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=w.province_id
 WHERE p.status='approved' AND p.id<>p_id AND p.type_id=p_type ORDER BY p.created_at DESC LIMIT p_limit;
END$$
DELIMITER ;



-- Source: database/procedures/property_images.sql
DROP PROCEDURE IF EXISTS sp_property_images;

DELIMITER $$
CREATE PROCEDURE sp_property_images(IN p_property INT)
BEGIN SELECT id,property_id,image_url,is_cover FROM property_images WHERE property_id=p_property ORDER BY is_cover DESC,id ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_image_create;

DELIMITER $$
CREATE PROCEDURE sp_property_image_create(IN p_property INT, IN p_url VARCHAR(255), IN p_cover BOOLEAN)
BEGIN INSERT INTO property_images (property_id,image_url,is_cover) VALUES (p_property,p_url,p_cover); SELECT LAST_INSERT_ID() AS id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_images_delete;

DELIMITER $$
CREATE PROCEDURE sp_property_images_delete(IN p_property INT)
BEGIN DELETE FROM property_images WHERE property_id=p_property; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;



-- Source: database/procedures/property_search.sql
DROP PROCEDURE IF EXISTS sp_latest_properties_v3;

DELIMITER $$
CREATE PROCEDURE sp_latest_properties_v3(IN p_limit INT)
BEGIN
  SELECT p.id, p.title, p.slug, p.address_detail, p.status, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
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
  LEFT JOIN districts d ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = w.province_id
  WHERE p.status = 'approved'
  ORDER BY p.created_at DESC, p.id DESC
  LIMIT p_limit;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_search_properties_v3;

DELIMITER $$
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
  SELECT p.id, p.title, p.slug, p.address_detail, p.status, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
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
  LEFT JOIN districts d ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = w.province_id
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
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_count_search_properties_v3;

DELIMITER $$
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
  LEFT JOIN districts d ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = w.province_id
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



-- Source: database/procedures/quoc.sql
DROP PROCEDURE IF EXISTS sp_moderate_property;

DELIMITER $$
CREATE PROCEDURE sp_moderate_property(
    IN p_id     INT UNSIGNED,
    IN p_action VARCHAR(10),      -- 'approve' | 'reject' | 'delete'
    IN p_reason VARCHAR(255)
)
BEGIN
    DECLARE v_status VARCHAR(10) DEFAULT NULL;

    SELECT status INTO v_status FROM properties WHERE id = p_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin không tồn tại hoặc đã bị xóa.';
    END IF;

    IF p_action = 'approve' THEN
        IF v_status <> 'pending' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ duyệt được tin đang chờ duyệt.';
        END IF;
        UPDATE properties SET status = 'approved', reject_reason = NULL, expired_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = p_id;

    ELSEIF p_action = 'reject' THEN
        IF v_status <> 'pending' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ từ chối được tin đang chờ duyệt.';
        END IF;
        IF p_reason IS NULL OR TRIM(p_reason) = '' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vui lòng nhập lý do từ chối.';
        END IF;
        UPDATE properties SET status = 'rejected', reject_reason = TRIM(p_reason) WHERE id = p_id;

    ELSEIF p_action = 'delete' THEN
        DELETE FROM properties WHERE id = p_id;

    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hành động không hợp lệ.';
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_stats_properties_by_status;

DELIMITER $$
CREATE PROCEDURE sp_stats_properties_by_status()
BEGIN
    SELECT status, COUNT(*) AS total FROM properties GROUP BY status;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_stats_properties_by_district;

DELIMITER $$
CREATE PROCEDURE sp_stats_properties_by_district()
BEGIN
    SELECT COALESCE(d.id, w.id) AS id, COALESCE(d.name, w.name) AS district_name, pv.name AS province_name,
           COALESCE(SUM(pr.status = 'approved'), 0) AS total,
           COALESCE(SUM(pr.status = 'approved'), 0) AS approved
    FROM properties pr
    JOIN wards w ON w.id = pr.ward_id
    LEFT JOIN districts d ON d.id = w.district_id
    JOIN provinces pv ON pv.id = w.province_id
    WHERE pr.status = 'approved'
    GROUP BY d.id, CASE WHEN d.id IS NULL THEN w.id ELSE 0 END, COALESCE(d.name, w.name), pv.name
    ORDER BY approved DESC, district_name;
END$$
DELIMITER ;



-- Source: database/procedures/report.sql
DROP PROCEDURE IF EXISTS sp_report_exists;

DELIMITER $$
CREATE PROCEDURE sp_report_exists(IN p_property INT, IN p_user INT)
BEGIN SELECT 1 FROM reports WHERE property_id=p_property AND reporter_id=p_user AND status='open' LIMIT 1; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_create;

DELIMITER $$
CREATE PROCEDURE sp_report_create(IN p_property INT, IN p_user INT, IN p_reason VARCHAR(255))
BEGIN
 INSERT INTO reports (property_id,reporter_id,reason)
 SELECT p.id,p_user,p_reason FROM properties p WHERE p.id=p_property AND p.status='approved'
 AND NOT EXISTS (SELECT 1 FROM reports r WHERE r.property_id=p_property AND r.reporter_id=p_user AND r.status='open');
 SELECT IF(ROW_COUNT()>0,LAST_INSERT_ID(),0) AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_count;

DELIMITER $$
CREATE PROCEDURE sp_report_count(IN p_status VARCHAR(20))
BEGIN SELECT COUNT(*) AS total FROM reports WHERE p_status IS NULL OR status=p_status; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_list;

DELIMITER $$
CREATE PROCEDURE sp_report_list(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT r.*,p.title AS property_title,p.status AS property_status,u.full_name AS reporter_name FROM reports r
 JOIN properties p ON p.id=r.property_id JOIN users u ON u.id=r.reporter_id
 WHERE p_status IS NULL OR r.status=p_status ORDER BY r.created_at DESC LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_resolve;

DELIMITER $$
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



-- Source: database/procedures/zz_runtime.sql
DROP PROCEDURE IF EXISTS sp_model_roles_all;

DELIMITER $$
CREATE PROCEDURE sp_model_roles_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `roles`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_roles_find;

DELIMITER $$
CREATE PROCEDURE sp_model_roles_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `roles` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_roles_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_roles_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `roles` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_roles_create;

DELIMITER $$
CREATE PROCEDURE sp_model_roles_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `roles` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_roles_update;

DELIMITER $$
CREATE PROCEDURE sp_model_roles_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `roles` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_users_all;

DELIMITER $$
CREATE PROCEDURE sp_model_users_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `users`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_users_find;

DELIMITER $$
CREATE PROCEDURE sp_model_users_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `users` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_users_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_users_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `users` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_users_create;

DELIMITER $$
CREATE PROCEDURE sp_model_users_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `users` (`role_id`,`full_name`,`email`,`phone`,`password_hash`,`avatar`,`bio`,`status`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.role_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.role_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.role_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.full_name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.full_name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.full_name')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.email'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.email')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.email')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.phone'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.phone')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.phone')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.password_hash'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.password_hash')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.password_hash')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.avatar'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.avatar')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.avatar')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.bio'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.bio')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.bio')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, 'active'));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_users_update;

DELIMITER $$
CREATE PROCEDURE sp_model_users_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `users` SET `role_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.role_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.role_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.role_id')) END, `role_id`),
`full_name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.full_name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.full_name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.full_name')) END, `full_name`),
`email`=IF(JSON_CONTAINS_PATH(p_data,'one','$.email'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.email')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.email')) END, `email`),
`phone`=IF(JSON_CONTAINS_PATH(p_data,'one','$.phone'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.phone')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.phone')) END, `phone`),
`password_hash`=IF(JSON_CONTAINS_PATH(p_data,'one','$.password_hash'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.password_hash')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.password_hash')) END, `password_hash`),
`avatar`=IF(JSON_CONTAINS_PATH(p_data,'one','$.avatar'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.avatar')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.avatar')) END, `avatar`),
`bio`=IF(JSON_CONTAINS_PATH(p_data,'one','$.bio'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.bio')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.bio')) END, `bio`),
`status`=IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, `status`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_provinces_all;

DELIMITER $$
CREATE PROCEDURE sp_model_provinces_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `provinces`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_provinces_find;

DELIMITER $$
CREATE PROCEDURE sp_model_provinces_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `provinces` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_provinces_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_provinces_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `provinces` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_provinces_create;

DELIMITER $$
CREATE PROCEDURE sp_model_provinces_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `provinces` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_provinces_update;

DELIMITER $$
CREATE PROCEDURE sp_model_provinces_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `provinces` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_districts_all;

DELIMITER $$
CREATE PROCEDURE sp_model_districts_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `districts`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_districts_find;

DELIMITER $$
CREATE PROCEDURE sp_model_districts_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `districts` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_districts_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_districts_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `districts` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_districts_create;

DELIMITER $$
CREATE PROCEDURE sp_model_districts_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `districts` (`province_id`,`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_districts_update;

DELIMITER $$
CREATE PROCEDURE sp_model_districts_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `districts` SET `province_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, `province_id`),
`name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_wards_all;

DELIMITER $$
CREATE PROCEDURE sp_model_wards_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `wards`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_wards_find;

DELIMITER $$
CREATE PROCEDURE sp_model_wards_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `wards` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_wards_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_wards_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `wards` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_wards_create;

DELIMITER $$
CREATE PROCEDURE sp_model_wards_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `wards` (`province_id`,`district_id`,`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, 1),
IF(JSON_CONTAINS_PATH(p_data,'one','$.district_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.district_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.district_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_wards_update;

DELIMITER $$
CREATE PROCEDURE sp_model_wards_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `wards` SET `province_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, `province_id`),
`district_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.district_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.district_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.district_id')) END, `district_id`),
`name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_types_all;

DELIMITER $$
CREATE PROCEDURE sp_model_property_types_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_types`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_types_find;

DELIMITER $$
CREATE PROCEDURE sp_model_property_types_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_types` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_types_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_property_types_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `property_types` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_types_create;

DELIMITER $$
CREATE PROCEDURE sp_model_property_types_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `property_types` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_types_update;

DELIMITER $$
CREATE PROCEDURE sp_model_property_types_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `property_types` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_amenities_all;

DELIMITER $$
CREATE PROCEDURE sp_model_amenities_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `amenities`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_amenities_find;

DELIMITER $$
CREATE PROCEDURE sp_model_amenities_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `amenities` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_amenities_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_amenities_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `amenities` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_amenities_create;

DELIMITER $$
CREATE PROCEDURE sp_model_amenities_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `amenities` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_amenities_update;

DELIMITER $$
CREATE PROCEDURE sp_model_amenities_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `amenities` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_properties_all;

DELIMITER $$
CREATE PROCEDURE sp_model_properties_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `properties`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_properties_find;

DELIMITER $$
CREATE PROCEDURE sp_model_properties_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `properties` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_properties_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_properties_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `properties` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_properties_create;

DELIMITER $$
CREATE PROCEDURE sp_model_properties_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `properties` (`user_id`,`type_id`,`ward_id`,`title`,`slug`,`description`,`listing_type`,`price`,`deposit`,`area`,`bedrooms`,`bathrooms`,`direction`,`legal_status`,`furniture`,`pets_allowed`,`address_detail`,`latitude`,`longitude`,`status`,`reject_reason`,`is_verified`,`views`,`expired_at`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.type_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.type_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.type_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.ward_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.ward_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.ward_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.title'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.title')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.title')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.slug'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.slug')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.slug')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.description'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.description')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.description')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.listing_type'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.listing_type')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.listing_type')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.price'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.price')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.price')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.deposit'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.deposit')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.deposit')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.area'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.area')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.area')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.bedrooms'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.bedrooms')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.bedrooms')) END, 0),
IF(JSON_CONTAINS_PATH(p_data,'one','$.bathrooms'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.bathrooms')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.bathrooms')) END, 0),
IF(JSON_CONTAINS_PATH(p_data,'one','$.direction'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.direction')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.direction')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.legal_status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.legal_status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.legal_status')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.furniture'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.furniture')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.furniture')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.pets_allowed'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.pets_allowed')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.pets_allowed')) END, 0),
IF(JSON_CONTAINS_PATH(p_data,'one','$.address_detail'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.address_detail')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.address_detail')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.latitude'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.latitude')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.latitude')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.longitude'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.longitude')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.longitude')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, 'pending'),
IF(JSON_CONTAINS_PATH(p_data,'one','$.reject_reason'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.reject_reason')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.reject_reason')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.is_verified'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_verified')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_verified')) END, 0),
IF(JSON_CONTAINS_PATH(p_data,'one','$.views'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.views')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.views')) END, 0),
IF(JSON_CONTAINS_PATH(p_data,'one','$.expired_at'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.expired_at')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.expired_at')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_properties_update;

DELIMITER $$
CREATE PROCEDURE sp_model_properties_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `properties` SET `user_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, `user_id`),
`type_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.type_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.type_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.type_id')) END, `type_id`),
`ward_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.ward_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.ward_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.ward_id')) END, `ward_id`),
`title`=IF(JSON_CONTAINS_PATH(p_data,'one','$.title'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.title')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.title')) END, `title`),
`slug`=IF(JSON_CONTAINS_PATH(p_data,'one','$.slug'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.slug')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.slug')) END, `slug`),
`description`=IF(JSON_CONTAINS_PATH(p_data,'one','$.description'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.description')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.description')) END, `description`),
`listing_type`=IF(JSON_CONTAINS_PATH(p_data,'one','$.listing_type'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.listing_type')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.listing_type')) END, `listing_type`),
`price`=IF(JSON_CONTAINS_PATH(p_data,'one','$.price'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.price')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.price')) END, `price`),
`deposit`=IF(JSON_CONTAINS_PATH(p_data,'one','$.deposit'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.deposit')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.deposit')) END, `deposit`),
`area`=IF(JSON_CONTAINS_PATH(p_data,'one','$.area'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.area')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.area')) END, `area`),
`bedrooms`=IF(JSON_CONTAINS_PATH(p_data,'one','$.bedrooms'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.bedrooms')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.bedrooms')) END, `bedrooms`),
`bathrooms`=IF(JSON_CONTAINS_PATH(p_data,'one','$.bathrooms'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.bathrooms')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.bathrooms')) END, `bathrooms`),
`direction`=IF(JSON_CONTAINS_PATH(p_data,'one','$.direction'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.direction')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.direction')) END, `direction`),
`legal_status`=IF(JSON_CONTAINS_PATH(p_data,'one','$.legal_status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.legal_status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.legal_status')) END, `legal_status`),
`furniture`=IF(JSON_CONTAINS_PATH(p_data,'one','$.furniture'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.furniture')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.furniture')) END, `furniture`),
`pets_allowed`=IF(JSON_CONTAINS_PATH(p_data,'one','$.pets_allowed'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.pets_allowed')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.pets_allowed')) END, `pets_allowed`),
`address_detail`=IF(JSON_CONTAINS_PATH(p_data,'one','$.address_detail'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.address_detail')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.address_detail')) END, `address_detail`),
`latitude`=IF(JSON_CONTAINS_PATH(p_data,'one','$.latitude'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.latitude')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.latitude')) END, `latitude`),
`longitude`=IF(JSON_CONTAINS_PATH(p_data,'one','$.longitude'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.longitude')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.longitude')) END, `longitude`),
`status`=IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, `status`),
`reject_reason`=IF(JSON_CONTAINS_PATH(p_data,'one','$.reject_reason'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.reject_reason')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.reject_reason')) END, `reject_reason`),
`is_verified`=IF(JSON_CONTAINS_PATH(p_data,'one','$.is_verified'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_verified')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_verified')) END, `is_verified`),
`views`=IF(JSON_CONTAINS_PATH(p_data,'one','$.views'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.views')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.views')) END, `views`),
`expired_at`=IF(JSON_CONTAINS_PATH(p_data,'one','$.expired_at'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.expired_at')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.expired_at')) END, `expired_at`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_images_all;

DELIMITER $$
CREATE PROCEDURE sp_model_property_images_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_images`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_images_find;

DELIMITER $$
CREATE PROCEDURE sp_model_property_images_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_images` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_images_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_property_images_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `property_images` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_images_create;

DELIMITER $$
CREATE PROCEDURE sp_model_property_images_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `property_images` (`property_id`,`image_url`,`is_cover`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.image_url'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.image_url')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.image_url')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.is_cover'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_cover')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_cover')) END, 0));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_images_update;

DELIMITER $$
CREATE PROCEDURE sp_model_property_images_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `property_images` SET `property_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, `property_id`),
`image_url`=IF(JSON_CONTAINS_PATH(p_data,'one','$.image_url'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.image_url')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.image_url')) END, `image_url`),
`is_cover`=IF(JSON_CONTAINS_PATH(p_data,'one','$.is_cover'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_cover')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_cover')) END, `is_cover`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_amenities_all;

DELIMITER $$
CREATE PROCEDURE sp_model_property_amenities_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_amenities`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_property_amenities_create;

DELIMITER $$
CREATE PROCEDURE sp_model_property_amenities_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `property_amenities` (`property_id`,`amenity_id`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.amenity_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.amenity_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.amenity_id')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_saved_searches_all;

DELIMITER $$
CREATE PROCEDURE sp_model_saved_searches_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `saved_searches`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_saved_searches_find;

DELIMITER $$
CREATE PROCEDURE sp_model_saved_searches_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `saved_searches` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_saved_searches_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_saved_searches_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `saved_searches` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_saved_searches_create;

DELIMITER $$
CREATE PROCEDURE sp_model_saved_searches_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `saved_searches` (`user_id`,`name`,`filters`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.filters'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.filters')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.filters')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_saved_searches_update;

DELIMITER $$
CREATE PROCEDURE sp_model_saved_searches_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `saved_searches` SET `user_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, `user_id`),
`name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`),
`filters`=IF(JSON_CONTAINS_PATH(p_data,'one','$.filters'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.filters')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.filters')) END, `filters`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_favorites_all;

DELIMITER $$
CREATE PROCEDURE sp_model_favorites_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `favorites`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_favorites_create;

DELIMITER $$
CREATE PROCEDURE sp_model_favorites_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `favorites` (`user_id`,`property_id`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reviews_all;

DELIMITER $$
CREATE PROCEDURE sp_model_reviews_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reviews`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reviews_find;

DELIMITER $$
CREATE PROCEDURE sp_model_reviews_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reviews` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reviews_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_reviews_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `reviews` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reviews_create;

DELIMITER $$
CREATE PROCEDURE sp_model_reviews_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `reviews` (`property_id`,`user_id`,`rating`,`comment`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.rating'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.rating')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.rating')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.comment'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.comment')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.comment')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reviews_update;

DELIMITER $$
CREATE PROCEDURE sp_model_reviews_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `reviews` SET `property_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, `property_id`),
`user_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, `user_id`),
`rating`=IF(JSON_CONTAINS_PATH(p_data,'one','$.rating'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.rating')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.rating')) END, `rating`),
`comment`=IF(JSON_CONTAINS_PATH(p_data,'one','$.comment'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.comment')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.comment')) END, `comment`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_all;

DELIMITER $$
CREATE PROCEDURE sp_model_viewing_appointments_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `viewing_appointments`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_find;

DELIMITER $$
CREATE PROCEDURE sp_model_viewing_appointments_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `viewing_appointments` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_viewing_appointments_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `viewing_appointments` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_create;

DELIMITER $$
CREATE PROCEDURE sp_model_viewing_appointments_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `viewing_appointments` (`property_id`,`customer_id`,`scheduled_at`,`note`,`status`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.customer_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.customer_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.customer_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.scheduled_at'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.scheduled_at')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.scheduled_at')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.note'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.note')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.note')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, 'pending'));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_update;

DELIMITER $$
CREATE PROCEDURE sp_model_viewing_appointments_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `viewing_appointments` SET `property_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, `property_id`),
`customer_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.customer_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.customer_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.customer_id')) END, `customer_id`),
`scheduled_at`=IF(JSON_CONTAINS_PATH(p_data,'one','$.scheduled_at'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.scheduled_at')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.scheduled_at')) END, `scheduled_at`),
`note`=IF(JSON_CONTAINS_PATH(p_data,'one','$.note'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.note')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.note')) END, `note`),
`status`=IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, `status`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reports_all;

DELIMITER $$
CREATE PROCEDURE sp_model_reports_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reports`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reports_find;

DELIMITER $$
CREATE PROCEDURE sp_model_reports_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reports` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reports_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_reports_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `reports` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reports_create;

DELIMITER $$
CREATE PROCEDURE sp_model_reports_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `reports` (`property_id`,`reporter_id`,`reason`,`description`,`status`,`admin_notes`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.reporter_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.reporter_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.reporter_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.reason'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.reason')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.reason')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.description'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.description')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.description')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, 'open'),
IF(JSON_CONTAINS_PATH(p_data,'one','$.admin_notes'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.admin_notes')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.admin_notes')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_reports_update;

DELIMITER $$
CREATE PROCEDURE sp_model_reports_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `reports` SET `property_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, `property_id`),
`reporter_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.reporter_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.reporter_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.reporter_id')) END, `reporter_id`),
`reason`=IF(JSON_CONTAINS_PATH(p_data,'one','$.reason'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.reason')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.reason')) END, `reason`),
`description`=IF(JSON_CONTAINS_PATH(p_data,'one','$.description'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.description')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.description')) END, `description`),
`status`=IF(JSON_CONTAINS_PATH(p_data,'one','$.status'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.status')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.status')) END, `status`),
`admin_notes`=IF(JSON_CONTAINS_PATH(p_data,'one','$.admin_notes'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.admin_notes')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.admin_notes')) END, `admin_notes`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_password_resets_all;

DELIMITER $$
CREATE PROCEDURE sp_model_password_resets_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `password_resets`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_password_resets_create;

DELIMITER $$
CREATE PROCEDURE sp_model_password_resets_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `password_resets` (`email`,`token`,`expires_at`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.email'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.email')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.email')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.token'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.token')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.token')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.expires_at'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.expires_at')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.expires_at')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_notifications_all;

DELIMITER $$
CREATE PROCEDURE sp_model_notifications_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `notifications`;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_notifications_find;

DELIMITER $$
CREATE PROCEDURE sp_model_notifications_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `notifications` WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_notifications_delete;

DELIMITER $$
CREATE PROCEDURE sp_model_notifications_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `notifications` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_notifications_create;

DELIMITER $$
CREATE PROCEDURE sp_model_notifications_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `notifications` (`user_id`,`title`,`message`,`link`,`type`,`is_read`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.title'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.title')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.title')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.message'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.message')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.message')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.link'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.link')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.link')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.type'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.type')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.type')) END, 'info'),
IF(JSON_CONTAINS_PATH(p_data,'one','$.is_read'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_read')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_read')) END, 0));
SELECT LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_model_notifications_update;

DELIMITER $$
CREATE PROCEDURE sp_model_notifications_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `notifications` SET `user_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, `user_id`),
`title`=IF(JSON_CONTAINS_PATH(p_data,'one','$.title'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.title')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.title')) END, `title`),
`message`=IF(JSON_CONTAINS_PATH(p_data,'one','$.message'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.message')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.message')) END, `message`),
`link`=IF(JSON_CONTAINS_PATH(p_data,'one','$.link'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.link')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.link')) END, `link`),
`type`=IF(JSON_CONTAINS_PATH(p_data,'one','$.type'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.type')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.type')) END, `type`),
`is_read`=IF(JSON_CONTAINS_PATH(p_data,'one','$.is_read'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_read')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_read')) END, `is_read`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_profile_full;

DELIMITER $$
CREATE PROCEDURE sp_user_profile_full(IN p_id INT, IN p_name VARCHAR(100), IN p_phone VARCHAR(15), IN p_has_bio TINYINT, IN p_bio TEXT, IN p_has_avatar TINYINT, IN p_avatar VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
UPDATE users SET full_name=p_name,phone=p_phone,bio=IF(p_has_bio,p_bio,bio),avatar=IF(p_has_avatar,p_avatar,avatar) WHERE id=p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_ward_admin_list;

DELIMITER $$
CREATE PROCEDURE sp_ward_admin_list(IN p_province INT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id,w.name,w.province_id AS parent_id,pr.name AS parent_name,
NULL AS grand_id,NULL AS grand_name,NULL AS extra,(SELECT COUNT(*) FROM properties p WHERE p.ward_id=w.id) AS used
FROM wards w LEFT JOIN provinces pr ON pr.id=w.province_id
WHERE p_province=0 OR w.province_id=p_province ORDER BY pr.name,w.name LIMIT 300;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_ward_name_exists;

DELIMITER $$
CREATE PROCEDURE sp_ward_name_exists(IN p_name VARCHAR(100), IN p_parent INT, IN p_except INT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) AS total FROM wards WHERE BINARY name=p_name AND id<>p_except AND (p_parent IS NULL OR province_id=p_parent);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_location_wards_for_provinces;

DELIMITER $$
CREATE PROCEDURE sp_location_wards_for_provinces(IN p_ids TEXT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id AS ward_id,w.name AS ward_name,w.province_id,w.district_id,d.name AS district_name
FROM wards w LEFT JOIN districts d ON d.id=w.district_id WHERE FIND_IN_SET(w.province_id,p_ids)>0 ORDER BY w.name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_images_remove_ids;

DELIMITER $$
CREATE PROCEDURE sp_property_images_remove_ids(IN p_property INT, IN p_ids TEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM property_images WHERE property_id=p_property AND FIND_IN_SET(id,p_ids)>0;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_property_similar_full;

DELIMITER $$
CREATE PROCEDURE sp_property_similar_full(IN p_current INT, IN p_location INT, IN p_type INT, IN p_price DECIMAL(18,2), IN p_limit INT)
SQL SECURITY INVOKER
BEGIN
SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.direction,p.is_verified,p.latitude,p.longitude,p.created_at,p.address_detail,
t.id AS type_id,t.name AS type_name,w.id AS ward_id,w.name AS ward_name,d.id AS district_id,d.name AS district_name,pr.id AS province_id,pr.name AS province_name,
(SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
LEFT JOIN provinces pr ON pr.id=w.province_id LEFT JOIN districts d ON d.id=w.district_id
WHERE p.status='approved' AND p.id<>p_current
ORDER BY CASE WHEN (w.id=p_location OR d.id=p_location) AND p.type_id=p_type THEN 0
WHEN (w.id=p_location OR d.id=p_location) THEN 1 WHEN p.type_id=p_type THEN 2 ELSE 3 END,
ABS(p.price-p_price),p.created_at DESC,p.id DESC LIMIT p_limit;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_moderation_page;

DELIMITER $$
CREATE PROCEDURE sp_moderation_page(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
SQL SECURITY INVOKER
BEGIN
SELECT p.id,p.title,p.listing_type,p.price,p.area,p.address_detail,p.status,p.reject_reason,p.is_verified,p.created_at,u.full_name AS agent_name
FROM properties p JOIN users u ON u.id=p.user_id WHERE p_status='all' OR p.status=p_status
ORDER BY p.created_at DESC,p.id DESC LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_moderation_total;

DELIMITER $$
CREATE PROCEDURE sp_moderation_total(IN p_status VARCHAR(20))
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) AS total FROM properties WHERE p_status='all' OR status=p_status;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_exists;

DELIMITER $$
CREATE PROCEDURE sp_report_exists(IN p_property INT, IN p_user INT)
SQL SECURITY INVOKER
BEGIN
SELECT 1 FROM reports WHERE property_id=p_property AND reporter_id=p_user AND status IN ('open','pending') LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_submit_full;

DELIMITER $$
CREATE PROCEDURE sp_report_submit_full(IN p_property INT, IN p_user INT, IN p_reason VARCHAR(255), IN p_description TEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO reports(property_id,reporter_id,reason,description,status)
SELECT p.id,p_user,p_reason,p_description,'open' FROM properties p WHERE p.id=p_property AND p.status='approved'
AND NOT EXISTS(SELECT 1 FROM reports r WHERE r.property_id=p_property AND r.reporter_id=p_user AND r.status IN ('open','pending'));
SELECT IF(ROW_COUNT()>0,LAST_INSERT_ID(),0) AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_total_full;

DELIMITER $$
CREATE PROCEDURE sp_report_total_full(IN p_status VARCHAR(20), IN p_search TEXT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) AS total FROM reports r JOIN properties p ON p.id=r.property_id LEFT JOIN users u ON u.id=r.reporter_id LEFT JOIN users agent ON agent.id=p.user_id
WHERE (p_status IS NULL OR (p_status IN ('open','pending') AND r.status IN ('open','pending')) OR r.status=p_status)
AND (p_search='' OR p.title LIKE CONCAT('%',p_search,'%') OR r.reason LIKE CONCAT('%',p_search,'%') OR r.description LIKE CONCAT('%',p_search,'%') OR u.full_name LIKE CONCAT('%',p_search,'%'));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_page_full;

DELIMITER $$
CREATE PROCEDURE sp_report_page_full(IN p_status VARCHAR(20), IN p_search TEXT, IN p_limit INT, IN p_offset INT)
SQL SECURITY INVOKER
BEGIN
SELECT r.*,p.title AS property_title,p.price AS property_price,p.status AS property_status,p.user_id AS agent_id,
u.full_name AS reporter_name,u.email AS reporter_email,agent.full_name AS agent_name,agent.email AS agent_email FROM reports r JOIN properties p ON p.id=r.property_id LEFT JOIN users u ON u.id=r.reporter_id LEFT JOIN users agent ON agent.id=p.user_id
WHERE (p_status IS NULL OR (p_status IN ('open','pending') AND r.status IN ('open','pending')) OR r.status=p_status)
AND (p_search='' OR p.title LIKE CONCAT('%',p_search,'%') OR r.reason LIKE CONCAT('%',p_search,'%') OR r.description LIKE CONCAT('%',p_search,'%') OR u.full_name LIKE CONCAT('%',p_search,'%')) ORDER BY r.created_at DESC,r.id DESC LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_resolve_full;

DELIMITER $$
CREATE PROCEDURE sp_report_resolve_full(IN p_id INT, IN p_status VARCHAR(20), IN p_hide TINYINT, IN p_notes TEXT)
SQL SECURITY INVOKER
BEGIN
DECLARE v_property INT;
DECLARE v_status VARCHAR(20);
DECLARE v_affected INT DEFAULT 0;
SELECT property_id,status INTO v_property,v_status FROM reports WHERE id=p_id FOR UPDATE;
IF v_property IS NOT NULL AND v_status IN ('open','pending') AND p_status IN ('resolved','dismissed') THEN
UPDATE reports SET status=p_status,admin_notes=COALESCE(p_notes,admin_notes),updated_at=NOW() WHERE id=p_id;
SET v_affected=ROW_COUNT();
IF p_hide=1 AND p_status='resolved' THEN UPDATE properties SET status='hidden',updated_at=NOW() WHERE id=v_property; END IF;
END IF;
SELECT v_affected AS affected;
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

DROP PROCEDURE IF EXISTS sp_property_map;

DELIMITER $$
CREATE PROCEDURE sp_property_map(
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
  IN p_sort_by VARCHAR(20))
SQL SECURITY INVOKER
BEGIN
SELECT p.id, p.title, p.slug, p.address_detail, p.status, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
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
  LEFT JOIN districts d ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = w.province_id
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
    p.created_at DESC, p.id DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_admin_controller_reports_1()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_admin_controller_reports_2()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports WHERE status IN ('open', 'pending');
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_3;

DELIMITER $$
CREATE PROCEDURE sp_rt_admin_controller_reports_3()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports WHERE status = 'resolved';
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_4;

DELIMITER $$
CREATE PROCEDURE sp_rt_admin_controller_reports_4()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports WHERE status = 'dismissed';
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_agent_controller_validate_listing_input_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_agent_controller_validate_listing_input_1(IN p_1 BIGINT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM wards WHERE province_id = p_1 AND name = p_2 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_agent_controller_validate_listing_input_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_agent_controller_validate_listing_input_2(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO wards (province_id, district_id, name) VALUES (p_1, NULL, p_2);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_report_controller_submit_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_report_controller_submit_1()
SQL SECURITY INVOKER
BEGIN
SELECT u.id, u.email FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin';
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_admin_stats_properties_by_province_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_admin_stats_properties_by_province_1()
SQL SECURITY INVOKER
BEGIN
SELECT pv.id AS province_id,
                    pv.name AS province_name,
                    COUNT(pr.id) AS total,
                    COUNT(pr.id) AS approved,
                    0 AS pending,
                    0 AS hidden
             FROM properties pr
             JOIN wards w ON w.id = pr.ward_id
             JOIN provinces pv ON pv.id = w.province_id
             WHERE pr.status = 'approved'
             GROUP BY pv.id, pv.name
             ORDER BY approved DESC, pv.name ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_appointment_find_with_property_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_appointment_find_with_property_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT va.*, p.title AS property_title, p.user_id AS agent_id
             FROM viewing_appointments va
             JOIN properties p ON p.id = va.property_id
             WHERE va.id = p_1 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_provinces_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_provinces_1()
SQL SECURITY INVOKER
BEGIN
SELECT id, name FROM provinces ORDER BY CASE WHEN id IN (1, 2, 3) THEN id ELSE 999 END, name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_districts_by_province_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_districts_by_province_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name FROM districts WHERE province_id = p_1 ORDER BY name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_wards_by_district_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_wards_by_district_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name FROM wards
             WHERE district_id = p_1
               AND name NOT REGEXP "^(Phường|Xã|Thị trấn)[[:space:]]+[0-9]+"
             ORDER BY name ASC, id ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_wards_by_province_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_wards_by_province_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name, province_id
             FROM wards
             WHERE province_id = p_1
             ORDER BY name ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_is_valid_ward_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_is_valid_ward_1(IN p_1 BIGINT, IN p_2 BIGINT, IN p_3 BIGINT, IN p_4 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id FROM wards w
                 WHERE w.id = p_1 AND w.province_id = p_2 AND (w.district_id = p_3 OR p_4 = 0);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_is_valid_ward_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_is_valid_ward_2(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM wards WHERE id = p_1 AND province_id = p_2;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_search_administrative_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_search_administrative_1()
SQL SECURITY INVOKER
BEGIN
SELECT d.id AS district_id, d.name AS district_name, p.id AS province_id, p.name AS province_name
                    FROM districts d
                    JOIN provinces p ON p.id = d.province_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_location_search_administrative_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_location_search_administrative_2(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id AS ward_id, w.name AS ward_name,
                               w.province_id, p.name AS province_name,
                               d.id AS district_id, d.name AS district_name
                        FROM wards w
                        JOIN provinces p ON p.id = w.province_id
                        LEFT JOIN districts d ON d.id = w.district_id
                        WHERE REPLACE(REPLACE(LOWER(w.name), 'Đ', 'd'), 'đ', 'd') LIKE p_1
                          AND w.name NOT REGEXP '^(Phường|Xã|Thị trấn)[[:space:]]+[0-9]+'
                        LIMIT 150;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_create_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_create_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT, IN p_3 LONGTEXT, IN p_4 LONGTEXT, IN p_5 LONGTEXT, IN p_6 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at)
             VALUES (p_1, p_2, p_3, p_4, p_5, 0, p_6);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_get_unread_for_user_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_get_unread_for_user_1(IN p_1 BIGINT, IN v_dynamic_1 INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications
                 WHERE user_id = p_1 AND is_read = 0
                 ORDER BY created_at DESC, id DESC
                 LIMIT v_dynamic_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_count_unread_for_user_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_count_unread_for_user_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM notifications WHERE user_id = p_1 AND is_read = 0;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_get_all_for_user_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_get_all_for_user_1(IN p_1 BIGINT, IN v_dynamic_1 INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications
                 WHERE user_id = p_1
                 ORDER BY is_read ASC, created_at DESC, id DESC
                 LIMIT v_dynamic_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_find_for_user_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_find_for_user_1(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications WHERE id = p_1 AND user_id = p_2 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_mark_as_read_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_mark_as_read_1(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE notifications SET is_read = 1 WHERE id = p_1 AND user_id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_mark_as_read_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_mark_as_read_2(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE notifications SET is_read = 1 WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_mark_all_as_read_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_mark_all_as_read_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE notifications SET is_read = 1 WHERE user_id = p_1 AND is_read = 0;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_find_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_find_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications WHERE id = p_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_notification_delete_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_notification_delete_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM notifications WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_renew_owned_listing_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_renew_owned_listing_1(IN p_1 BIGINT, IN p_2 BIGINT, IN v_dynamic_1 INT, IN v_dynamic_2 INT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties
             SET expired_at = CASE
                 WHEN expired_at IS NOT NULL AND expired_at > NOW() THEN DATE_ADD(expired_at, INTERVAL v_dynamic_1 DAY)
                 ELSE DATE_ADD(NOW(), INTERVAL v_dynamic_2 DAY)
             END
             WHERE id = p_1 AND user_id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_find_owned_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_find_owned_1(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT p.*, w.province_id, w.district_id, pr.name AS province_name, w.name AS ward_name
             FROM properties p
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN provinces pr ON pr.id = w.province_id
             LEFT JOIN districts d ON d.id = w.district_id
             WHERE p.id = p_1 AND p.user_id = p_2;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_amenity_ids_for_listing_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_amenity_ids_for_listing_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT amenity_id FROM property_amenities WHERE property_id = p_1 ORDER BY amenity_id ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_sync_amenities_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_sync_amenities_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM property_amenities WHERE property_id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_sync_amenities_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_sync_amenities_2(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO property_amenities (property_id, amenity_id) VALUES (p_1, p_2);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_1(IN p_1 BIGINT, IN p_2 BIGINT, IN p_3 LONGTEXT, IN p_4 LONGTEXT, IN p_5 LONGTEXT, IN p_6 LONGTEXT, IN p_7 LONGTEXT, IN p_8 LONGTEXT, IN p_9 LONGTEXT, IN p_10 LONGTEXT, IN p_11 LONGTEXT, IN p_12 LONGTEXT, IN p_13 LONGTEXT, IN p_14 LONGTEXT, IN p_15 LONGTEXT, IN p_16 LONGTEXT, IN p_17 LONGTEXT, IN p_18 BIGINT, IN p_19 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties
                 SET type_id = p_1, ward_id = p_2, title = p_3, description = p_4, listing_type = p_5, price = p_6, deposit = p_7,
                     area = p_8, bedrooms = p_9, bathrooms = p_10, direction = p_11, legal_status = p_12, furniture = p_13,
                     pets_allowed = p_14, address_detail = p_15, latitude = p_16, longitude = p_17,
                     status = 'pending', reject_reason = NULL
                 WHERE id = p_18 AND user_id = p_19;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_2(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM property_images WHERE id = p_1 AND property_id = p_2;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_3;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_3(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE property_images SET is_cover = 0 WHERE property_id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_4;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_4(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE property_images SET is_cover = 1 WHERE id = p_1 AND property_id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_5;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_5(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM property_images WHERE property_id = p_1 AND is_cover = 1 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_6;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_6(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM property_images WHERE property_id = p_1 ORDER BY id ASC LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_7;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_update_owned_listing_7(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE property_images SET is_cover = 1 WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_toggle_verified_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_toggle_verified_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, user_id, title, is_verified FROM properties WHERE id = p_1 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_toggle_verified_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_toggle_verified_2(IN p_1 LONGTEXT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET is_verified = p_1 WHERE id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, user_id, title, status FROM properties WHERE id = p_1 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_2(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM properties WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_3;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_3(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'approved', reject_reason = NULL, expired_at = COALESCE(expired_at, DATE_ADD(NOW(), INTERVAL 30 DAY)) WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_4;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_4(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'rejected' WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_5;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_5(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'hidden' WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_6;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_6(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'pending' WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_7;

DELIMITER $$
CREATE PROCEDURE sp_rt_property_moderation_update_status_7(IN p_1 LONGTEXT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = p_1 WHERE id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_province_child_count_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_province_child_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM wards WHERE province_id = p_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_province_usage_count_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_province_usage_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.province_id = p_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_province_admin_list_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_province_admin_list_1()
SQL SECURITY INVOKER
BEGIN
SELECT pr.id, pr.name, NULL AS parent_id, NULL AS parent_name,
             (SELECT COUNT(*) FROM wards w WHERE w.province_id = pr.id) AS extra,
             (SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.province_id = pr.id) AS used
             FROM provinces pr
             ORDER BY pr.name;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_report_get_detail_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_report_get_detail_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT r.*,
                    p.title AS property_title, p.price AS property_price, p.status AS property_status, p.user_id AS agent_id,
                    u.full_name AS reporter_name, u.email AS reporter_email,
                    agent.full_name AS agent_name, agent.email AS agent_email
             FROM reports r
             JOIN properties p ON p.id = r.property_id
             LEFT JOIN users u ON u.id = r.reporter_id
             LEFT JOIN users agent ON agent.id = p.user_id
             WHERE r.id = p_1 LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_user_create_password_reset_token_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_create_password_reset_token_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE email = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_user_create_password_reset_token_2;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_create_password_reset_token_2(IN p_1 LONGTEXT, IN p_2 LONGTEXT, IN p_3 LONGTEXT, IN p_4 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (p_1, p_2, p_3, p_4);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_user_find_valid_password_reset_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_find_valid_password_reset_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM password_resets WHERE token = p_1 AND expires_at >= p_2 ORDER BY created_at DESC LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_user_delete_password_resets_for_email_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_delete_password_resets_for_email_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE email = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_user_delete_password_reset_token_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_delete_password_reset_token_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE token = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_ward_usage_count_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_usage_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM properties WHERE ward_id = p_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_ward_find_by_province_id_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_find_by_province_id_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT MIN(id) AS id, MIN(name) AS name, MIN(province_id) AS province_id FROM wards WHERE province_id = p_1 GROUP BY BINARY name ORDER BY MIN(name) ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_ward_find_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_find_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name, province_id, district_id FROM wards WHERE id = p_1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_ward_create_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_create_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO wards (name, province_id, district_id) VALUES (p_1, p_2, NULL);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_ward_update_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_update_1(IN p_1 LONGTEXT, IN p_2 BIGINT, IN p_3 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE wards SET name = p_1, province_id = p_2 WHERE id = p_3;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_rt_ward_delete_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_delete_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM wards WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$
DELIMITER ;
