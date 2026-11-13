-- Runtime migration: stored procedures only. Select the target database before importing.
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_model_roles_all$$
CREATE PROCEDURE sp_model_roles_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `roles`;
END$$

DROP PROCEDURE IF EXISTS sp_model_roles_find$$
CREATE PROCEDURE sp_model_roles_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `roles` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_roles_delete$$
CREATE PROCEDURE sp_model_roles_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `roles` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_roles_create$$
CREATE PROCEDURE sp_model_roles_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `roles` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_roles_update$$
CREATE PROCEDURE sp_model_roles_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `roles` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_users_all$$
CREATE PROCEDURE sp_model_users_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `users`;
END$$

DROP PROCEDURE IF EXISTS sp_model_users_find$$
CREATE PROCEDURE sp_model_users_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `users` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_users_delete$$
CREATE PROCEDURE sp_model_users_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `users` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_users_create$$
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

DROP PROCEDURE IF EXISTS sp_model_users_update$$
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

DROP PROCEDURE IF EXISTS sp_model_provinces_all$$
CREATE PROCEDURE sp_model_provinces_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `provinces`;
END$$

DROP PROCEDURE IF EXISTS sp_model_provinces_find$$
CREATE PROCEDURE sp_model_provinces_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `provinces` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_provinces_delete$$
CREATE PROCEDURE sp_model_provinces_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `provinces` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_provinces_create$$
CREATE PROCEDURE sp_model_provinces_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `provinces` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_provinces_update$$
CREATE PROCEDURE sp_model_provinces_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `provinces` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_districts_all$$
CREATE PROCEDURE sp_model_districts_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `districts`;
END$$

