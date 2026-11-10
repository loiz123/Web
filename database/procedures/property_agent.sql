DELIMITER $$
DROP PROCEDURE IF EXISTS sp_agent_property_by_id$$
CREATE PROCEDURE sp_agent_property_by_id(IN p_id INT, IN p_owner INT)
BEGIN SELECT * FROM properties WHERE id=p_id AND (p_owner IS NULL OR user_id=p_owner) LIMIT 1; END$$
DROP PROCEDURE IF EXISTS sp_agent_property_list$$
CREATE PROCEDURE sp_agent_property_list(IN p_owner INT, IN p_status VARCHAR(20))
BEGIN
 SELECT p.id,p.title,p.status,p.listing_type,p.price,p.area,p.created_at,p.expired_at,p.reject_reason,t.name AS type_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id WHERE p.user_id=p_owner AND (p_status IS NULL OR p.status=p_status)
 ORDER BY p.created_at DESC,p.id DESC;
END$$
DROP PROCEDURE IF EXISTS sp_agent_property_update$$
CREATE PROCEDURE sp_agent_property_update(IN p_id INT, IN p_owner INT, IN p_type INT, IN p_title VARCHAR(200), IN p_description TEXT, IN p_listing VARCHAR(10), IN p_price DECIMAL(18,2), IN p_area FLOAT, IN p_bedrooms INT, IN p_bathrooms INT, IN p_address VARCHAR(200), IN p_lat DOUBLE, IN p_lng DOUBLE)
BEGIN
 UPDATE properties SET type_id=p_type,title=p_title,description=p_description,listing_type=p_listing,price=p_price,area=p_area,
 bedrooms=p_bedrooms,bathrooms=p_bathrooms,address_detail=p_address,latitude=p_lat,longitude=p_lng,status='pending',reject_reason=NULL
 WHERE id=p_id AND user_id=p_owner;
 SELECT ROW_COUNT() AS affected, EXISTS(SELECT 1 FROM properties WHERE id=p_id AND user_id=p_owner) AS owned;
END$$
DROP PROCEDURE IF EXISTS sp_agent_property_status$$
CREATE PROCEDURE sp_agent_property_status(IN p_id INT, IN p_owner INT, IN p_status VARCHAR(20))
BEGIN
 UPDATE properties SET status=p_status,reject_reason=NULL WHERE id=p_id AND user_id=p_owner AND
 ((p_status IN ('hidden','closed') AND status='approved') OR (p_status='pending' AND status IN ('hidden','closed')));
 SELECT ROW_COUNT() AS affected;
END$$
DROP PROCEDURE IF EXISTS sp_agent_property_delete$$
CREATE PROCEDURE sp_agent_property_delete(IN p_id INT, IN p_owner INT)
BEGIN DELETE FROM properties WHERE id=p_id AND user_id=p_owner; SELECT ROW_COUNT() AS affected; END$$
DROP PROCEDURE IF EXISTS sp_agent_property_create$$
CREATE PROCEDURE sp_agent_property_create(IN p_user INT,IN p_type INT,IN p_ward INT,IN p_title VARCHAR(200),IN p_description TEXT,IN p_listing VARCHAR(10),IN p_price DECIMAL(18,2),IN p_deposit DECIMAL(18,2),IN p_area FLOAT,IN p_bedrooms INT,IN p_bathrooms INT,IN p_direction VARCHAR(30),IN p_legal VARCHAR(100),IN p_furniture VARCHAR(20),IN p_pets BOOLEAN,IN p_address VARCHAR(200),IN p_lat DOUBLE,IN p_lng DOUBLE)
BEGIN
 INSERT INTO properties (user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,latitude,longitude,status)
 VALUES (p_user,p_type,p_ward,p_title,p_description,p_listing,p_price,p_deposit,p_area,p_bedrooms,p_bathrooms,p_direction,p_legal,p_furniture,p_pets,p_address,p_lat,p_lng,'pending');
 SELECT LAST_INSERT_ID() AS id;
END$$
DROP PROCEDURE IF EXISTS sp_agent_property_add_amenity$$
CREATE PROCEDURE sp_agent_property_add_amenity(IN p_property INT, IN p_amenity INT)
BEGIN INSERT INTO property_amenities (property_id,amenity_id) VALUES (p_property,p_amenity); END$$
DROP PROCEDURE IF EXISTS sp_property_similar_ranked$$
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
