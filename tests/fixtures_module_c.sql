-- Chỉ chạy trên CSDL kiểm thử tạo từ schema.sql và seed.sql.
-- Các vị trí là dữ liệu giả lập, không phải vị trí bất động sản thực.
INSERT INTO properties (id,user_id,type_id,ward_id,title,description,listing_type,price,area,bedrooms,bathrooms,address_detail,latitude,longitude,status)
VALUES
(14,2,1,5,'Fixture 14','Du lieu kiem thu','sale',1500000000,25,1,1,'Vi tri gia lap',10.803,106.735,'approved'),
(15,2,1,5,'Fixture 15','Du lieu kiem thu','sale',1800000000,600,5,1,'Vi tri gia lap',10.804,106.736,'approved'),
(16,2,1,5,'Fixture 16','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.805,106.737,'approved'),
(17,2,1,5,'Fixture 17','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.806,106.738,'approved'),
(18,2,1,5,'Fixture 18','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.807,106.739,'approved'),
(19,2,1,5,'Fixture 19','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.808,106.740,'approved'),
(20,2,1,5,'Fixture 20','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.809,106.741,'approved'),
(21,2,1,5,'Fixture 21','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.810,106.742,'approved'),
(22,2,1,5,'Fixture 22','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.811,106.743,'approved'),
(23,2,1,5,'Fixture 23','Du lieu kiem thu','sale',1800000000,70,2,1,'Vi tri gia lap',10.812,106.744,'approved');
