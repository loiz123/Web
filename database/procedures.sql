-- Routine (stored procedure) mẫu. Import SAU seed.sql.
-- Lưu ý: một số free hosting KHÔNG cho tạo routine -> đặt use_routines=false trong config.
USE batdongsan;
DROP PROCEDURE IF EXISTS sp_latest_properties;
DROP PROCEDURE IF EXISTS sp_search_properties;
DROP PROCEDURE IF EXISTS sp_search_properties_v2;
DROP PROCEDURE IF EXISTS sp_count_search_properties_v2;

DELIMITER $$

-- Tin mới nhất đã duyệt (kèm ảnh bìa, địa chỉ)
CREATE PROCEDURE sp_latest_properties(IN p_limit INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
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
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
  ORDER BY p.created_at DESC, p.id DESC
  LIMIT p_limit;
END$$

-- Tìm kiếm cơ bản (tương thích ngược v1)
CREATE PROCEDURE sp_search_properties(
  IN p_keyword VARCHAR(100), IN p_listing_type VARCHAR(10), IN p_district_id INT,
  IN p_min_price DECIMAL(18,2), IN p_max_price DECIMAL(18,2),
  IN p_limit INT, IN p_offset INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
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
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%'))
    AND (p_listing_type IS NULL OR p.listing_type = p_listing_type)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (p_min_price IS NULL OR p.price >= p_min_price)
    AND (p_max_price IS NULL OR p.price <= p_max_price)
  ORDER BY p.created_at DESC
  LIMIT p_limit OFFSET p_offset;
END$$

-- [C - Thiện] Tìm kiếm nâng cao v2: đầy đủ các bộ lọc (loại BĐS, tỉnh/quận/phường, giá, diện tích, số PN, sắp xếp)
CREATE PROCEDURE sp_search_properties_v2(
  IN p_keyword VARCHAR(100),
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
  IN p_sort_by VARCHAR(20),
  IN p_limit INT,
  IN p_offset INT)
BEGIN
  SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
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
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%') OR w.name LIKE CONCAT('%', p_keyword, '%') OR d.name LIKE CONCAT('%', p_keyword, '%') OR pr.name LIKE CONCAT('%', p_keyword, '%') OR t.name LIKE CONCAT('%', p_keyword, '%'))
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
    p.created_at DESC, p.id DESC
  LIMIT p_limit OFFSET p_offset;
END$$

-- [C - Thiện] Đếm tổng số kết quả khớp với bộ lọc
CREATE PROCEDURE sp_count_search_properties_v2(
  IN p_keyword VARCHAR(100),
  IN p_listing_type VARCHAR(10),
  IN p_type_id INT,
  IN p_province_id INT,
  IN p_district_id INT,
  IN p_ward_id INT,
  IN p_min_price DECIMAL(18,2),
  IN p_max_price DECIMAL(18,2),
  IN p_min_area FLOAT,
  IN p_max_area FLOAT,
  IN p_bedrooms INT)
BEGIN
  SELECT COUNT(*) AS total
  FROM properties p
  JOIN property_types t ON t.id = p.type_id
  JOIN wards w          ON w.id = p.ward_id
  JOIN districts d      ON d.id = w.district_id
  JOIN provinces pr     ON pr.id = d.province_id
  WHERE p.status = 'approved'
    AND (p_keyword IS NULL OR p.title LIKE CONCAT('%', p_keyword, '%') OR p.address_detail LIKE CONCAT('%', p_keyword, '%') OR w.name LIKE CONCAT('%', p_keyword, '%') OR d.name LIKE CONCAT('%', p_keyword, '%') OR pr.name LIKE CONCAT('%', p_keyword, '%') OR t.name LIKE CONCAT('%', p_keyword, '%'))
    AND (p_listing_type IS NULL OR p.listing_type = p_listing_type)
    AND (p_type_id IS NULL OR p.type_id = p_type_id)
    AND (p_ward_id IS NULL OR p.ward_id = p_ward_id)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (p_province_id IS NULL OR pr.id = p_province_id)
    AND (p_min_price IS NULL OR p.price >= p_min_price)
    AND (p_max_price IS NULL OR p.price <= p_max_price)
    AND (p_min_area IS NULL OR p.area >= p_min_area)
    AND (p_max_area IS NULL OR p.area <= p_max_area)
    AND (p_bedrooms IS NULL OR (p_bedrooms = 5 AND p.bedrooms >= 5) OR (p_bedrooms < 5 AND p.bedrooms = p_bedrooms));
END$$

DELIMITER ;
