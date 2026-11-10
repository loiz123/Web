DELIMITER $$
DROP PROCEDURE IF EXISTS sp_dashboard_counts$$
CREATE PROCEDURE sp_dashboard_counts()
BEGIN
 SELECT (SELECT COUNT(*) FROM users) AS users_total,
        (SELECT COUNT(*) FROM properties) AS properties_total,
        (SELECT COUNT(*) FROM properties WHERE status='pending') AS pending_total,
        (SELECT COUNT(*) FROM reports WHERE status='open') AS reports_total;
END$$
DROP PROCEDURE IF EXISTS sp_dashboard_by_status$$
CREATE PROCEDURE sp_dashboard_by_status()
BEGIN SELECT status,COUNT(*) AS total FROM properties GROUP BY status; END$$
DROP PROCEDURE IF EXISTS sp_dashboard_by_district$$
CREATE PROCEDURE sp_dashboard_by_district()
BEGIN
 SELECT COALESCE(d.id,w.id) AS id,COALESCE(d.name,w.name) AS district_name,pv.name AS province_name,COUNT(pr.id) AS total,SUM(pr.status='approved') AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id LEFT JOIN districts d ON d.id=w.district_id
 JOIN provinces pv ON pv.id=w.province_id GROUP BY d.id,CASE WHEN d.id IS NULL THEN w.id ELSE 0 END,COALESCE(d.name,w.name),pv.name ORDER BY total DESC,district_name;
END$$
DELIMITER ;
