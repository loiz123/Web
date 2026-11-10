DELIMITER $$
DROP PROCEDURE IF EXISTS sp_property_detail$$
CREATE PROCEDURE sp_property_detail(IN p_id INT)
BEGIN
 SELECT p.*,t.name AS type_name,w.name AS ward_name,d.id AS district_id,COALESCE(d.name,w.name) AS district_name,
 pr.id AS province_id,pr.name AS province_name,u.full_name AS agent_name,u.phone AS agent_phone,u.email AS agent_email,u.avatar AS agent_avatar,u.bio AS agent_bio,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM properties p JOIN property_types t ON t.id=p.type_id JOIN wards w ON w.id=p.ward_id
 LEFT JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=w.province_id JOIN users u ON u.id=p.user_id
 WHERE p.id=p_id LIMIT 1;
END$$
DROP PROCEDURE IF EXISTS sp_property_detail_images$$
CREATE PROCEDURE sp_property_detail_images(IN p_id INT)
BEGIN SELECT id,image_url,is_cover FROM property_images WHERE property_id=p_id ORDER BY is_cover DESC,id ASC; END$$
DROP PROCEDURE IF EXISTS sp_property_detail_amenities$$
CREATE PROCEDURE sp_property_detail_amenities(IN p_id INT)
BEGIN SELECT a.id,a.name FROM property_amenities pa JOIN amenities a ON a.id=pa.amenity_id WHERE pa.property_id=p_id ORDER BY a.name ASC; END$$
DROP PROCEDURE IF EXISTS sp_property_increment_views$$
CREATE PROCEDURE sp_property_increment_views(IN p_id INT)
BEGIN UPDATE properties SET views=views+1,updated_at=updated_at WHERE id=p_id; END$$
DROP PROCEDURE IF EXISTS sp_property_same_type$$
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
