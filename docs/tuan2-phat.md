# Tuần 2 - Phát: Auth, danh mục admin, dropdown Tỉnh/Quận/Phường

## Nhánh và nội dung
| Nhánh | Nội dung | File chính |
|---|---|---|
| `feature/auth` | Validate đăng ký (họ tên, email, SĐT, mật khẩu + nhập lại), chuyển trang theo vai trò, chặn tài khoản locked (cả phiên đang mở), trang hồ sơ (đổi họ tên, SĐT, mật khẩu) | `AuthController`, `ProfileController`, `Auth::validate()`, `User`, `views/profile`, `views/auth` |
| `feature/admin-catalog` | CRUD loại BĐS, tiện ích, tỉnh/thành, quận/huyện, phường/xã (tab Bootstrap + modal), chặn xóa khi đang dùng | `AdminCatalogController`, `CatalogModel`, `Province/District/Ward`, `views/admin/catalog.php` |
| `feature/location-api` | `api/provinces`, `api/districts/{id}`, `api/wards/{id}` trả `{success,message,data}`; `location.js` nạp dropdown phụ thuộc | `ApiController`, `public/assets/js/location.js` |

## Cách dùng dropdown cho Hải (form đăng tin / sửa tin) và Thiện (bộ lọc)
```html
<select id="province" data-target="#district" data-level="districts">...</select>
<select id="district" data-target="#ward" data-level="wards" data-selected="<?= (int)$p['district_id'] ?>"></select>
<select id="ward" name="ward_id" data-selected="<?= (int)$p['ward_id'] ?>"></select>
```
- `data-selected` dùng cho form sửa: giữ lại quận/phường đã chọn.
- Tỉnh của form sửa: đặt `selected` ở `<option>` tỉnh phía server (lấy từ quận của phường).
- Danh mục thêm trong admin có hiệu lực ngay ở form (đọc thẳng từ CSDL).

## Quy tắc đã chốt
- Mật khẩu 6-72 ký tự (72 là giới hạn bcrypt). SĐT không bắt buộc, dạng `0xxxxxxxxx` hoặc `+84xxxxxxxxx`.
- Không xóa được: loại BĐS / phường / quận / tỉnh đang có tin dùng; tỉnh còn quận; quận còn phường; tiện ích đang gắn vào tin.
- `Auth::validate()` chạy mỗi request (1 truy vấn): khóa tài khoản có hiệu lực ngay, đổi vai trò/tên cập nhật ngay.
- Hàm `str_len()` thay `mb_strlen` để không phụ thuộc extension mbstring (một số free hosting tắt).

## Đã kiểm thử
65 kiểm thử HTTP (đăng nhập/đăng ký/khóa/hồ sơ/API/CRUD danh mục/CSRF/phân quyền) và 9 kiểm thử JS (dropdown, chọn sẵn, modal) đều đạt trên PHP 8.3 + MariaDB 10.11.

## Checklist Pull Request
- [ ] Import lại `schema.sql`, `seed.sql`, `procedures.sql` rồi đăng nhập 3 tài khoản demo (mật khẩu `Admin@123`)
- [ ] Khóa `customer@demo.local` bằng phpMyAdmin (`status = 'locked'`) -> phiên đang mở bị đăng xuất, không đăng nhập lại được
- [ ] `/admin/catalog`: thêm, sửa, xóa ở cả 5 tab; thử xóa mục đang dùng
- [ ] Ở tab Phường: chọn tỉnh -> quận tự nạp; bấm Sửa -> modal chọn sẵn đúng tỉnh/quận
- [ ] `/profile`: đổi họ tên, SĐT, mật khẩu; đăng nhập lại bằng mật khẩu mới