DROP PROCEDURE IF EXISTS sp_model_districts_find$$
CREATE PROCEDURE sp_model_districts_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `districts` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_districts_delete$$
CREATE PROCEDURE sp_model_districts_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `districts` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_districts_create$$
CREATE PROCEDURE sp_model_districts_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `districts` (`province_id`,`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_districts_update$$
CREATE PROCEDURE sp_model_districts_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `districts` SET `province_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, `province_id`),
`name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_wards_all$$
CREATE PROCEDURE sp_model_wards_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `wards`;
END$$

DROP PROCEDURE IF EXISTS sp_model_wards_find$$
CREATE PROCEDURE sp_model_wards_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `wards` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_wards_delete$$
CREATE PROCEDURE sp_model_wards_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `wards` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_wards_create$$
CREATE PROCEDURE sp_model_wards_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `wards` (`province_id`,`district_id`,`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, 1),
IF(JSON_CONTAINS_PATH(p_data,'one','$.district_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.district_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.district_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_wards_update$$
CREATE PROCEDURE sp_model_wards_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `wards` SET `province_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.province_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.province_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.province_id')) END, `province_id`),
`district_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.district_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.district_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.district_id')) END, `district_id`),
`name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_types_all$$
CREATE PROCEDURE sp_model_property_types_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_types`;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_types_find$$
CREATE PROCEDURE sp_model_property_types_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_types` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_types_delete$$
CREATE PROCEDURE sp_model_property_types_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `property_types` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_types_create$$
CREATE PROCEDURE sp_model_property_types_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `property_types` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_types_update$$
CREATE PROCEDURE sp_model_property_types_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `property_types` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_amenities_all$$
CREATE PROCEDURE sp_model_amenities_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `amenities`;
END$$

DROP PROCEDURE IF EXISTS sp_model_amenities_find$$
CREATE PROCEDURE sp_model_amenities_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `amenities` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_amenities_delete$$
CREATE PROCEDURE sp_model_amenities_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `amenities` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_amenities_create$$
CREATE PROCEDURE sp_model_amenities_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `amenities` (`name`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_amenities_update$$
CREATE PROCEDURE sp_model_amenities_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `amenities` SET `name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_properties_all$$
CREATE PROCEDURE sp_model_properties_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `properties`;
END$$

DROP PROCEDURE IF EXISTS sp_model_properties_find$$
CREATE PROCEDURE sp_model_properties_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `properties` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_properties_delete$$
CREATE PROCEDURE sp_model_properties_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `properties` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_properties_create$$
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

DROP PROCEDURE IF EXISTS sp_model_properties_update$$
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

DROP PROCEDURE IF EXISTS sp_model_property_images_all$$
CREATE PROCEDURE sp_model_property_images_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_images`;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_images_find$$
CREATE PROCEDURE sp_model_property_images_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_images` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_images_delete$$
CREATE PROCEDURE sp_model_property_images_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `property_images` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_images_create$$
CREATE PROCEDURE sp_model_property_images_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `property_images` (`property_id`,`image_url`,`is_cover`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.image_url'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.image_url')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.image_url')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.is_cover'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_cover')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_cover')) END, 0));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_images_update$$
CREATE PROCEDURE sp_model_property_images_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `property_images` SET `property_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, `property_id`),
`image_url`=IF(JSON_CONTAINS_PATH(p_data,'one','$.image_url'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.image_url')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.image_url')) END, `image_url`),
`is_cover`=IF(JSON_CONTAINS_PATH(p_data,'one','$.is_cover'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.is_cover')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.is_cover')) END, `is_cover`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_amenities_all$$
CREATE PROCEDURE sp_model_property_amenities_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `property_amenities`;
END$$

DROP PROCEDURE IF EXISTS sp_model_property_amenities_create$$
CREATE PROCEDURE sp_model_property_amenities_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `property_amenities` (`property_id`,`amenity_id`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.amenity_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.amenity_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.amenity_id')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_saved_searches_all$$
CREATE PROCEDURE sp_model_saved_searches_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `saved_searches`;
END$$

DROP PROCEDURE IF EXISTS sp_model_saved_searches_find$$
CREATE PROCEDURE sp_model_saved_searches_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `saved_searches` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_saved_searches_delete$$
CREATE PROCEDURE sp_model_saved_searches_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `saved_searches` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_saved_searches_create$$
CREATE PROCEDURE sp_model_saved_searches_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `saved_searches` (`user_id`,`name`,`filters`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.filters'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.filters')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.filters')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_saved_searches_update$$
CREATE PROCEDURE sp_model_saved_searches_update(IN p_id INT, IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
UPDATE `saved_searches` SET `user_id`=IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, `user_id`),
`name`=IF(JSON_CONTAINS_PATH(p_data,'one','$.name'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.name')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.name')) END, `name`),
`filters`=IF(JSON_CONTAINS_PATH(p_data,'one','$.filters'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.filters')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.filters')) END, `filters`) WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_favorites_all$$
CREATE PROCEDURE sp_model_favorites_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `favorites`;
END$$

DROP PROCEDURE IF EXISTS sp_model_favorites_create$$
CREATE PROCEDURE sp_model_favorites_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `favorites` (`user_id`,`property_id`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.user_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.user_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.user_id')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.property_id'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.property_id')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.property_id')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_reviews_all$$
CREATE PROCEDURE sp_model_reviews_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reviews`;
END$$

DROP PROCEDURE IF EXISTS sp_model_reviews_find$$
CREATE PROCEDURE sp_model_reviews_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reviews` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_reviews_delete$$
CREATE PROCEDURE sp_model_reviews_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `reviews` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_reviews_create$$
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

DROP PROCEDURE IF EXISTS sp_model_reviews_update$$
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

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_all$$
CREATE PROCEDURE sp_model_viewing_appointments_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `viewing_appointments`;
END$$

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_find$$
CREATE PROCEDURE sp_model_viewing_appointments_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `viewing_appointments` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_delete$$
CREATE PROCEDURE sp_model_viewing_appointments_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `viewing_appointments` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_create$$
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

DROP PROCEDURE IF EXISTS sp_model_viewing_appointments_update$$
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

DROP PROCEDURE IF EXISTS sp_model_reports_all$$
CREATE PROCEDURE sp_model_reports_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reports`;
END$$

DROP PROCEDURE IF EXISTS sp_model_reports_find$$
CREATE PROCEDURE sp_model_reports_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `reports` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_reports_delete$$
CREATE PROCEDURE sp_model_reports_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `reports` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_reports_create$$
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

