# Triển khai Vercel + Render

Ứng dụng PHP hiện dựng cả giao diện và backend. Vercel chuyển tiếp các request đến dịch vụ PHP trên Render; đây không phải giao diện tĩnh tách riêng.

1. Tạo MySQL có thể truy cập từ Render. phpMyAdmin là công cụ quản lý MySQL; kiểm tra nhà cung cấp cho phép kết nối từ bên ngoài và tài khoản có quyền tạo, gọi procedure. Cấu hình TLS theo hướng dẫn của họ.
2. Với database **mới và trống**, chọn đúng database trên phpMyAdmin rồi import `deploy/empty_database.sql`. File này không tạo hoặc chọn database, không xóa bảng hay procedure và không chứa tài khoản demo. Có thể tạo lại file bằng `python deploy/build_empty_database.py`. Không import file này lên database đã có dữ liệu.
3. Chạy `php deploy/create_admin.php` từ CLI với các biến `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` và tùy chọn `ADMIN_NAME`. Script chỉ tạo tài khoản mới, không ghi đè tài khoản đã có; đặt mật khẩu riêng dài ít nhất 12 ký tự. Không đặt thông tin bí mật vào file được commit.
4. Tạo web service Render dùng Dockerfile ở thư mục gốc. Thiết lập `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_DEBUG=false`. MySQL phải hỗ trợ stored procedure. Nếu nhà cung cấp yêu cầu CA tùy chỉnh, cung cấp file CA trên dịch vụ và đặt đường dẫn bằng `DB_SSL_CA`. Không commit mật khẩu hay file cấu hình chứa bí mật.
5. Để lưu ảnh trên Cloudinary, thiết lập `IMAGE_STORAGE=cloudinary`, `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET` trong Render. Giữ bí mật API secret. XAMPP tiếp tục dùng lưu trữ cục bộ nếu không đặt `IMAGE_STORAGE`.
6. Sau khi có URL Render, sao chép `vercel.example.json` thành `vercel.json` ở thư mục gốc và thay host mẫu bằng URL Render thực tế. Triển khai dự án Vercel, rồi kiểm tra đăng nhập, session, tìm kiếm, tải ảnh và các thao tác POST qua domain Vercel.

Gói Render Free không lưu được file trên filesystem qua lần khởi động lại, ngủ hoặc triển khai lại; hãy cấu hình Cloudinary hoặc dùng dịch vụ trả phí có persistent disk trước khi dùng ảnh thật. Session trên filesystem có thể mất khi dịch vụ khởi động lại.

## Gửi mail quên mật khẩu qua HTTPS (Resend)

Render Free [chặn cổng SMTP 25/465/587](https://render.com/docs/free). Ứng dụng hỗ trợ [Resend API](https://resend.com/docs/api-reference/emails/send-email) qua HTTPS/cổng 443.

1. Tạo tài khoản tại Resend và tạo API key có quyền gửi email tại https://resend.com/api-keys.
2. Trên **Render → backend PHP → Environment**, đặt:

   ```text
   MAIL_TRANSPORT=resend
   RESEND_API_KEY=<API key của bạn>
   MAIL_FROM_EMAIL=onboarding@resend.dev
   MAIL_FROM_NAME=BDS Viet
   ```

3. Lưu cấu hình và triển khai lại backend. Khi dùng `onboarding@resend.dev`, [Resend chỉ cho gửi tới email chủ tài khoản Resend](https://resend.com/docs/knowledge-base/403-error-resend-dev-domain). Đăng ký tài khoản ứng dụng bằng chính email đó rồi thử quên mật khẩu. Muốn gửi cho người dùng bất kỳ, xác minh domain tại https://resend.com/domains và đổi `MAIL_FROM_EMAIL` sang địa chỉ thuộc domain đã xác minh.
4. Local XAMPP dùng cùng API key: thêm vào `config/config.local.php`, giữ nguyên phần cấu hình database:

   ```php
   'mail' => [
       'transport' => 'resend',
       'resend_api_key' => '<API key của bạn>',
       'from_email' => 'onboarding@resend.dev',
       'from_name' => 'BDS Viet',
   ],
   ```

   File này được Git bỏ qua. Không commit API key. PHP cần extension cURL và CA certificates hợp lệ; ứng dụng xác minh chứng chỉ HTTPS.

Kiểm tra cấu hình bằng `php scripts/check_mail.php`. Lệnh chỉ gửi thư kiểm tra khi thêm `--to=email-cua-ban@example.com`. Sau đó thử yêu cầu quên mật khẩu, mở link, đặt mật khẩu mới rồi đăng nhập lại. API nhận thư chưa bảo đảm thư vào Inbox; kiểm tra cả thư rác và trạng thái gửi trong Resend.

Khi chưa cấu hình, trang quên mật khẩu báo dịch vụ chưa sẵn sàng và không tạo token. Nếu nhà cung cấp từ chối gửi, token mới bị thu hồi và lỗi được ghi vào server log. Nhật ký mail không ghi link/token đặt lại mật khẩu. Nếu dùng hosting cho phép SMTP, có thể chọn `MAIL_TRANSPORT=smtp` cùng các biến `SMTP_ENABLED`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` sẵn có.
