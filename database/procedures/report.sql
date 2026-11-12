DELIMITER $$
DROP PROCEDURE IF EXISTS sp_report_exists$$
CREATE PROCEDURE sp_report_exists(IN p_property INT, IN p_user INT)
BEGIN SELECT 1 FROM reports WHERE property_id=p_property AND reporter_id=p_user AND status='open' LIMIT 1; END$$
DROP PROCEDURE IF EXISTS sp_report_create$$
CREATE PROCEDURE sp_report_create(IN p_property INT, IN p_user INT, IN p_reason VARCHAR(255))
BEGIN
 INSERT INTO reports (property_id,reporter_id,reason)
 SELECT p.id,p_user,p_reason FROM properties p WHERE p.id=p_property AND p.status='approved'
 AND NOT EXISTS (SELECT 1 FROM reports r WHERE r.property_id=p_property AND r.reporter_id=p_user AND r.status='open');
 SELECT IF(ROW_COUNT()>0,LAST_INSERT_ID(),0) AS id;
END$$
DROP PROCEDURE IF EXISTS sp_report_count$$
CREATE PROCEDURE sp_report_count(IN p_status VARCHAR(20))
BEGIN SELECT COUNT(*) AS total FROM reports WHERE p_status IS NULL OR status=p_status; END$$
DROP PROCEDURE IF EXISTS sp_report_list$$
CREATE PROCEDURE sp_report_list(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT r.*,p.title AS property_title,p.status AS property_status,u.full_name AS reporter_name FROM reports r
 JOIN properties p ON p.id=r.property_id JOIN users u ON u.id=r.reporter_id
 WHERE p_status IS NULL OR r.status=p_status ORDER BY r.created_at DESC LIMIT p_limit OFFSET p_offset;
END$$
DROP PROCEDURE IF EXISTS sp_report_resolve$$
CREATE PROCEDURE sp_report_resolve(IN p_id INT, IN p_status VARCHAR(20), IN p_hide BOOLEAN)
BEGIN
 DECLARE v_affected INT DEFAULT 0;
 UPDATE reports SET status=p_status WHERE id=p_id AND status='open' AND p_status IN ('resolved','dismissed');
 SET v_affected=ROW_COUNT();
 IF v_affected>0 AND p_hide=1 AND p_status='resolved' THEN
  UPDATE properties p JOIN reports r ON r.property_id=p.id SET p.status='hidden' WHERE r.id=p_id AND p.status='approved';
 END IF;
 SELECT v_affected AS affected;
END$$
DELIMITER ;
