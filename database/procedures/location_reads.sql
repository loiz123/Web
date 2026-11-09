-- Import vào database đã chọn. Không tạo hoặc xóa bảng.
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_location_provinces$$
CREATE PROCEDURE sp_location_provinces()
BEGIN
  SELECT id, name FROM provinces ORDER BY name;
END$$

DROP PROCEDURE IF EXISTS sp_location_districts$$
CREATE PROCEDURE sp_location_districts(IN p_province_id INT)
BEGIN
  SELECT id, name FROM districts WHERE province_id = p_province_id ORDER BY name;
END$$

DROP PROCEDURE IF EXISTS sp_location_wards$$
CREATE PROCEDURE sp_location_wards(IN p_district_id INT)
BEGIN
  SELECT id, name FROM wards WHERE district_id = p_district_id ORDER BY name;
END$$

DROP PROCEDURE IF EXISTS sp_location_province_by_district$$
CREATE PROCEDURE sp_location_province_by_district(IN p_district_id INT)
BEGIN
  SELECT province_id FROM districts WHERE id = p_district_id;
END$$

DROP PROCEDURE IF EXISTS sp_location_valid_ward$$
CREATE PROCEDURE sp_location_valid_ward(IN p_province_id INT, IN p_district_id INT, IN p_ward_id INT)
BEGIN
  SELECT w.id FROM wards w JOIN districts d ON d.id = w.district_id
  WHERE w.id = p_ward_id AND d.id = p_district_id AND d.province_id = p_province_id;
END$$

DELIMITER ;