DROP PROCEDURE IF EXISTS sp_model_reports_update$$
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

DROP PROCEDURE IF EXISTS sp_model_password_resets_all$$
CREATE PROCEDURE sp_model_password_resets_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `password_resets`;
END$$

DROP PROCEDURE IF EXISTS sp_model_password_resets_create$$
CREATE PROCEDURE sp_model_password_resets_create(IN p_data LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_data) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid model data'; END IF;
INSERT INTO `password_resets` (`email`,`token`,`expires_at`) VALUES (IF(JSON_CONTAINS_PATH(p_data,'one','$.email'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.email')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.email')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.token'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.token')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.token')) END, NULL),
IF(JSON_CONTAINS_PATH(p_data,'one','$.expires_at'), CASE WHEN JSON_TYPE(JSON_EXTRACT(p_data,'$.expires_at')) = 'NULL' THEN NULL ELSE JSON_UNQUOTE(JSON_EXTRACT(p_data,'$.expires_at')) END, NULL));
SELECT LAST_INSERT_ID() AS id;
END$$

DROP PROCEDURE IF EXISTS sp_model_notifications_all$$
CREATE PROCEDURE sp_model_notifications_all()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `notifications`;
END$$

DROP PROCEDURE IF EXISTS sp_model_notifications_find$$
CREATE PROCEDURE sp_model_notifications_find(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM `notifications` WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_model_notifications_delete$$
CREATE PROCEDURE sp_model_notifications_delete(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM `notifications` WHERE id=p_id;
SELECT ROW_COUNT() AS affected;
END$$

DROP PROCEDURE IF EXISTS sp_model_notifications_create$$
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

DROP PROCEDURE IF EXISTS sp_model_notifications_update$$
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

DROP PROCEDURE IF EXISTS sp_user_profile_full$$
CREATE PROCEDURE sp_user_profile_full(IN p_id INT, IN p_name VARCHAR(100), IN p_phone VARCHAR(15), IN p_has_bio TINYINT, IN p_bio TEXT, IN p_has_avatar TINYINT, IN p_avatar VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
UPDATE users SET full_name=p_name,phone=p_phone,bio=IF(p_has_bio,p_bio,bio),avatar=IF(p_has_avatar,p_avatar,avatar) WHERE id=p_id;
END$$

DROP PROCEDURE IF EXISTS sp_ward_admin_list$$
CREATE PROCEDURE sp_ward_admin_list(IN p_province INT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id,w.name,w.province_id AS parent_id,pr.name AS parent_name,
NULL AS grand_id,NULL AS grand_name,NULL AS extra,(SELECT COUNT(*) FROM properties p WHERE p.ward_id=w.id) AS used
FROM wards w LEFT JOIN provinces pr ON pr.id=w.province_id
WHERE p_province=0 OR w.province_id=p_province ORDER BY pr.name,w.name LIMIT 300;
END$$

DROP PROCEDURE IF EXISTS sp_ward_name_exists$$
CREATE PROCEDURE sp_ward_name_exists(IN p_name VARCHAR(100), IN p_parent INT, IN p_except INT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) AS total FROM wards WHERE BINARY name=p_name AND id<>p_except AND (p_parent IS NULL OR province_id=p_parent);
END$$

DROP PROCEDURE IF EXISTS sp_location_wards_for_provinces$$
CREATE PROCEDURE sp_location_wards_for_provinces(IN p_ids TEXT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id AS ward_id,w.name AS ward_name,w.province_id,w.district_id,d.name AS district_name
FROM wards w LEFT JOIN districts d ON d.id=w.district_id WHERE FIND_IN_SET(w.province_id,p_ids)>0 ORDER BY w.name;
END$$

DROP PROCEDURE IF EXISTS sp_property_images_remove_ids$$
CREATE PROCEDURE sp_property_images_remove_ids(IN p_property INT, IN p_ids TEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM property_images WHERE property_id=p_property AND FIND_IN_SET(id,p_ids)>0;
END$$

DROP PROCEDURE IF EXISTS sp_property_similar_full$$
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

DROP PROCEDURE IF EXISTS sp_moderation_page$$
CREATE PROCEDURE sp_moderation_page(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
SQL SECURITY INVOKER
BEGIN
SELECT p.id,p.title,p.listing_type,p.price,p.area,p.address_detail,p.status,p.reject_reason,p.is_verified,p.created_at,u.full_name AS agent_name
FROM properties p JOIN users u ON u.id=p.user_id WHERE p_status='all' OR p.status=p_status
ORDER BY p.created_at DESC,p.id DESC LIMIT p_limit OFFSET p_offset;
END$$

DROP PROCEDURE IF EXISTS sp_moderation_total$$
CREATE PROCEDURE sp_moderation_total(IN p_status VARCHAR(20))
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) AS total FROM properties WHERE p_status='all' OR status=p_status;
END$$

DROP PROCEDURE IF EXISTS sp_report_exists$$
CREATE PROCEDURE sp_report_exists(IN p_property INT, IN p_user INT)
SQL SECURITY INVOKER
BEGIN
SELECT 1 FROM reports WHERE property_id=p_property AND reporter_id=p_user AND status IN ('open','pending') LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_report_submit_full$$
CREATE PROCEDURE sp_report_submit_full(IN p_property INT, IN p_user INT, IN p_reason VARCHAR(255), IN p_description TEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO reports(property_id,reporter_id,reason,description,status)
SELECT p.id,p_user,p_reason,p_description,'open' FROM properties p WHERE p.id=p_property AND p.status='approved'
AND NOT EXISTS(SELECT 1 FROM reports r WHERE r.property_id=p_property AND r.reporter_id=p_user AND r.status IN ('open','pending'));
SELECT IF(ROW_COUNT()>0,LAST_INSERT_ID(),0) AS id;
END$$

DROP PROCEDURE IF EXISTS sp_report_total_full$$
CREATE PROCEDURE sp_report_total_full(IN p_status VARCHAR(20), IN p_search TEXT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) AS total FROM reports r JOIN properties p ON p.id=r.property_id LEFT JOIN users u ON u.id=r.reporter_id LEFT JOIN users agent ON agent.id=p.user_id
WHERE (p_status IS NULL OR (p_status IN ('open','pending') AND r.status IN ('open','pending')) OR r.status=p_status)
AND (p_search='' OR p.title LIKE CONCAT('%',p_search,'%') OR r.reason LIKE CONCAT('%',p_search,'%') OR r.description LIKE CONCAT('%',p_search,'%') OR u.full_name LIKE CONCAT('%',p_search,'%'));
END$$

DROP PROCEDURE IF EXISTS sp_report_page_full$$
CREATE PROCEDURE sp_report_page_full(IN p_status VARCHAR(20), IN p_search TEXT, IN p_limit INT, IN p_offset INT)
SQL SECURITY INVOKER
BEGIN
SELECT r.*,p.title AS property_title,p.price AS property_price,p.status AS property_status,p.user_id AS agent_id,
u.full_name AS reporter_name,u.email AS reporter_email,agent.full_name AS agent_name,agent.email AS agent_email FROM reports r JOIN properties p ON p.id=r.property_id LEFT JOIN users u ON u.id=r.reporter_id LEFT JOIN users agent ON agent.id=p.user_id
WHERE (p_status IS NULL OR (p_status IN ('open','pending') AND r.status IN ('open','pending')) OR r.status=p_status)
AND (p_search='' OR p.title LIKE CONCAT('%',p_search,'%') OR r.reason LIKE CONCAT('%',p_search,'%') OR r.description LIKE CONCAT('%',p_search,'%') OR u.full_name LIKE CONCAT('%',p_search,'%')) ORDER BY r.created_at DESC,r.id DESC LIMIT p_limit OFFSET p_offset;
END$$

DROP PROCEDURE IF EXISTS sp_report_resolve_full$$
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

DROP PROCEDURE IF EXISTS sp_dashboard_counts$$
CREATE PROCEDURE sp_dashboard_counts()
SQL SECURITY INVOKER
BEGIN
SELECT (SELECT COUNT(*) FROM users) AS users_total,
(SELECT COUNT(*) FROM properties) AS properties_total,
(SELECT COUNT(*) FROM properties WHERE status='approved') AS approved_total,
(SELECT COUNT(*) FROM properties WHERE status='pending') AS pending_total,
(SELECT COUNT(*) FROM reports WHERE status IN ('open','pending')) AS reports_total;
END$$

DROP PROCEDURE IF EXISTS sp_property_map$$
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

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_1$$
CREATE PROCEDURE sp_rt_admin_controller_reports_1()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports;
END$$

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_2$$
CREATE PROCEDURE sp_rt_admin_controller_reports_2()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports WHERE status IN ('open', 'pending');
END$$

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_3$$
CREATE PROCEDURE sp_rt_admin_controller_reports_3()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports WHERE status = 'resolved';
END$$

DROP PROCEDURE IF EXISTS sp_rt_admin_controller_reports_4$$
CREATE PROCEDURE sp_rt_admin_controller_reports_4()
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM reports WHERE status = 'dismissed';
END$$

DROP PROCEDURE IF EXISTS sp_rt_agent_controller_validate_listing_input_1$$
CREATE PROCEDURE sp_rt_agent_controller_validate_listing_input_1(IN p_1 BIGINT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM wards WHERE province_id = p_1 AND name = p_2 LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_agent_controller_validate_listing_input_2$$
CREATE PROCEDURE sp_rt_agent_controller_validate_listing_input_2(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO wards (province_id, district_id, name) VALUES (p_1, NULL, p_2);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_report_controller_submit_1$$
CREATE PROCEDURE sp_rt_report_controller_submit_1()
SQL SECURITY INVOKER
BEGIN
SELECT u.id, u.email FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin';
END$$

DROP PROCEDURE IF EXISTS sp_rt_admin_stats_properties_by_province_1$$
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

DROP PROCEDURE IF EXISTS sp_rt_appointment_find_with_property_1$$
CREATE PROCEDURE sp_rt_appointment_find_with_property_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT va.*, p.title AS property_title, p.user_id AS agent_id
             FROM viewing_appointments va
             JOIN properties p ON p.id = va.property_id
             WHERE va.id = p_1 LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_provinces_1$$
CREATE PROCEDURE sp_rt_location_provinces_1()
SQL SECURITY INVOKER
BEGIN
SELECT id, name FROM provinces ORDER BY CASE WHEN id IN (1, 2, 3) THEN id ELSE 999 END, name;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_districts_by_province_1$$
CREATE PROCEDURE sp_rt_location_districts_by_province_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name FROM districts WHERE province_id = p_1 ORDER BY name;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_wards_by_district_1$$
CREATE PROCEDURE sp_rt_location_wards_by_district_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name FROM wards
             WHERE district_id = p_1
               AND name NOT REGEXP "^(Phường|Xã|Thị trấn)[[:space:]]+[0-9]+"
             ORDER BY name ASC, id ASC;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_wards_by_province_1$$
CREATE PROCEDURE sp_rt_location_wards_by_province_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name, province_id
             FROM wards
             WHERE province_id = p_1
             ORDER BY name ASC;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_is_valid_ward_1$$
CREATE PROCEDURE sp_rt_location_is_valid_ward_1(IN p_1 BIGINT, IN p_2 BIGINT, IN p_3 BIGINT, IN p_4 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT w.id FROM wards w
                 WHERE w.id = p_1 AND w.province_id = p_2 AND (w.district_id = p_3 OR p_4 = 0);
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_is_valid_ward_2$$
CREATE PROCEDURE sp_rt_location_is_valid_ward_2(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM wards WHERE id = p_1 AND province_id = p_2;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_search_administrative_1$$
CREATE PROCEDURE sp_rt_location_search_administrative_1()
SQL SECURITY INVOKER
BEGIN
SELECT d.id AS district_id, d.name AS district_name, p.id AS province_id, p.name AS province_name
                    FROM districts d
                    JOIN provinces p ON p.id = d.province_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_location_search_administrative_2$$
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

DROP PROCEDURE IF EXISTS sp_rt_notification_create_1$$
CREATE PROCEDURE sp_rt_notification_create_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT, IN p_3 LONGTEXT, IN p_4 LONGTEXT, IN p_5 LONGTEXT, IN p_6 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at)
             VALUES (p_1, p_2, p_3, p_4, p_5, 0, p_6);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_get_unread_for_user_1$$
CREATE PROCEDURE sp_rt_notification_get_unread_for_user_1(IN p_1 BIGINT, IN v_dynamic_1 INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications
                 WHERE user_id = p_1 AND is_read = 0
                 ORDER BY created_at DESC, id DESC
                 LIMIT v_dynamic_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_count_unread_for_user_1$$
CREATE PROCEDURE sp_rt_notification_count_unread_for_user_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM notifications WHERE user_id = p_1 AND is_read = 0;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_get_all_for_user_1$$
CREATE PROCEDURE sp_rt_notification_get_all_for_user_1(IN p_1 BIGINT, IN v_dynamic_1 INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications
                 WHERE user_id = p_1
                 ORDER BY is_read ASC, created_at DESC, id DESC
                 LIMIT v_dynamic_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_find_for_user_1$$
CREATE PROCEDURE sp_rt_notification_find_for_user_1(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications WHERE id = p_1 AND user_id = p_2 LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_mark_as_read_1$$
CREATE PROCEDURE sp_rt_notification_mark_as_read_1(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE notifications SET is_read = 1 WHERE id = p_1 AND user_id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_mark_as_read_2$$
CREATE PROCEDURE sp_rt_notification_mark_as_read_2(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE notifications SET is_read = 1 WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_mark_all_as_read_1$$
CREATE PROCEDURE sp_rt_notification_mark_all_as_read_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE notifications SET is_read = 1 WHERE user_id = p_1 AND is_read = 0;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_find_1$$
CREATE PROCEDURE sp_rt_notification_find_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM notifications WHERE id = p_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_notification_delete_1$$
CREATE PROCEDURE sp_rt_notification_delete_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM notifications WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_renew_owned_listing_1$$
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

DROP PROCEDURE IF EXISTS sp_rt_property_find_owned_1$$
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

DROP PROCEDURE IF EXISTS sp_rt_property_amenity_ids_for_listing_1$$
CREATE PROCEDURE sp_rt_property_amenity_ids_for_listing_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT amenity_id FROM property_amenities WHERE property_id = p_1 ORDER BY amenity_id ASC;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_sync_amenities_1$$
CREATE PROCEDURE sp_rt_property_sync_amenities_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM property_amenities WHERE property_id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_sync_amenities_2$$
CREATE PROCEDURE sp_rt_property_sync_amenities_2(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO property_amenities (property_id, amenity_id) VALUES (p_1, p_2);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_1$$
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

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_2$$
CREATE PROCEDURE sp_rt_property_update_owned_listing_2(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM property_images WHERE id = p_1 AND property_id = p_2;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_3$$
CREATE PROCEDURE sp_rt_property_update_owned_listing_3(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE property_images SET is_cover = 0 WHERE property_id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_4$$
CREATE PROCEDURE sp_rt_property_update_owned_listing_4(IN p_1 BIGINT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE property_images SET is_cover = 1 WHERE id = p_1 AND property_id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_5$$
CREATE PROCEDURE sp_rt_property_update_owned_listing_5(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM property_images WHERE property_id = p_1 AND is_cover = 1 LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_6$$
CREATE PROCEDURE sp_rt_property_update_owned_listing_6(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id FROM property_images WHERE property_id = p_1 ORDER BY id ASC LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_update_owned_listing_7$$
CREATE PROCEDURE sp_rt_property_update_owned_listing_7(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE property_images SET is_cover = 1 WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_toggle_verified_1$$
CREATE PROCEDURE sp_rt_property_moderation_toggle_verified_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, user_id, title, is_verified FROM properties WHERE id = p_1 LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_toggle_verified_2$$
CREATE PROCEDURE sp_rt_property_moderation_toggle_verified_2(IN p_1 LONGTEXT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET is_verified = p_1 WHERE id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_1$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, user_id, title, status FROM properties WHERE id = p_1 LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_2$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_2(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM properties WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_3$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_3(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'approved', reject_reason = NULL, expired_at = COALESCE(expired_at, DATE_ADD(NOW(), INTERVAL 30 DAY)) WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_4$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_4(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'rejected' WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_5$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_5(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'hidden' WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_6$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_6(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = 'pending' WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_property_moderation_update_status_7$$
CREATE PROCEDURE sp_rt_property_moderation_update_status_7(IN p_1 LONGTEXT, IN p_2 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE properties SET status = p_1 WHERE id = p_2;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_province_child_count_1$$
CREATE PROCEDURE sp_rt_province_child_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM wards WHERE province_id = p_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_province_usage_count_1$$
CREATE PROCEDURE sp_rt_province_usage_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.province_id = p_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_province_admin_list_1$$
CREATE PROCEDURE sp_rt_province_admin_list_1()
SQL SECURITY INVOKER
BEGIN
SELECT pr.id, pr.name, NULL AS parent_id, NULL AS parent_name,
             (SELECT COUNT(*) FROM wards w WHERE w.province_id = pr.id) AS extra,
             (SELECT COUNT(*) FROM properties p JOIN wards w ON w.id = p.ward_id WHERE w.province_id = pr.id) AS used
             FROM provinces pr
             ORDER BY pr.name;
END$$

DROP PROCEDURE IF EXISTS sp_rt_report_get_detail_1$$
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

DROP PROCEDURE IF EXISTS sp_rt_user_create_password_reset_token_1$$
CREATE PROCEDURE sp_rt_user_create_password_reset_token_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE email = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_user_create_password_reset_token_2$$
CREATE PROCEDURE sp_rt_user_create_password_reset_token_2(IN p_1 LONGTEXT, IN p_2 LONGTEXT, IN p_3 LONGTEXT, IN p_4 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (p_1, p_2, p_3, p_4);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_user_find_valid_password_reset_1$$
CREATE PROCEDURE sp_rt_user_find_valid_password_reset_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM password_resets WHERE token = p_1 AND expires_at >= p_2 ORDER BY created_at DESC LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_user_delete_password_resets_for_email_1$$
CREATE PROCEDURE sp_rt_user_delete_password_resets_for_email_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE email = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_user_delete_password_reset_token_1$$
CREATE PROCEDURE sp_rt_user_delete_password_reset_token_1(IN p_1 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM password_resets WHERE token = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_ward_usage_count_1$$
CREATE PROCEDURE sp_rt_ward_usage_count_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT COUNT(*) FROM properties WHERE ward_id = p_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_ward_find_by_province_id_1$$
CREATE PROCEDURE sp_rt_ward_find_by_province_id_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT MIN(id) AS id, MIN(name) AS name, MIN(province_id) AS province_id FROM wards WHERE province_id = p_1 GROUP BY BINARY name ORDER BY MIN(name) ASC;
END$$

DROP PROCEDURE IF EXISTS sp_rt_ward_find_1$$
CREATE PROCEDURE sp_rt_ward_find_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
SELECT id, name, province_id, district_id FROM wards WHERE id = p_1;
END$$

DROP PROCEDURE IF EXISTS sp_rt_ward_create_1$$
CREATE PROCEDURE sp_rt_ward_create_1(IN p_1 LONGTEXT, IN p_2 LONGTEXT)
SQL SECURITY INVOKER
BEGIN
INSERT INTO wards (name, province_id, district_id) VALUES (p_1, p_2, NULL);
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_ward_update_1$$
CREATE PROCEDURE sp_rt_ward_update_1(IN p_1 LONGTEXT, IN p_2 BIGINT, IN p_3 BIGINT)
SQL SECURITY INVOKER
BEGIN
UPDATE wards SET name = p_1, province_id = p_2 WHERE id = p_3;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DROP PROCEDURE IF EXISTS sp_rt_ward_delete_1$$
CREATE PROCEDURE sp_rt_ward_delete_1(IN p_1 BIGINT)
SQL SECURITY INVOKER
BEGIN
DELETE FROM wards WHERE id = p_1;
SELECT ROW_COUNT() AS __affected, LAST_INSERT_ID() AS __insert_id;
END$$

DELIMITER ;
