DELIMITER $$
DROP PROCEDURE IF EXISTS sp_property_images$$
CREATE PROCEDURE sp_property_images(IN p_property INT)
BEGIN SELECT id,property_id,image_url,is_cover FROM property_images WHERE property_id=p_property ORDER BY is_cover DESC,id ASC; END$$
DROP PROCEDURE IF EXISTS sp_property_image_create$$
CREATE PROCEDURE sp_property_image_create(IN p_property INT, IN p_url VARCHAR(255), IN p_cover BOOLEAN)
BEGIN INSERT INTO property_images (property_id,image_url,is_cover) VALUES (p_property,p_url,p_cover); SELECT LAST_INSERT_ID() AS id; END$$
DROP PROCEDURE IF EXISTS sp_property_images_delete$$
CREATE PROCEDURE sp_property_images_delete(IN p_property INT)
BEGIN DELETE FROM property_images WHERE property_id=p_property; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;
