# Tài liệu cơ sở dữ liệu (schema)

Mô tả đầy đủ 15 bảng của website bất động sản. Nội dung được lập từ chính CSDL tạo bởi `database/schema.sql`, nên khớp với code. Khi đổi cấu trúc bảng, cập nhật cả `schema.sql` lẫn tài liệu này (xem mục 7).

## 1. Tổng quan

| Mục | Giá trị |
| --- | --- |
| Hệ quản trị | MySQL 5.7 trở lên hoặc MariaDB 10.3 trở lên |
| Tên CSDL | `batdongsan` |
| Bảng mã | utf8mb4, collation utf8mb4_unicode_ci |
| Engine | InnoDB (có khóa ngoại) |
| Số bảng | 15 |
| Quản lý | phpMyAdmin; sơ đồ quan hệ xem ở tab Designer hoặc `docs/erd.png` |
| File nguồn | `database/schema.sql`, `seed.sql`, `procedures.sql` (import theo thứ tự này) |

### Bảng và người phụ trách

| # | Bảng | Phụ trách | Mức triển khai | Mô tả ngắn |
| --- | --- | --- | --- | --- |
| 1 | `roles` | Phát | Dữ liệu cấu hình, app đọc | Ba vai trò của hệ thống: admin, agent (môi giới), customer (khách) |
| 2 | `users` | Phát | Đầy đủ | Tài khoản người dùng |
| 3 | `provinces` | Phát | Đầy đủ | Tỉnh/thành phố |
| 4 | `districts` | Phát | Đầy đủ | Quận/huyện, thuộc một tỉnh |
| 5 | `wards` | Phát | Đầy đủ | Phường/xã, thuộc một quận |
| 6 | `property_types` | Phát | Đầy đủ | Loại bất động sản: căn hộ, nhà phố, biệt thự, đất nền, văn phòng, condotel |
| 7 | `amenities` | Phát | Đầy đủ | Danh sách tiện ích (hồ bơi, gym, bảo vệ 24/7...) |
| 8 | `properties` | Hải | Đầy đủ | Tin đăng bán hoặc cho thuê |
| 9 | `property_images` | Hải | Đầy đủ | Ảnh của tin |
| 10 | `property_amenities` | Hải | Đầy đủ | Bảng trung gian nhiều-nhiều giữa properties và amenities |
| 11 | `favorites` | Thiện | Đầy đủ | Tin khách đã lưu |
| 12 | `reviews` | Thiện | Đầy đủ | Đánh giá 1 đến 5 sao và bình luận |
| 13 | `saved_searches` | Thiện | Nếu còn thời gian | Bộ lọc tìm kiếm khách đã lưu |
| 14 | `viewing_appointments` | Quốc | Đầy đủ | Lịch hẹn xem nhà do khách đặt; môi giới của tin xác nhận hoặc hủy |
| 15 | `reports` | Quốc | Đầy đủ | Báo cáo tin vi phạm (tin giả, giá ảo...) do khách gửi; admin xử lý |

## 2. Quy ước

- Tên bảng và cột dùng `snake_case`, tên bảng số nhiều (`users`, `properties`).
- Khóa chính là cột `id` kiểu số nguyên không dấu, tự tăng; bảng trung gian dùng khóa chính kép.
- Khóa ngoại đặt tên `<bảng_số_ít>_id` (`user_id`, `property_id`); riêng `customer_id` và `reporter_id` cùng trỏ về `users`.
- Thời gian dùng `DATETIME` với `created_at` mặc định giờ hiện tại; `properties.updated_at` tự cập nhật.
- Tiền dùng `DECIMAL(18,2)`, đơn vị VNĐ. Diện tích tính bằng m².
- Giá trị liệt kê dùng `ENUM`, viết thường, tiếng Anh (xem mục 3).
- Mọi truy vấn trong code dùng prepared statement (PDO), không nối chuỗi SQL.

## 3. Giá trị liệt kê và luồng trạng thái

