-- [B] Hai: routine cho dashboard và đổi trạng thái tin của chính môi giới.
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_get_agent_properties$$
CREATE PROCEDURE sp_get_agent_properties(
    IN p_user_id INT UNSIGNED,
    IN p_status VARCHAR(10)
)
BEGIN
    SELECT p.id, p.title, p.status, p.listing_type, p.price, p.area, p.created_at,
           p.expired_at, p.reject_reason, t.name AS type_name,
           (SELECT image_url FROM property_images i
            WHERE i.property_id = p.id
            ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
    FROM properties p
    JOIN property_types t ON t.id = p.type_id
    WHERE p.user_id = p_user_id
      AND (p_status IS NULL OR p.status = p_status)
    ORDER BY p.created_at DESC, p.id DESC;
END$$

DROP PROCEDURE IF EXISTS sp_set_property_status$$
CREATE PROCEDURE sp_set_property_status(
    IN p_id INT UNSIGNED,
    IN p_user_id INT UNSIGNED,
    IN p_status VARCHAR(10)
)
BEGIN
    DECLARE v_status VARCHAR(10) DEFAULT NULL;

    SELECT status INTO v_status
    FROM properties
    WHERE id = p_id AND user_id = p_user_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin không tồn tại hoặc không thuộc về bạn.';
    ELSEIF p_status = 'hidden' AND v_status <> 'approved' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ có thể ẩn tin đã được duyệt.';
    ELSEIF p_status = 'pending' AND v_status NOT IN ('hidden', 'closed') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin không thể gửi duyệt lại ở trạng thái hiện tại.';
    ELSEIF p_status = 'closed' AND v_status <> 'approved' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ có thể đóng tin đã được duyệt.';
    ELSE
        UPDATE properties
        SET status = p_status, reject_reason = NULL
        WHERE id = p_id AND user_id = p_user_id;
        SELECT 1 AS changed;
    END IF;
END$$

DELIMITER ;
