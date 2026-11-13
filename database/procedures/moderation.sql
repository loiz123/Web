DELIMITER $$
DROP PROCEDURE IF EXISTS sp_moderation_count$$
CREATE PROCEDURE sp_moderation_count(IN p_status VARCHAR(20))
BEGIN SELECT COUNT(*) AS total FROM properties WHERE status=p_status; END$$
DROP PROCEDURE IF EXISTS sp_moderation_list$$
CREATE PROCEDURE sp_moderation_list(IN p_status VARCHAR(20), IN p_limit INT, IN p_offset INT)
BEGIN
 SELECT p.id,p.title,p.listing_type,p.price,p.area,p.address_detail,p.status,p.reject_reason,p.is_verified,p.created_at,u.full_name AS agent_name
 FROM properties p JOIN users u ON u.id=p.user_id WHERE p.status=p_status
 ORDER BY p.created_at DESC,p.id DESC LIMIT p_limit OFFSET p_offset;
END$$
DROP PROCEDURE IF EXISTS sp_moderation_apply$$
CREATE PROCEDURE sp_moderation_apply(IN p_id INT, IN p_action VARCHAR(10), IN p_reason VARCHAR(255))
BEGIN
 DECLARE v_affected INT DEFAULT 0;
 IF p_action='approve' THEN
  UPDATE properties SET status='approved',reject_reason=NULL,expired_at=COALESCE(expired_at,DATE_ADD(NOW(),INTERVAL 30 DAY)),expired_at=COALESCE(expired_at,DATE_ADD(NOW(),INTERVAL 30 DAY)) WHERE id=p_id AND status IN ('pending','hidden','rejected','closed');
 ELSEIF p_action='reject' AND p_reason IS NOT NULL AND TRIM(p_reason)<>'' THEN
  UPDATE properties SET status='rejected',reject_reason=TRIM(p_reason) WHERE id=p_id AND status IN ('pending','approved');
 ELSEIF p_action='delete' THEN
  DELETE FROM properties WHERE id=p_id;
 ELSE
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid moderation action';
 END IF;
 SET v_affected=ROW_COUNT();
 SELECT v_affected AS affected,EXISTS(SELECT 1 FROM properties WHERE id=p_id) AS exists_after;
END$$
DELIMITER ;
