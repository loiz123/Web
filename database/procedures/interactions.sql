DELIMITER $$
DROP PROCEDURE IF EXISTS sp_favorite_exists$$
CREATE PROCEDURE sp_favorite_exists(IN p_user INT, IN p_property INT)
BEGIN SELECT 1 FROM favorites WHERE user_id=p_user AND property_id=p_property LIMIT 1; END$$
DROP PROCEDURE IF EXISTS sp_favorite_add$$
CREATE PROCEDURE sp_favorite_add(IN p_user INT, IN p_property INT)
BEGIN INSERT IGNORE INTO favorites (user_id,property_id) VALUES (p_user,p_property); SELECT ROW_COUNT() AS affected; END$$
DROP PROCEDURE IF EXISTS sp_favorite_remove$$
CREATE PROCEDURE sp_favorite_remove(IN p_user INT, IN p_property INT)
BEGIN DELETE FROM favorites WHERE user_id=p_user AND property_id=p_property; SELECT ROW_COUNT() AS affected; END$$
DROP PROCEDURE IF EXISTS sp_favorite_list$$
CREATE PROCEDURE sp_favorite_list(IN p_user INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.bedrooms,p.bathrooms,p.is_verified,p.status,f.created_at AS favorited_at,
 t.name AS type_name,w.name AS ward_name,COALESCE(d.name,'') AS district_name,pr.name AS province_name,
 (SELECT image_url FROM property_images i WHERE i.property_id=p.id ORDER BY i.is_cover DESC,i.id LIMIT 1) AS cover_url
 FROM favorites f JOIN properties p ON p.id=f.property_id JOIN property_types t ON t.id=p.type_id
 JOIN wards w ON w.id=p.ward_id LEFT JOIN districts d ON d.id=w.district_id JOIN provinces pr ON pr.id=w.province_id
 WHERE f.user_id=p_user AND p.status='approved' ORDER BY f.created_at DESC;
END$$
DROP PROCEDURE IF EXISTS sp_favorite_count$$
CREATE PROCEDURE sp_favorite_count(IN p_user INT)
BEGIN SELECT COUNT(*) AS total FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=p_user AND p.status='approved'; END$$
DROP PROCEDURE IF EXISTS sp_favorite_ids$$
CREATE PROCEDURE sp_favorite_ids(IN p_user INT)
BEGIN SELECT property_id FROM favorites WHERE user_id=p_user; END$$
DROP PROCEDURE IF EXISTS sp_review_for_property$$
CREATE PROCEDURE sp_review_for_property(IN p_property INT)
BEGIN SELECT r.*,u.full_name AS user_name,u.avatar AS user_avatar,u.role_id FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.property_id=p_property ORDER BY r.created_at DESC; END$$
DROP PROCEDURE IF EXISTS sp_review_stats$$
CREATE PROCEDURE sp_review_stats(IN p_property INT)
BEGIN SELECT COUNT(*) AS total,AVG(rating) AS avg_rating FROM reviews WHERE property_id=p_property; END$$
DROP PROCEDURE IF EXISTS sp_review_by_user$$
CREATE PROCEDURE sp_review_by_user(IN p_property INT, IN p_user INT)
BEGIN SELECT * FROM reviews WHERE property_id=p_property AND user_id=p_user LIMIT 1; END$$
DROP PROCEDURE IF EXISTS sp_review_save$$
CREATE PROCEDURE sp_review_save(IN p_property INT, IN p_user INT, IN p_rating INT, IN p_comment TEXT)
BEGIN
 IF EXISTS(SELECT 1 FROM properties WHERE id=p_property) THEN
  INSERT INTO reviews (property_id,user_id,rating,comment) VALUES (p_property,p_user,p_rating,p_comment)
  ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),created_at=CURRENT_TIMESTAMP;
  SELECT ROW_COUNT() AS affected;
 ELSE SELECT 0 AS affected; END IF;
END$$
DROP PROCEDURE IF EXISTS sp_review_delete$$
CREATE PROCEDURE sp_review_delete(IN p_id INT, IN p_user INT)
BEGIN DELETE FROM reviews WHERE id=p_id AND user_id=p_user; SELECT ROW_COUNT() AS affected; END$$
DELIMITER ;
