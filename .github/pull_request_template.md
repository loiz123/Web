## Làm gì

<!-- 1-3 câu: Pull Request này thêm, sửa hoặc xóa gì? -->

## Issue liên quan

Closes #

## Loại thay đổi

- [ ] Chức năng mới
- [ ] Sửa lỗi
- [ ] Giao diện (Bootstrap, responsive)
- [ ] Thay đổi CSDL (schema, routine, dữ liệu mẫu)
- [ ] Tài liệu hoặc báo cáo
- [ ] Khác:

## Test thế nào

<!-- Các bước để reviewer chạy lại, ví dụ:
1. Đăng nhập agent@demo.local
2. Vào /agent/create, nhập form, bấm Đăng tin
3. Kiểm tra trong phpMyAdmin có dòng mới với status = pending -->

1.
2.
3.

## Ảnh chụp (nếu có giao diện)

<!-- Dán ảnh máy tính và điện thoại. Ảnh này dùng luôn cho báo cáo Chương 3. -->

## Checklist của người tạo PR

- [ ] Nhánh tạo từ `develop` và PR trỏ vào `develop` (không vào `main`)
- [ ] Chạy được trên máy mình, đã đăng nhập thử đúng vai trò cần dùng
- [ ] Chỉ sửa file thuộc phần mình phụ trách; file dùng chung (`app/core`, layout, `schema.sql`) đã báo Phát
- [ ] Truy vấn dùng prepared statement; dữ liệu hiển thị qua `e()`
- [ ] Form POST có `csrf_field()`; AJAX dùng hàm `api()` (đã gửi token CSRF)
- [ ] Trang cần đăng nhập đã gọi `requireRole(...)` hoặc `requireLogin()`
- [ ] AJAX trả JSON dạng `{success, message, data}`
- [ ] Routine mới nằm trong `database/procedures/<tên>.sql`
- [ ] Nếu đổi cấu trúc bảng: đã cập nhật `database/schema.sql`, `docs/schema.md`, `docs/erd.mermaid`, `docs/erd.png`
- [ ] Không commit `config/config.local.php`, mật khẩu DB, Google Maps API key hay file trong `public/uploads`
- [ ] Giao diện xem được ở 360px (điện thoại) và máy tính
- [ ] Đã xóa code thừa, `var_dump`, `print_r`, `console.log`

## Câu hỏi cho reviewer

<!-- Chỗ nào bạn chưa chắc? Reviewer cần để lại ít nhất một câu hỏi "tại sao làm vậy" trong PR. -->

## Người review (vòng tròn)

Thiện review Phát, Phát review Quốc, Quốc review Hải, Hải review Thiện.
Cần ít nhất 1 người approve trước khi merge.
