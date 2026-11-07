# Báo cáo sửa lỗi develop

## Đã sửa

- Bỏ phương thức `wantsJson` khai báo sai phạm vi trong `ReviewController`; sử dụng phương thức được bảo vệ của lớp cha, tránh PHP fatal error.
- Chỉ cho đánh giá tin đã duyệt; từ chối đánh giá trùng bằng kiểm tra tại controller và ràng buộc duy nhất của CSDL. Giao diện thông báo khi đã đánh giá. Model không cập nhật đánh giá cũ và không yêu cầu extension mbstring để cắt bình luận.
- Không báo đã lưu yêu thích khi câu lệnh INSERT không thêm dòng nào.
- Điều phối yêu cầu Nominatim bằng file lock dùng chung trên một máy chủ, thay cho giới hạn theo từng session. Giới hạn này không đồng bộ giữa nhiều máy chủ.

## Đã kiểm tra

- Kiểm tra cú pháp PHP toàn bộ thư mục app; tải ReviewController cùng lớp cha thành công.
- Trên cơ sở dữ liệu thử nghiệm riêng: tạo đánh giá thành công, đánh giá trùng bị từ chối, điểm ban đầu được giữ nguyên.
- `git diff --check` không báo lỗi định dạng.

## Chưa hoàn tất

- 13 tin trong seed thiếu tọa độ thực. Không tự gán tọa độ khi chưa xác minh đúng địa chỉ; cần bổ sung tọa độ đã kiểm chứng và cập nhật dữ liệu đã triển khai. Schema hiện cho phép NULL nên chưa đáp ứng hoàn toàn thiết kế.
- Bộ test Module C đã có fixture riêng và yêu cầu biến BDS_TEST_DB; lần chạy trên database thử nghiệm đạt 49/49. Các kiểm tra này chưa bao phủ toàn bộ website.
- Chưa kiểm thử đầy đủ giao diện trên điện thoại, upload, lịch hẹn, duyệt tin, dịch vụ Nominatim và hosting. User-Agent Nominatim cần thông tin liên hệ thật khi triển khai.
- Không có migration tự động cho dữ liệu đang dùng; không chạy lại schema hoặc seed lên database hiện có vì có thể xóa dữ liệu.

## Bổ sung dữ liệu ngày 07/11/2026

- Đã sao lưu CSDL `batdongsan` vào `C:/xampp/mysql/batdongsan_before_fixture_20261107.sql`, sau đó thêm 10 tin mẫu (ID 4–13) và ảnh bìa cho các tin đó. Script: `database/add_sample_properties.sql`.
- Đã tạo database độc lập `batdongsan_test_module_c_20261107` gồm schema, seed, routine và `tests/fixtures_module_c.sql`; tọa độ của các fixture là giả lập.
- Test Module C yêu cầu đặt `BDS_TEST_DB` với tên bắt đầu `batdongsan_test_`, ngăn chạy nhầm trên CSDL chính. Ví dụ PowerShell: `$env:BDS_TEST_DB="batdongsan_test_module_c_20261107"; C:/xampp/php/php.exe tests/test_module_c.php`.
- Chưa thêm tọa độ cho tin mẫu trên database chính vì chưa có vị trí thực được xác minh. Các tin này vẫn dùng vị trí dự phòng theo quận khi hiển thị.
