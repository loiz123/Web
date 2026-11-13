-- Part 2/2: 25 procedures. Select the target database before import.
SET NAMES utf8mb4;

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

DROP PROCEDURE IF EXISTS sp_rt_report_controller_submit_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_report_controller_submit_1()
SQL SECURITY INVOKER
BEGIN
SELECT u.id, u.email FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin';
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

DROP PROCEDURE IF EXISTS sp_rt_user_delete_password_reset_token_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_delete_password_reset_token_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE token = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
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

DROP PROCEDURE IF EXISTS sp_rt_user_find_valid_password_reset_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_user_find_valid_password_reset_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM password_resets WHERE token = p_1 AND expires_at >= p_2 ORDER BY created_at DESC LIMIT 1;
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

DROP PROCEDURE IF EXISTS sp_rt_ward_delete_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_delete_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM wards WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
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

DROP PROCEDURE IF EXISTS sp_rt_ward_find_by_province_id_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_find_by_province_id_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT MIN(id) AS id, MIN(name) AS name, MIN(province_id) AS province_id FROM wards WHERE province_id = p_1 GROUP BY BINARY name ORDER BY MIN(name) ASC;
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

DROP PROCEDURE IF EXISTS sp_rt_ward_usage_count_1;

DELIMITER $$
CREATE PROCEDURE sp_rt_ward_usage_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM properties WHERE ward_id = p_1;
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