| Cột | Giá trị | Ý nghĩa |
| --- | --- | --- |
| `users.status` | `active`, `locked` | Hoạt động, bị admin khóa |
| `properties.listing_type` | `sale`, `rent` | Bán, cho thuê |
| `properties.furniture` | `none`, `basic`, `full` | Không nội thất, cơ bản, đầy đủ (chỉ cho thuê) |
| `properties.status` | `pending`, `approved`, `rejected`, `hidden`, `closed` | Chờ duyệt, đã duyệt, bị từ chối, môi giới đang ẩn, đã bán hoặc đã cho thuê |
| `viewing_appointments.status` | `pending`, `confirmed`, `cancelled`, `done` | Chờ xác nhận, đã xác nhận, đã hủy, đã xem xong |
| `reports.status` | `open`, `resolved`, `dismissed` | Mới, đã xử lý, bỏ qua |

**Luồng trạng thái của tin (`properties.status`):**

1. Môi giới đăng tin: `pending`.
2. Admin duyệt: `approved` (hiện cho khách) hoặc từ chối: `rejected` (kèm `reject_reason`, môi giới sửa rồi gửi lại thành `pending`).
3. Môi giới có thể đổi tin `approved` thành `hidden` (ẩn tạm, hiện lại được) hoặc `closed` (đã bán hoặc cho thuê).
4. Khách chỉ thấy tin `approved`. Chủ tin và admin xem được mọi trạng thái.

## 4. Quan hệ giữa các bảng

| Bảng con | Cột | Trỏ tới | Khi xóa bản ghi cha |
| --- | --- | --- | --- |
| `users` | `role_id` | `roles.id` | Chặn xóa (RESTRICT) |
| `districts` | `province_id` | `provinces.id` | Xóa theo (CASCADE) |
| `wards` | `district_id` | `districts.id` | Xóa theo (CASCADE) |
| `properties` | `type_id` | `property_types.id` | Chặn xóa (RESTRICT) |
| `properties` | `user_id` | `users.id` | Chặn xóa (RESTRICT) |
| `properties` | `ward_id` | `wards.id` | Chặn xóa (RESTRICT) |
| `property_images` | `property_id` | `properties.id` | Xóa theo (CASCADE) |
| `property_amenities` | `amenity_id` | `amenities.id` | Xóa theo (CASCADE) |
| `property_amenities` | `property_id` | `properties.id` | Xóa theo (CASCADE) |
| `favorites` | `property_id` | `properties.id` | Xóa theo (CASCADE) |
| `favorites` | `user_id` | `users.id` | Xóa theo (CASCADE) |
| `reviews` | `property_id` | `properties.id` | Xóa theo (CASCADE) |
| `reviews` | `user_id` | `users.id` | Xóa theo (CASCADE) |
| `saved_searches` | `user_id` | `users.id` | Xóa theo (CASCADE) |
| `viewing_appointments` | `property_id` | `properties.id` | Xóa theo (CASCADE) |
| `viewing_appointments` | `customer_id` | `users.id` | Xóa theo (CASCADE) |
| `reports` | `property_id` | `properties.id` | Xóa theo (CASCADE) |
| `reports` | `reporter_id` | `users.id` | Xóa theo (CASCADE) |

Sơ đồ quan hệ: `docs/erd.png` (ảnh), `docs/erd.mermaid` (mã nguồn Mermaid), hoặc tab Designer của phpMyAdmin.

## 5. Chi tiết từng bảng

### 5.1. `roles`

Ba vai trò của hệ thống: admin, agent (môi giới), customer (khách). Dữ liệu cố định, nạp bằng seed.sql.

**Phụ trách:** Phát · **Mức triển khai:** Dữ liệu cấu hình, app đọc

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | tinyint(3) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `name` | varchar(20) | Không | UQ |  | Tên |

**Chỉ mục:** `name` (duy nhất, name)

### 5.2. `users`

Tài khoản người dùng. Mật khẩu lưu dạng băm (password_hash), không lưu mật khẩu thật. status = locked thì không đăng nhập được.

