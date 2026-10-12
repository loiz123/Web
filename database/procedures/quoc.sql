DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_moderate_property(
    IN p_property_id INT,
    IN p_action VARCHAR(20),
    IN p_reason TEXT
)
BEGIN
    DECLARE current_status VARCHAR(20);
    
    SELECT status INTO current_status 
    FROM properties 
    WHERE id = p_property_id;

    IF current_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tin đăng không tồn tại';
    ELSEIF current_status = 'deleted' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể thao tác trên tin đã bị xóa';
    ELSE
        IF p_action = 'approve' THEN
            UPDATE properties 
            SET status = 'approved', reject_reason = NULL, updated_at = NOW() 
            WHERE id = p_property_id;
        ELSEIF p_action = 'reject' THEN
            UPDATE properties 
            SET status = 'rejected', reject_reason = p_reason, updated_at = NOW() 
            WHERE id = p_property_id;
        ELSEIF p_action = 'delete' THEN
            UPDATE properties 
            SET status = 'deleted', updated_at = NOW() 
            WHERE id = p_property_id;
        END IF;
    END IF;
END //

DELIMITER ;