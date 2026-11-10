DELIMITER $$
DROP PROCEDURE IF EXISTS sp_catalog_find$$
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
DROP PROCEDURE IF EXISTS sp_catalog_create$$
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
DROP PROCEDURE IF EXISTS sp_catalog_update$$
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
DROP PROCEDURE IF EXISTS sp_catalog_delete$$
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
DROP PROCEDURE IF EXISTS sp_catalog_name_exists$$
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
DROP PROCEDURE IF EXISTS sp_catalog_usage$$
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
DROP PROCEDURE IF EXISTS sp_catalog_children$$
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
DROP PROCEDURE IF EXISTS sp_catalog_admin_list$$
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
DROP PROCEDURE IF EXISTS sp_catalog_sorted$$
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
