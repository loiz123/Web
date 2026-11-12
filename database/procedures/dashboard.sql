DELIMITER $$
DROP PROCEDURE IF EXISTS sp_dashboard_counts$$
CREATE PROCEDURE sp_dashboard_counts()
BEGIN
 SELECT (SELECT COUNT(*) FROM users) AS users_total,
        (SELECT COUNT(*) FROM properties WHERE status='approved') AS properties_total,
        (SELECT COUNT(*) FROM properties WHERE status='pending') AS pending_total,
        (SELECT COUNT(*) FROM reports WHERE status IN ('open','pending')) AS reports_total;
END$$
DROP PROCEDURE IF EXISTS sp_dashboard_by_status$$
CREATE PROCEDURE sp_dashboard_by_status()
BEGIN SELECT status,COUNT(*) AS total FROM properties GROUP BY status; END$$
DROP PROCEDURE IF EXISTS sp_dashboard_by_district$$
CREATE PROCEDURE sp_dashboard_by_district()
BEGIN
 SELECT pv.id AS id,pv.name AS district_name,pv.name AS province_name,
        COALESCE(SUM(pr.status='approved'),0) AS total,
        COALESCE(SUM(pr.status='approved'),0) AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id
 JOIN provinces pv ON pv.id=w.province_id
 WHERE pr.status='approved'
 GROUP BY pv.id,pv.name ORDER BY approved DESC,pv.name ASC;
END$$
DROP PROCEDURE IF EXISTS sp_dashboard_by_province$$
CREATE PROCEDURE sp_dashboard_by_province()
BEGIN
 SELECT pv.id AS province_id,pv.name AS province_name,
        COALESCE(SUM(pr.status='approved'),0) AS total,
        COALESCE(SUM(pr.status='approved'),0) AS approved
 FROM properties pr JOIN wards w ON w.id=pr.ward_id
 JOIN provinces pv ON pv.id=w.province_id
 WHERE pr.status='approved'
 GROUP BY pv.id,pv.name ORDER BY approved DESC,pv.name ASC;
END$$
DELIMITER ;