**Phụ trách:** Phát · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `role_id` | tinyint(3) unsigned | Không | FK |  | FK roles. 1 = admin, 2 = agent, 3 = customer (theo seed.sql) |
| `full_name` | varchar(100) | Không |  |  | Họ và tên |
| `email` | varchar(100) | Không | UQ |  | Duy nhất; dùng để đăng nhập |
| `phone` | varchar(15) | Có |  | NULL | Số điện thoại |
| `password_hash` | varchar(255) | Không |  |  | Kết quả của password_hash() trong PHP, không phải mật khẩu thật |
| `avatar` | varchar(255) | Có |  | NULL | Đường dẫn ảnh đại diện |
| `bio` | text | Có |  | NULL | Giới thiệu bản thân (môi giới) |
| `status` | enum('active','locked') | Không |  | active | active hoặc locked |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |

**Chỉ mục:** `email` (duy nhất, email)

### 5.3. `provinces`

Tỉnh/thành phố. Admin thêm, sửa, xóa; dùng cho dropdown phụ thuộc Tỉnh, Quận, Phường.

**Phụ trách:** Phát · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `name` | varchar(100) | Không |  |  | Tên |

### 5.4. `districts`

Quận/huyện, thuộc một tỉnh. Xóa tỉnh thì xóa luôn các quận của tỉnh đó.

**Phụ trách:** Phát · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `province_id` | int(10) unsigned | Không | FK |  | FK provinces |
| `name` | varchar(100) | Không |  |  | Tên |

### 5.5. `wards`

Phường/xã, thuộc một quận. Là cấp địa chỉ nhỏ nhất mà tin đăng trỏ tới.

**Phụ trách:** Phát · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `district_id` | int(10) unsigned | Không | FK |  | FK districts |
| `name` | varchar(100) | Không |  |  | Tên |

### 5.6. `property_types`

Loại bất động sản: căn hộ, nhà phố, biệt thự, đất nền, văn phòng, condotel.

**Phụ trách:** Phát · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `name` | varchar(50) | Không |  |  | Tên |

### 5.7. `amenities`

Danh sách tiện ích (hồ bơi, gym, bảo vệ 24/7...). Gán cho tin qua bảng property_amenities.

**Phụ trách:** Phát · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `name` | varchar(100) | Không |  |  | Tên |

### 5.8. `properties`

Tin đăng bán hoặc cho thuê. Môi giới tạo với status = pending, admin duyệt thành approved hoặc từ chối (rejected, kèm reject_reason). Chỉ tin approved hiện ra cho khách.

**Phụ trách:** Hải · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `user_id` | int(10) unsigned | Không | FK |  | FK users: môi giới đăng tin |
| `type_id` | int(10) unsigned | Không | FK |  | FK property_types |
| `ward_id` | int(10) unsigned | Không | FK |  | FK wards; quận và tỉnh suy ra qua wards, districts |
| `title` | varchar(200) | Không |  |  | Tiêu đề tin |
| `description` | text | Có |  | NULL | Mô tả chi tiết |
| `listing_type` | enum('sale','rent') | Không |  |  | sale (bán) hoặc rent (cho thuê) |
| `price` | decimal(18,2) | Không |  |  | Giá bán, hoặc giá thuê mỗi tháng nếu rent (đơn vị VNĐ) |
| `deposit` | decimal(18,2) | Có |  | NULL | Tiền cọc, chỉ dùng khi rent |
| `area` | float | Không |  |  | Diện tích m² |
| `bedrooms` | tinyint(3) unsigned | Không |  | 0 | Số phòng ngủ |
| `bathrooms` | tinyint(3) unsigned | Không |  | 0 | Số phòng tắm |
| `direction` | varchar(30) | Có |  | NULL | Hướng nhà (Đông, Tây Nam...) |
| `legal_status` | varchar(100) | Có |  | NULL | Sổ hồng, sổ đỏ, hợp đồng mua bán... |
| `furniture` | enum('none','basic','full') | Có |  | NULL | none, basic hoặc full; chỉ dùng khi rent |
| `pets_allowed` | tinyint(1) | Không |  | 0 | 1 = cho nuôi thú cưng |
| `address_detail` | varchar(200) | Có |  | NULL | Số nhà, tên đường |
| `latitude` | decimal(10,7) | Có |  | NULL | Vĩ độ cho Google Maps; có thể trống |
| `longitude` | decimal(10,7) | Có |  | NULL | Kinh độ cho Google Maps; có thể trống |
| `status` | enum('pending','approved','rejected','hidden','closed') | Không |  | pending | pending, approved, rejected, hidden hoặc closed (xem luồng trạng thái) |
| `reject_reason` | varchar(255) | Có |  | NULL | Lý do admin từ chối, môi giới đọc được ở dashboard |
| `is_verified` | tinyint(1) | Không |  | 0 | 1 = đã xác thực (tick xanh) |
| `views` | int(10) unsigned | Không |  | 0 | Tăng mỗi lần xem trang chi tiết |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |
| `updated_at` | datetime | Không |  | CURRENT_TIMESTAMP | Tự cập nhật mỗi lần sửa |

