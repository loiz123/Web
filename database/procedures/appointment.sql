-- Import vào database đã chọn; các procedure không xóa bảng hoặc dữ liệu.
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_appointment_book$$
CREATE PROCEDURE sp_appointment_book(IN p_property_id INT, IN p_customer_id INT, IN p_scheduled_at DATETIME, IN p_note VARCHAR(255))
BEGIN
  INSERT INTO viewing_appointments (property_id, customer_id, scheduled_at, note)
  SELECT id, p_customer_id, p_scheduled_at, p_note FROM properties WHERE id = p_property_id AND status = 'approved';
  SELECT IF(ROW_COUNT() > 0, LAST_INSERT_ID(), 0) AS id;
END$$
DROP PROCEDURE IF EXISTS sp_appointment_has_active$$
CREATE PROCEDURE sp_appointment_has_active(IN p_property_id INT, IN p_customer_id INT)
BEGIN
  SELECT 1 FROM viewing_appointments WHERE property_id = p_property_id AND customer_id = p_customer_id AND status IN ('pending', 'confirmed') LIMIT 1;
END$$
DROP PROCEDURE IF EXISTS sp_appointment_for_customer$$
CREATE PROCEDURE sp_appointment_for_customer(IN p_customer_id INT)
BEGIN
  SELECT va.*, p.title AS property_title FROM viewing_appointments va JOIN properties p ON p.id = va.property_id
  WHERE va.customer_id = p_customer_id ORDER BY va.scheduled_at DESC;
END$$
DROP PROCEDURE IF EXISTS sp_appointment_for_agent$$
CREATE PROCEDURE sp_appointment_for_agent(IN p_agent_id INT)
BEGIN
  SELECT va.*, p.title AS property_title, u.full_name AS customer_name, u.phone AS customer_phone
  FROM viewing_appointments va JOIN properties p ON p.id = va.property_id JOIN users u ON u.id = va.customer_id
  WHERE p.user_id = p_agent_id ORDER BY va.scheduled_at DESC;
END$$
DROP PROCEDURE IF EXISTS sp_appointment_agent_status$$
CREATE PROCEDURE sp_appointment_agent_status(IN p_id INT, IN p_agent_id INT, IN p_status VARCHAR(20))
BEGIN
  UPDATE viewing_appointments va JOIN properties p ON p.id = va.property_id SET va.status = p_status
  WHERE va.id = p_id AND p.user_id = p_agent_id AND va.status IN ('pending', 'confirmed') AND p_status IN ('confirmed', 'cancelled', 'done');
  SELECT ROW_COUNT() AS affected;
END$$
DROP PROCEDURE IF EXISTS sp_appointment_customer_cancel$$
CREATE PROCEDURE sp_appointment_customer_cancel(IN p_id INT, IN p_customer_id INT)
BEGIN
  UPDATE viewing_appointments SET status = 'cancelled' WHERE id = p_id AND customer_id = p_customer_id AND status IN ('pending', 'confirmed');
  SELECT ROW_COUNT() AS affected;
END$$
DELIMITER ;
