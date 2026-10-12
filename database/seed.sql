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

INSERT INTO properties (user_id,type_id,ward_id,title,description,listing_type,price,deposit,area,bedrooms,bathrooms,direction,legal_status,furniture,pets_allowed,address_detail,status,is_verified) VALUES
(2,1,5,'Căn hộ 2PN view sông Thảo Điền','Căn hộ full nội thất, gần metro.','rent',18000000,36000000,70,2,2,'Đông Nam','Sổ hồng','full',1,'12 Xuân Thủy','approved',1),
(2,2,3,'Nhà phố 4 tầng trung tâm Quận 1','Hẻm xe hơi, kinh doanh tốt.','sale',12500000000,NULL,60,4,4,'Tây Bắc','Sổ hồng',NULL,0,'45 Hai Bà Trưng','approved',1),
(2,4,8,'Đất nền mặt tiền Hải Châu','Đất vuông vức, pháp lý rõ ràng.','sale',4200000000,NULL,100,0,0,'Đông','Sổ đỏ',NULL,0,'Đường Bạch Đằng','pending',0);
