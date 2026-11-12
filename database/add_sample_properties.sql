-- Thêm tin mẫu còn thiếu; giữ nguyên tin đã có. Sao lưu CSDL trước khi chạy.
USE batdongsan;
START TRANSACTION;
INSERT IGNORE INTO properties (id,user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,status,reject_reason,is_verified) VALUES
(4,2,1,1,'Căn hộ 3PN ban công rộng tại Bến Nghé','Căn hộ nhiều ánh sáng, gần phố đi bộ và tiện ích trung tâm.','sale',8200000000,NULL,92,3,2,'Đông Nam','Sổ hồng',NULL,0,'12 Nguyễn Huệ','approved',NULL,1),
(5,2,2,4,'Nhà phố hẻm xe hơi tại Võ Thị Sáu','Nhà mới sửa, khu dân cư yên tĩnh, thuận tiện đi trung tâm.','sale',10300000000,NULL,76,4,3,'Tây Nam','Sổ hồng',NULL,0,'28 Trần Quốc Thảo','pending',NULL,0),
(6,2,1,5,'Căn hộ 1PN cho thuê tại Thảo Điền','Đầy đủ nội thất, có ban công và chỗ đậu xe.','rent',12500000,25000000,48,1,1,'Đông','Sổ hồng','full',1,'18 Quốc Hương','rejected','Thiếu thông tin xác nhận quyền cho thuê.',0),
(7,2,1,2,'Căn hộ 2PN gần chợ Bến Thành','Căn hộ nội thất cơ bản, có thể nuôi thú cưng.','rent',16000000,32000000,68,2,2,'Nam','Sổ hồng','basic',1,'45 Lê Thị Hồng Gấm','hidden',NULL,0),
(8,2,4,8,'Đất nền vuông vức tại Phường Hải Châu 1','Lô đất đường rộng, phù hợp xây nhà ở hoặc văn phòng.','sale',4600000000,NULL,105,0,0,'Đông','Sổ đỏ',NULL,0,'70 Nguyễn Văn Linh','closed',NULL,0),
(9,2,2,7,'Nhà phố 4 tầng gần công viên Dịch Vọng','Có gara, giếng trời và khu bếp riêng.','sale',11800000000,NULL,82,4,4,'Đông Bắc','Sổ hồng',NULL,0,'16 Trần Thái Tông','approved',NULL,1),
(10,2,5,6,'Văn phòng sáng thoáng tại Phường Điện Biên','Mặt bằng phù hợp nhóm 15 đến 20 người, có thang máy.','rent',30000000,60000000,120,0,2,'Đông','Hợp đồng thuê','basic',0,'90 Điện Biên Phủ','pending',NULL,0),
(11,2,3,5,'Biệt thự sân vườn tại Thảo Điền','Không gian yên tĩnh, có hồ bơi và sân vườn riêng.','sale',28500000000,NULL,260,5,5,'Nam','Sổ hồng',NULL,0,'35 Nguyễn Văn Hưởng','rejected','Ảnh và diện tích thực tế chưa được xác minh.',0),
(12,2,1,3,'Căn hộ 2PN tại Đa Kao','Tầng cao, nội thất mới, gần trường học và bệnh viện.','sale',5900000000,NULL,74,2,2,'Tây Bắc','Sổ hồng',NULL,0,'8 Nguyễn Đình Chiểu','approved',NULL,1),
(13,2,6,8,'Condotel hướng biển tại Phường Hải Châu 1','Căn hộ nghỉ dưỡng có ban công và dịch vụ dọn phòng.','rent',14500000,29000000,55,1,1,'Đông','Hợp đồng mua bán','full',0,'22 Võ Nguyên Giáp','closed',NULL,0);
INSERT INTO property_images (property_id,image_url,is_cover)
SELECT p.id, CONCAT('uploads/sample-property-', LPAD(((p.id - 1) % 10) + 1, 2, '0'), '.jpg'), 1
FROM properties p WHERE p.id BETWEEN 4 AND 13 AND NOT EXISTS (SELECT 1 FROM property_images i WHERE i.property_id=p.id);
COMMIT;
