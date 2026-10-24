# Web kinh doanh bất động sản (PHP · OOP + MVC · Bootstrap 5 · MySQL)

## Chạy trên máy (XAMPP / Laragon, PHP >= 8.0)
1. Copy thư mục vào `htdocs` (hoặc `www`). Bật Apache + MySQL.
2. phpMyAdmin → Import lần lượt: `database/schema.sql` → `database/seed.sql` → `database/procedures.sql`.
3. Copy `config/config.local.example.php` thành `config/config.local.php`, sửa user/pass DB. (File này không commit.)
4. Mở `http://localhost/batdongsan/public/`.

Tài khoản demo (mật khẩu `Admin@123`): `admin@demo.local`, `agent@demo.local`, `customer@demo.local`.

## Deploy free hosting
- Import 3 file SQL qua phpMyAdmin của host. Nếu host **không cho tạo routine**: bỏ qua `procedures.sql`, đặt `'use_routines' => false` trong `config.local.php` (code tự dùng SQL thường).
- Upload toàn bộ project; file `.htaccess` ở gốc tự chuyển request vào `public/`. Nếu link bị lệch, điền `'base_url'` trong config.local.php.

## Cấu trúc
```
config/            cấu hình (config.php + config.local.php riêng từng máy)
database/          schema.sql, seed.sql, procedures.sql
docs/erd.mermaid   sơ đồ ERD (15 bảng)
public/            document root: index.php (front controller), assets/, uploads/
app/core/          App (router), Controller, Model, Database, Auth
app/models/        1 model / bảng chính (User, Property, Location...)
app/controllers/   Home, Auth, Api, Property, Agent, Favorite  +  admin/ (URL /admin/...)
app/views/         layouts/, partials/, trang theo module
app/helpers/       functions.php (e(), base_url(), csrf_field(), flash()...)
```

## Quy ước
- URL: `/controller/action/param` → `ControllerController::action(param)`. `/admin/users/lock/3` → `AdminUsersController::lock(3)` (file trong `controllers/admin/`).
- Model: khai báo `$table`, `$fillable`; dùng `create/update/find/delete` có sẵn; truy vấn riêng viết bằng `$this->query()`; gọi routine bằng `$this->call('sp_ten', [...])`.
- Luôn dùng prepared statement. Mọi form POST có `<?= csrf_field() ?>`; AJAX gửi header `X-CSRF-Token` (hàm `api()` trong `assets/js/app.js` đã làm sẵn).
- AJAX trả JSON qua `$this->json($data, $success, $message)` → `{success, message, data}`.
- Chặn quyền: `$this->requireRole('agent')` ở constructor hoặc đầu action.
- Escape output bằng `e()`.

## Đăng tin môi giới
- Đăng nhập bằng tài khoản môi giới, mở `/agent/create`, điền thông tin và gửi từ 1 đến 10 ảnh JPG/PNG/WEBP (tối đa 5 MB mỗi ảnh).
- Chọn ảnh bìa, vị trí được tải theo tỉnh/thành bằng AJAX; tiện ích được lưu cùng tin đăng.
- Tin mới được lưu với trạng thái `pending` để quản trị viên duyệt.
- Mở `/agent` để xem và lọc tin theo trạng thái; môi giới có thể sửa, ẩn tin đã duyệt, gửi tin đã ẩn duyệt lại hoặc xóa tin của mình.
- Mỗi lần sửa hoặc gửi tin đã ẩn duyệt lại sẽ chuyển tin về `pending` để quản trị viên duyệt lại.

## Phân công (tìm `TODO [A|B|C|D]` trong code)
| | Module | File chính |
|---|---|---|
| A | Tài khoản, danh mục | AuthController (có sẵn), AdminUsers/AdminCatalogController, models User/Location/PropertyType/Amenity |
| B | Môi giới đăng tin | AgentController, model Property/PropertyImage, views/agent |
| C | Tìm kiếm, chi tiết | PropertyController (search có sẵn, làm detail), SavedSearch, Google Maps |
| D | Tương tác + duyệt tin | FavoriteController, Review/Appointment/Report, AdminPropertiesController |

## Git
Nhánh `main` luôn chạy được. Mỗi người `feature/<module>`, commit nhỏ, merge qua Pull Request. Chụp lại lịch sử commit/branch/PR làm minh chứng báo cáo.
