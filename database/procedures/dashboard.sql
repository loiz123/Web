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
 SELECT pv.id AS id,pv.name AS district_name,pv.name AS province_name,COUNT(pr.id) AS total,COALESCE(SUM(pr.status='approved'),0) AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id
 JOIN provinces pv ON pv.id=w.province_id GROUP BY pv.id,pv.name ORDER BY total DESC,pv.name ASC;
END$$
DROP PROCEDURE IF EXISTS sp_dashboard_by_province$$
CREATE PROCEDURE sp_dashboard_by_province()
BEGIN
 SELECT pv.id AS province_id,pv.name AS province_name,COUNT(pr.id) AS total,COALESCE(SUM(pr.status='approved'),0) AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id
 JOIN provinces pv ON pv.id=w.province_id GROUP BY pv.id,pv.name ORDER BY total DESC,pv.name ASC;
END$$
DELIMITER ;
