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
 SELECT d.id,d.name AS district_name,pv.name AS province_name,COUNT(pr.id) AS total,SUM(pr.status='approved') AS approved
 FROM districts d JOIN provinces pv ON pv.id=d.province_id JOIN wards w ON w.district_id=d.id
 JOIN properties pr ON pr.ward_id=w.id GROUP BY d.id,d.name,pv.name ORDER BY total DESC,d.name;
END$$
DELIMITER ;