**Chỉ mục:** `idx_prop_created` (created_at); `idx_prop_search` (status, listing_type, price)

### 5.9. `property_images`

Ảnh của tin. CSDL chỉ lưu đường dẫn tương đối trong public/ (ví dụ uploads/properties/abc.jpg). Quy ước: mỗi tin có một ảnh is_cover = 1.

**Phụ trách:** Hải · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `property_id` | int(10) unsigned | Không | FK |  | FK properties |
| `image_url` | varchar(255) | Không |  |  | Đường dẫn tương đối trong public/ |
| `is_cover` | tinyint(1) | Không |  | 0 | 1 = ảnh bìa hiện trên thẻ tin |

### 5.10. `property_amenities`

Bảng trung gian nhiều-nhiều giữa properties và amenities. Khóa chính kép (property_id, amenity_id).

**Phụ trách:** Hải · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `property_id` | int(10) unsigned | Không | PK, FK |  | FK properties (khóa chính kép) |
| `amenity_id` | int(10) unsigned | Không | PK, FK |  | FK amenities (khóa chính kép) |

### 5.11. `favorites`

Tin khách đã lưu. Khóa chính kép (user_id, property_id) nên mỗi người chỉ lưu một tin một lần.

**Phụ trách:** Thiện · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `user_id` | int(10) unsigned | Không | PK, FK |  | FK users (khóa chính kép) |
| `property_id` | int(10) unsigned | Không | PK, FK |  | FK properties (khóa chính kép) |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |

### 5.12. `reviews`

Đánh giá 1 đến 5 sao và bình luận. Mỗi người chỉ đánh giá một lần cho mỗi tin (unique property_id + user_id).

**Phụ trách:** Thiện · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `property_id` | int(10) unsigned | Không | FK |  | FK properties |
| `user_id` | int(10) unsigned | Không | FK |  | FK users |
| `rating` | tinyint(3) unsigned | Không |  |  | 1 đến 5 (ràng buộc CHECK chk_rating) |
| `comment` | varchar(500) | Có |  | NULL | Nội dung bình luận |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |

**Chỉ mục:** `uq_review` (duy nhất, property_id, user_id)

**Ràng buộc CHECK:** `chk_rating` giữ `rating` trong khoảng 1 đến 5.

### 5.13. `saved_searches`

Bộ lọc tìm kiếm khách đã lưu. Cột filters chứa JSON, ví dụ {"keyword":"thảo điền","listing_type":"rent","max_price":20000000}.

**Phụ trách:** Thiện · **Mức triển khai:** Nếu còn thời gian

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `user_id` | int(10) unsigned | Không | FK |  | FK users |
| `name` | varchar(100) | Không |  |  | Tên do khách đặt cho bộ lọc |
| `filters` | text | Không |  |  | Chuỗi JSON bộ lọc |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |

