DELIMITER $$
DROP PROCEDURE IF EXISTS sp_admin_users_count$$
CREATE PROCEDURE sp_admin_users_count(IN p_role VARCHAR(20), IN p_status VARCHAR(20), IN p_keyword VARCHAR(200))
BEGIN
 SELECT COUNT(*) AS total FROM users u JOIN roles r ON r.id=u.role_id
 WHERE (p_role IS NULL OR r.name=p_role) AND (p_status IS NULL OR u.status=p_status)
 AND (p_keyword IS NULL OR u.full_name LIKE p_keyword OR u.email LIKE p_keyword);
END$$
DROP PROCEDURE IF EXISTS sp_admin_users_list$$
CREATE PROCEDURE sp_admin_users_list(IN p_role VARCHAR(20), IN p_status VARCHAR(20), IN p_keyword VARCHAR(200), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT u.id,u.full_name,u.email,u.phone,u.status,u.created_at,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id
 WHERE (p_role IS NULL OR r.name=p_role) AND (p_status IS NULL OR u.status=p_status)
 AND (p_keyword IS NULL OR u.full_name LIKE p_keyword OR u.email LIKE p_keyword) ORDER BY u.id DESC LIMIT p_limit OFFSET p_offset;
END$$
DROP PROCEDURE IF EXISTS sp_admin_user_find$$
CREATE PROCEDURE sp_admin_user_find(IN p_id INT)
BEGIN SELECT u.id,u.status,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=p_id; END$$
DROP PROCEDURE IF EXISTS sp_admin_user_status$$
CREATE PROCEDURE sp_admin_user_status(IN p_id INT, IN p_status VARCHAR(20))
BEGIN
 UPDATE users u JOIN roles r ON r.id=u.role_id SET u.status=p_status
 WHERE u.id=p_id AND p_status IN ('active','locked') AND (p_status<>'locked' OR r.name<>'admin');
 SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;
