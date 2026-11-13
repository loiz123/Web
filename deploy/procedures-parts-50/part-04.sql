-- Part 4/5: 50 procedures. Select the target database before import.
SET NAMES utf8mb4;

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
