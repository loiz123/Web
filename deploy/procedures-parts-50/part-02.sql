-- Part 2/5: 50 procedures. Select the target database before import.
SET NAMES utf8mb4;

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

DROP PROCEDURE IF EXISTS sp_report_exists;

DELIMITER $$
CREATE PROCEDURE sp_report_exists(IN p_property INT, IN p_user INT)
SQL SECURITY INVOKER
BEGIN
SELECT 1 FROM reports WHERE property_id=p_property AND reporter_id=p_user AND status IN ('open','pending') LIMIT 1;
END$$
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
