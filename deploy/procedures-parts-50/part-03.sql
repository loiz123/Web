-- Part 3/5: 50 procedures. Select the target database before import.
SET NAMES utf8mb4;

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
