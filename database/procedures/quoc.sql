-- [D] Quốc: routine duyệt tin. Import bằng phpMyAdmin (tab Import hoặc tab SQL).
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_moderate_property$$
CREATE PROCEDURE sp_moderate_property(
    IN p_id     INT UNSIGNED,
    IN p_action VARCHAR(10),      -- 'approve' | 'reject' | 'delete'
    IN p_reason VARCHAR(255)
)
BEGIN
    DECLARE v_status VARCHAR(10) DEFAULT NULL;

    SELECT status INTO v_status FROM properties WHERE id = p_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin không tồn tại hoặc đã bị xóa.';
    END IF;

    IF p_action = 'approve' THEN
        IF v_status <> 'pending' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ duyệt được tin đang chờ duyệt.';
        END IF;
        UPDATE properties SET status = 'approved', reject_reason = NULL WHERE id = p_id;

    ELSEIF p_action = 'reject' THEN
        IF v_status <> 'pending' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ từ chối được tin đang chờ duyệt.';
        END IF;
        IF p_reason IS NULL OR TRIM(p_reason) = '' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vui lòng nhập lý do từ chối.';
        END IF;
        UPDATE properties SET status = 'rejected', reject_reason = TRIM(p_reason) WHERE id = p_id;

    ELSEIF p_action = 'delete' THEN
        DELETE FROM properties WHERE id = p_id;

    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hành động không hợp lệ.';
    END IF;
END$$

DELIMITER ;
