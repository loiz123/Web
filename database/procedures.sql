-- Routine (stored procedure) mẫu. Import SAU seed.sql.
-- Lưu ý: một số free hosting KHÔNG cho tạo routine -> đặt use_routines=false trong config.
USE batdongsan;
DROP PROCEDURE IF EXISTS sp_latest_properties;
DROP PROCEDURE IF EXISTS sp_search_properties;

DELIMITER $$

-- Tin mới nhất đã duyệt (kèm ảnh bìa, địa chỉ)
CREATE PROCEDURE sp_latest_properties(IN p_limit INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
         p.is_verified, t.name AS type_name, w.name AS ward_name, d.name AS district_name,
         (SELECT image_url FROM property_images i
            WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
  FROM properties p
  JOIN property_types t ON t.id = p.type_id
  JOIN wards w          ON w.id = p.ward_id
  JOIN districts d      ON d.id = w.district_id
  WHERE p.status = 'approved'
  ORDER BY p.created_at DESC, p.id DESC
  LIMIT p_limit;
END$$

-- Tìm kiếm có lọc (NULL = bỏ qua điều kiện). [C] mở rộng thêm.
CREATE PROCEDURE sp_search_properties(
  IN p_keyword VARCHAR(100), IN p_listing_type VARCHAR(10), IN p_district_id INT,
  IN p_min_price DECIMAL(18,2), IN p_max_price DECIMAL(18,2),
  IN p_limit INT, IN p_offset INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
         p.is_verified, t.name AS type_name, w.name AS ward_name, d.name AS district_name,
         (SELECT image_url FROM property_images i
            WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
  FROM properties p
  JOIN property_types t ON t.id = p.type_id
  JOIN wards w          ON w.id = p.ward_id
  JOIN districts d      ON d.id = w.district_id
  WHERE p.status = 'approved'
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%'))
    AND (p_listing_type IS NULL OR p.listing_type = p_listing_type)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (p_min_price IS NULL OR p.price >= p_min_price)
    AND (p_max_price IS NULL OR p.price <= p_max_price)
  ORDER BY p.created_at DESC
  LIMIT p_limit OFFSET p_offset;
END$$

DELIMITER ;
