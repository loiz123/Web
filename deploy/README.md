# Triển khai Vercel + Render

Ứng dụng PHP hiện dựng cả giao diện và backend. Vercel chuyển tiếp các request đến dịch vụ PHP trên Render; đây không phải giao diện tĩnh tách riêng.

1. Tạo MySQL có thể truy cập từ Render. phpMyAdmin là công cụ quản lý MySQL; kiểm tra nhà cung cấp cho phép kết nối từ bên ngoài và tài khoản có quyền tạo, gọi procedure. Cấu hình TLS theo hướng dẫn của họ.
2. Với database **mới và trống**, chọn đúng database trên phpMyAdmin rồi import `deploy/empty_database.sql`. File này không tạo hoặc chọn database, không xóa bảng hay procedure và không chứa tài khoản demo. Có thể tạo lại file bằng `python deploy/build_empty_database.py`. Không import file này lên database đã có dữ liệu.
3. Chạy `php deploy/create_admin.php` từ CLI với các biến `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` và tùy chọn `ADMIN_NAME`. Script chỉ tạo tài khoản mới, không ghi đè tài khoản đã có; đặt mật khẩu riêng dài ít nhất 12 ký tự. Không đặt thông tin bí mật vào file được commit.
4. Tạo web service Render dùng Dockerfile ở thư mục gốc. Thiết lập `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_DEBUG=false`. MySQL phải hỗ trợ stored procedure. Nếu nhà cung cấp yêu cầu CA tùy chỉnh, cung cấp file CA trên dịch vụ và đặt đường dẫn bằng `DB_SSL_CA`. Không commit mật khẩu hay file cấu hình chứa bí mật.
5. Để lưu ảnh trên Cloudinary, thiết lập `IMAGE_STORAGE=cloudinary`, `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET` trong Render. Giữ bí mật API secret. XAMPP tiếp tục dùng lưu trữ cục bộ nếu không đặt `IMAGE_STORAGE`.
6. Sau khi có URL Render, sao chép `vercel.example.json` thành `vercel.json` ở thư mục gốc và thay host mẫu bằng URL Render thực tế. Triển khai dự án Vercel, rồi kiểm tra đăng nhập, session, tìm kiếm, tải ảnh và các thao tác POST qua domain Vercel.

Gói Render Free không lưu được file trên filesystem qua lần khởi động lại, ngủ hoặc triển khai lại; hãy cấu hình Cloudinary hoặc dùng dịch vụ trả phí có persistent disk trước khi dùng ảnh thật. Session trên filesystem có thể mất khi dịch vụ khởi động lại.