### 5.14. `viewing_appointments`

Lịch hẹn xem nhà do khách đặt; môi giới của tin xác nhận hoặc hủy. Môi giới suy ra từ properties.user_id nên không lưu riêng.

**Phụ trách:** Quốc · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `property_id` | int(10) unsigned | Không | FK |  | FK properties |
| `customer_id` | int(10) unsigned | Không | FK |  | FK users: khách đặt lịch |
| `scheduled_at` | datetime | Không |  |  | Ngày giờ khách muốn xem nhà |
| `note` | varchar(255) | Có |  | NULL | Ghi chú của khách |
| `status` | enum('pending','confirmed','cancelled','done') | Không |  | pending | pending, confirmed, cancelled hoặc done |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |

### 5.15. `reports`

Báo cáo tin vi phạm (tin giả, giá ảo...) do khách gửi; admin xử lý.

**Phụ trách:** Quốc · **Mức triển khai:** Đầy đủ

| Cột | Kiểu | Null | Khóa | Mặc định | Mô tả |
| --- | --- | --- | --- | --- | --- |
| `id` | int(10) unsigned | Không | PK | auto_increment | Khóa chính, tự tăng |
| `property_id` | int(10) unsigned | Không | FK |  | FK properties |
| `reporter_id` | int(10) unsigned | Không | FK |  | FK users: người báo cáo |
| `reason` | varchar(255) | Không |  |  | Lý do báo cáo |
| `status` | enum('open','resolved','dismissed') | Không |  | open | open, resolved hoặc dismissed |
| `created_at` | datetime | Không |  | CURRENT_TIMESTAMP | Thời điểm tạo |

## 6. Routine (stored procedure) hiện có

Nằm trong `database/procedures.sql`. Khi hosting không cho tạo routine, đặt `use_routines = false` trong `config/config.local.php` để code dùng SQL thường.

| Routine | Tham số | Chức năng | Chủ |
| --- | --- | --- | --- |
| `sp_latest_properties` | `p_limit` | Tin mới nhất đã duyệt, kèm ảnh bìa, loại, phường, quận | Thiện |
| `sp_search_properties` | `p_keyword`, `p_listing_type`, `p_district_id`, `p_min_price`, `p_max_price`, `p_limit`, `p_offset` | Tìm kiếm có lọc; tham số NULL nghĩa là bỏ qua điều kiện | Thiện |

Routine mới của từng người viết trong `database/procedures/<tên>.sql` (`phat.sql`, `hai.sql`, `thien.sql`, `quoc.sql`), đặt tên dạng `sp_<hành_động>_<đối_tượng>`.

## 7. Quy trình thay đổi schema

1. Chỉ sửa cấu trúc bảng bằng cách sửa `database/schema.sql` (hoặc thêm file migration nhỏ trong `database/`), không sửa trực tiếp trên phpMyAdmin của host rồi quên cập nhật file.
2. Tạo Pull Request vào `develop`, ghi rõ bảng và cột đổi, gắn Phát review.
3. Sau khi merge, cả nhóm import lại CSDL trên máy mình và cập nhật `docs/schema.md`, `docs/erd.mermaid`, `docs/erd.png`.
4. Cột mới nên cho phép NULL hoặc có giá trị mặc định để không làm hỏng dữ liệu mẫu.

## 8. Dữ liệu mẫu và tài khoản demo

`seed.sql` nạp 3 vai trò, 3 tài khoản, 3 tỉnh, 6 quận, 8 phường, 6 loại BĐS, 6 tiện ích và 3 tin mẫu (2 tin `approved`, 1 tin `pending`). Hải bổ sung thêm ít nhất 10 tin mẫu (xem `docs/seed-data.md`).

| Email | Vai trò | Mật khẩu |
| --- | --- | --- |
| `admin@demo.local` | admin | `Admin@123` |
| `agent@demo.local` | agent | `Admin@123` |
| `customer@demo.local` | customer | `Admin@123` |

Đổi mật khẩu các tài khoản này khi deploy thật.
