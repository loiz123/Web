USE batdongsan;
SET NAMES utf8mb4;

INSERT INTO roles (id, name) VALUES (1,'admin'),(2,'agent'),(3,'customer');

-- Tài khoản demo, mật khẩu chung: Admin@123 (ĐỔI khi deploy thật)
INSERT INTO users (id, role_id, full_name, email, phone, password_hash) VALUES
(1,1,'Quản trị viên','admin@demo.local','0900000001','$2y$10$Wt6GkpLu.nvSxQ2zACWaC.0y0IytIpW2E0fYbDOwaJzt941eSnsZK'),
(2,2,'Môi giới Demo','agent@demo.local','0900000002','$2y$10$Wt6GkpLu.nvSxQ2zACWaC.0y0IytIpW2E0fYbDOwaJzt941eSnsZK'),
(3,3,'Khách Demo','customer@demo.local','0900000003','$2y$10$Wt6GkpLu.nvSxQ2zACWaC.0y0IytIpW2E0fYbDOwaJzt941eSnsZK');

INSERT INTO provinces (id, name) VALUES (1,'TP. Hồ Chí Minh'),(2,'Hà Nội'),(3,'Đà Nẵng');
INSERT INTO districts (id, province_id, name) VALUES
(1,1,'Quận 1'),(2,1,'Quận 3'),(3,1,'TP. Thủ Đức'),(4,2,'Quận Ba Đình'),(5,2,'Quận Cầu Giấy'),(6,3,'Quận Hải Châu');
INSERT INTO wards (id, district_id, name) VALUES
(1,1,'Phường Bến Nghé'),(2,1,'Phường Bến Thành'),(3,1,'Phường Đa Kao'),
(4,2,'Phường Võ Thị Sáu'),(5,3,'Phường Thảo Điền'),(6,4,'Phường Điện Biên'),
(7,5,'Phường Dịch Vọng'),(8,6,'Phường Hải Châu 1');

INSERT INTO property_types (name) VALUES ('Căn hộ'),('Nhà phố'),('Biệt thự'),('Đất nền'),('Văn phòng'),('Condotel');
INSERT INTO amenities (name) VALUES ('Hồ bơi'),('Phòng gym'),('Bảo vệ 24/7'),('Chỗ đậu xe'),('Thang máy'),('Ban công');

INSERT INTO properties (id,user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,status,is_verified) VALUES
(1,2,1,5,'Căn hộ 2PN view sông Thảo Điền','Căn hộ full nội thất, gần metro.','rent',18000000,36000000,70,2,2,'Đông Nam','Sổ hồng','full',1,'12 Xuân Thủy','approved',1),
(2,2,2,3,'Nhà phố 4 tầng trung tâm Quận 1','Hẻm xe hơi, kinh doanh tốt.','sale',12500000000,NULL,60,4,4,'Tây Bắc','Sổ hồng',NULL,0,'45 Hai Bà Trưng','approved',1),
(3,2,4,8,'Đất nền mặt tiền Hải Châu','Đất vuông vức, pháp lý rõ ràng.','sale',4200000000,NULL,100,0,0,'Đông','Sổ đỏ',NULL,0,'Đường Bạch Đằng','pending',0);

-- 10 tin bổ sung: có cả bán/cho thuê và đủ 5 trạng thái kiểm duyệt.
INSERT INTO properties (id,user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,status,reject_reason,is_verified) VALUES
(4,2,1,1,'Căn hộ 3PN ban công rộng tại Bến Nghé','Căn hộ nhiều ánh sáng, gần phố đi bộ và tiện ích trung tâm.','sale',8200000000,NULL,92,3,2,'Đông Nam','Sổ hồng',NULL,0,'12 Nguyễn Huệ','approved',NULL,1),
(5,2,2,4,'Nhà phố hẻm xe hơi tại Võ Thị Sáu','Nhà mới sửa, khu dân cư yên tĩnh, thuận tiện đi Quận 1.','sale',10300000000,NULL,76,4,3,'Tây Nam','Sổ hồng',NULL,0,'28 Trần Quốc Thảo','pending',NULL,0),
(6,2,1,5,'Căn hộ 1PN cho thuê tại Thảo Điền','Đầy đủ nội thất, có ban công và chỗ đậu xe.','rent',12500000,25000000,48,1,1,'Đông','Sổ hồng','full',1,'18 Quốc Hương','rejected','Thiếu thông tin xác nhận quyền cho thuê.',0),
(7,2,1,2,'Căn hộ 2PN gần chợ Bến Thành','Căn hộ nội thất cơ bản, có thể nuôi thú cưng.','rent',16000000,32000000,68,2,2,'Nam','Sổ hồng','basic',1,'45 Lê Thị Hồng Gấm','hidden',NULL,0),
(8,2,4,8,'Đất nền vuông vức tại Hải Châu','Lô đất đường rộng, phù hợp xây nhà ở hoặc văn phòng.','sale',4600000000,NULL,105,0,0,'Đông','Sổ đỏ',NULL,0,'70 Nguyễn Văn Linh','closed',NULL,0),
(9,2,2,7,'Nhà phố 4 tầng gần công viên Cầu Giấy','Có gara, giếng trời và khu bếp riêng.','sale',11800000000,NULL,82,4,4,'Đông Bắc','Sổ hồng',NULL,0,'16 Trần Thái Tông','approved',NULL,1),
(10,2,5,6,'Văn phòng sáng thoáng tại Hải Châu','Mặt bằng phù hợp nhóm 15 đến 20 người, có thang máy.','rent',30000000,60000000,120,0,2,'Đông','Hợp đồng thuê','basic',0,'90 Bạch Đằng','pending',NULL,0),
(11,2,3,5,'Biệt thự sân vườn tại Thảo Điền','Không gian yên tĩnh, có hồ bơi và sân vườn riêng.','sale',28500000000,NULL,260,5,5,'Nam','Sổ hồng',NULL,0,'35 Nguyễn Văn Hưởng','rejected','Ảnh và diện tích thực tế chưa được xác minh.',0),
(12,2,1,3,'Căn hộ 2PN tại Đa Kao','Tầng cao, nội thất mới, gần trường học và bệnh viện.','sale',5900000000,NULL,74,2,2,'Tây Bắc','Sổ hồng',NULL,0,'8 Nguyễn Đình Chiểu','approved',NULL,1),
(13,2,6,8,'Condotel hướng biển tại Hải Châu','Căn hộ nghỉ dưỡng có ban công và dịch vụ dọn phòng.','rent',14500000,29000000,55,1,1,'Đông','Hợp đồng mua bán','full',0,'22 Võ Nguyên Giáp','closed',NULL,0);

-- Ảnh bìa local trong public/uploads; ba tin gốc dùng chung bộ ảnh mẫu.
INSERT INTO property_images (property_id,image_url,is_cover) VALUES
(1,'uploads/sample-property-01.jpg',1),
(2,'uploads/sample-property-02.jpg',1),
(3,'uploads/sample-property-03.jpg',1),
(4,'uploads/sample-property-04.jpg',1),
(5,'uploads/sample-property-05.jpg',1),
(6,'uploads/sample-property-06.jpg',1),
(7,'uploads/sample-property-07.jpg',1),
(8,'uploads/sample-property-08.jpg',1),
(9,'uploads/sample-property-09.jpg',1),
(10,'uploads/sample-property-10.jpg',1),
(11,'uploads/sample-property-01.jpg',1),
(12,'uploads/sample-property-02.jpg',1),
(13,'uploads/sample-property-03.jpg',1);
