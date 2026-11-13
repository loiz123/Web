-- Import vào database đã chọn; không tạo hoặc xóa bảng.
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_user_by_email$$
CREATE PROCEDURE sp_user_by_email(IN p_email VARCHAR(100))
BEGIN
  SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = p_email;
END$$
DROP PROCEDURE IF EXISTS sp_user_by_id$$
CREATE PROCEDURE sp_user_by_id(IN p_id INT)
BEGIN
  SELECT * FROM users WHERE id = p_id;
END$$
DROP PROCEDURE IF EXISTS sp_user_session$$
CREATE PROCEDURE sp_user_session(IN p_id INT)
BEGIN
  SELECT u.id, u.full_name, u.email, u.password_hash, u.avatar, u.bio, u.status, u.role_id,
         r.name AS role_name
  FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = p_id;
END$$
DROP PROCEDURE IF EXISTS sp_user_register$$
CREATE PROCEDURE sp_user_register(IN p_name VARCHAR(100), IN p_email VARCHAR(100), IN p_phone VARCHAR(15), IN p_hash VARCHAR(255), IN p_role VARCHAR(20))
BEGIN
  INSERT INTO users (role_id, full_name, email, phone, password_hash)
  SELECT id, p_name, p_email, p_phone, p_hash FROM roles WHERE name = p_role;
  SELECT IF(ROW_COUNT() > 0, LAST_INSERT_ID(), 0) AS id;
END$$
DROP PROCEDURE IF EXISTS sp_user_update_profile$$
CREATE PROCEDURE sp_user_update_profile(IN p_id INT, IN p_name VARCHAR(100), IN p_phone VARCHAR(15))
BEGIN
  UPDATE users SET full_name = p_name, phone = p_phone WHERE id = p_id;
END$$
DROP PROCEDURE IF EXISTS sp_user_set_password$$
CREATE PROCEDURE sp_user_set_password(IN p_id INT, IN p_hash VARCHAR(255))
BEGIN
  UPDATE users SET password_hash = p_hash WHERE id = p_id;
END$$
DROP PROCEDURE IF EXISTS sp_saved_search_create$$
CREATE PROCEDURE sp_saved_search_create(IN p_user INT, IN p_name VARCHAR(100), IN p_filters LONGTEXT)
BEGIN
  INSERT INTO saved_searches (user_id, name, filters, created_at) VALUES (p_user, p_name, p_filters, NOW());
  SELECT LAST_INSERT_ID() AS id;
END$$
DROP PROCEDURE IF EXISTS sp_saved_search_list$$
CREATE PROCEDURE sp_saved_search_list(IN p_user INT)
BEGIN
  SELECT * FROM saved_searches WHERE user_id = p_user ORDER BY created_at DESC;
END$$
DROP PROCEDURE IF EXISTS sp_saved_search_find$$
CREATE PROCEDURE sp_saved_search_find(IN p_id INT, IN p_user INT)
BEGIN
  SELECT * FROM saved_searches WHERE id = p_id AND user_id = p_user LIMIT 1;
END$$
DROP PROCEDURE IF EXISTS sp_saved_search_delete$$
CREATE PROCEDURE sp_saved_search_delete(IN p_id INT, IN p_user INT)
BEGIN
  DELETE FROM saved_searches WHERE id = p_id AND user_id = p_user;
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;
