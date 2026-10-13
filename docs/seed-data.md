# Dữ liệu mẫu tin đăng

`database/seed.sql` tạo 13 tin đăng cho môi giới demo (3 tin ban đầu và 10 tin bổ sung). Dữ liệu có cả `sale` và `rent`, đủ trạng thái `pending`, `approved`, `rejected`, `hidden`, `closed`; mỗi tin có một ảnh bìa local.

## Nạp dữ liệu

1. Mở phpMyAdmin và chọn tab **Import**.
2. Import `database/schema.sql` trước, sau đó import `database/seed.sql`.
3. Không import lặp lại `seed.sql` trên cùng CSDL vì các bảng có dữ liệu ID cố định. `schema.sql` xóa và tạo lại các bảng trước khi seed.

Ảnh được lưu trong `public/uploads/sample-property-01.jpg` đến `sample-property-10.jpg`; SQL lưu đường dẫn tương đối từ `public/`. Ảnh lấy từ Unsplash và được đưa vào repo để giao diện mẫu không cần tải ảnh từ mạng lúc chạy.

## Kiểm tra

Đếm tin theo loại giao dịch và trạng thái:

```sql
SELECT listing_type, status, COUNT(*) AS total
FROM properties
GROUP BY listing_type, status
ORDER BY listing_type, status;
```

Kiểm tra tin nào chưa có ảnh:

```sql
SELECT p.id, p.title
FROM properties p
LEFT JOIN property_images i ON i.property_id = p.id
GROUP BY p.id, p.title
HAVING COUNT(i.id) = 0;
```

Kết quả truy vấn thứ hai phải rỗng. Tài khoản môi giới mẫu là `agent@demo.local`; mật khẩu demo được ghi trong `database/seed.sql`.