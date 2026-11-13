# Chuyển toàn bộ truy cập dữ liệu ứng dụng sang stored procedure

Ngày thực hiện: 13/11/2026. Phạm vi: toàn bộ đọc/ghi dữ liệu khi website chạy trong `app/`, kể cả controller, kiểm tra phiên, model cơ sở và thông báo.

## 1. File import cho phpMyAdmin online

- [procedures-online.sql](../deploy/procedures-online.sql): file tổng hợp hoàn chỉnh, **237 tên procedure khác nhau**. Có một số định nghĩa được cập nhật ở file runtime cuối cùng để giữ tương thích chữ ký và hành vi.
- [check-procedures.sql](../deploy/check-procedures.sql): tìm procedure còn thiếu sau khi import. Thành công khi truy vấn không trả dòng nào.
- [zz_runtime.sql](../database/procedures/zz_runtime.sql): nguồn các procedure bổ sung, được nạp sau các module cũ.

### Database online đã có dữ liệu

1. Mở phpMyAdmin và chọn đúng database online ở cột trái.
2. Chọn **Import** và chọn `deploy/procedures-online.sql`, định dạng SQL, bảng mã UTF-8.
3. Thực thi import, xác nhận phpMyAdmin không báo lỗi. Cần quyền tạo/thay thế và thực thi routine; các routine INVOKER cũng sử dụng quyền dữ liệu của tài khoản kết nối.
4. Import hoặc mở nội dung `deploy/check-procedures.sql` trong tab **SQL** và chạy ở cùng database. Không có dòng kết quả nghĩa là đã có tất cả procedure bắt buộc.
5. Sau khi procedure đã cài thành công mới cập nhật code PHP. Nên thực hiện trong thời gian bảo trì vì DROP/CREATE routine không phải thay đổi có thể rollback nguyên gói.
6. Kiểm tra đăng nhập, tìm kiếm/bản đồ, tạo/sửa tin có ảnh, duyệt tin, lịch hẹn và báo cáo trên môi trường đích.

File tổng hợp không có USE tên database cố định, không có DEFINER cố định, không tạo/xóa bảng, không thay đổi schema và không nạp dữ liệu mẫu. Không import lại `schema.sql`, `seed.sql` hoặc seed địa giới vào database đang dùng. Nếu database cũ thiếu slug, xem riêng [migration slug](../database/migrations/20261110_add_property_slug.sql).

Các file chỉ chứa routine và truy vấn kiểm tra metadata; không chứa thông tin kết nối hoặc API key. Không tự động kết nối/import lên database online trong lần thực hiện này.

### Database mới

Tạo schema, nạp seed theo README rồi import file tổng hợp. Database local mặc định tên batdongsan; khi chạy schema trên hosting có tên database được cấp sẵn cần dùng quy trình schema phù hợp của hosting. File procedure tổng hợp dùng database hiện đang chọn.

### Lệnh cài local và tạo lại gói SQL

```powershell
C:\xampp\php\php.exe scripts/import_all_procedures_local.php
python scripts/build_procedure_bundle.py
```

Importer đọc cấu hình DB và biến môi trường, nạp `database/procedures.sql` cùng toàn bộ file con theo thứ tự, sau đó kiểm tra đủ các tên procedure. Có thể dùng `BDS_TEST_DB` để chọn database thử riêng. Builder tạo lại cả file import và file kiểm tra từ nguồn SQL, không kết nối database.

## 2. Thay đổi trong mã nguồn

| Phần | Cách xử lý mới |
| --- | --- |
| Database | Chỉ thực thi CALL với tên procedure được kiểm tra và giá trị bind qua PDO; đọc hết result set và đóng cursor trước thao tác tiếp theo |
| Model cơ sở | Không còn query()/fallbackCall(). CRUD gọi procedure riêng theo từng bảng với tên nằm trong danh sách cho phép; dữ liệu JSON được lọc qua fillable |
| RoutineResult | Buffer kết quả cho các caller cần fetch, fetchColumn, fetchAll và rowCount; affected rows/insert ID lấy từ kết quả do procedure trả về |
| Tài khoản và session | Đọc thông tin qua sp_user_session; procedure trả đủ password_hash, role_id, avatar và bio để giữ kiểm tra phiên và hồ sơ |
| Địa giới và danh mục | Toàn bộ tra cứu, kiểm tra tên trùng, thêm/sửa/xóa và danh sách quản trị gọi procedure |
| Tìm kiếm và bản đồ | Dùng các procedure search/count/detail; bản đồ có sp_property_map nhận bộ lọc có tham số, không truyền SQL/WHERE từ PHP |
| Tin và ảnh | Sửa tin, gia hạn, đọc quyền sở hữu, đồng bộ tiện ích, xóa/chọn ảnh bìa đều qua procedure; giữ transaction và kiểm tra user_id |
| Tin tương tự | sp_property_similar_full ưu tiên cùng vị trí/loại và bổ sung các tin khác theo khoảng giá |
| Thông báo | Tạo, đọc, đếm, đánh dấu đọc và xóa đều qua procedure |
| Báo cáo | Procedure mới giữ description/admin_notes, lọc/tìm kiếm/phân trang, hỗ trợ trạng thái open/pending khi đọc và ghi open khi tạo để tương thích hosting |
| Kiểm duyệt và dashboard | Procedure xử lý các tab, thống kê, xác thực/đổi trạng thái và khởi tạo thời hạn khi duyệt |

Không còn lựa chọn `use_routines=false` để chạy SQL trực tiếp. Thiếu procedure bắt buộc sẽ báo lỗi cài đặt thay vì tự trả dữ liệu giả hoặc chạy SQL thay thế. Các lệnh SQL trong script cài schema, nhập seed, đồng bộ/migration và fixture/inspection của test vẫn phục vụ quản trị/kiểm thử; chúng không phải đường đọc/ghi runtime của website.

Tham khảo [bản đồ các thao tác SQL đã chuyển](procedure-runtime-map.json). Không có procedure tổng quát nhận chuỗi SQL tùy ý từ PHP; các procedure nghiệp vụ nhận giá trị dữ liệu và dùng câu SQL định nghĩa trên server. Một số procedure danh mục cũ có SQL động nội bộ theo danh sách bảng cho phép.

## 3. Kiểm chứng

Đã dùng MariaDB 10.4.32 riêng tại 127.0.0.1:3313 và database `batdongsan_test_procedures_20261113`, không nhập đè database gốc hoặc online. Schema/seed và fixture chỉ được nhập vào database thử. Bộ Module C dùng thêm seed địa giới 63 tỉnh và 3.308 phường/xã của dự án; PHP test bật GD, HTTP test chạy tại 127.0.0.1:8103.

| Kiểm tra | Kết quả |
| --- | --- |
| Import file SQL tổng hợp vào database thử | Thành công; 237 tên procedure được cài |
| test_procedure_only.php | 188 kiểm tra đạt: chặn SQL trực tiếp trong app, đủ procedure, session/profile/reset token, danh mục, thông báo, CRUD sparse/null/decimal, bản đồ, dashboard, kiểm duyệt, thiếu routine và đóng cursor |
| test_user_validation.php | 12/12 đạt |
| test_module_c.php | 80/80 đạt trên fixture địa giới đầy đủ |
| test_ui_and_new_features.php | 50/50 đạt, gồm HTTP test |
| test_compare_render.php | 7/7 đạt |
| test_property_image_ownership.php | 8 kiểm tra đạt |
| test_admin_mutation_guards.php | 12 kiểm tra HTTP/DB đạt |
| test_agent_image_storage.php | 9 kiểm tra đạt; lỗi upload mô phỏng được xử lý như mong đợi |
| test_agent_search_procedures.php | Luồng đăng/sửa/quyền sở hữu/rollback/trạng thái/tìm kiếm đạt trên seed cơ bản |
| test_report_workflow.php | 22 kiểm tra đạt trên enum hosting không có pending, gồm có/không description |
| Importer PHP local | Thành công khi chọn database thử |

Test kiểm tra countSearch trước đây phụ thuộc chuỗi code `isset(...)` đã được đổi thành kiểm tra hành vi: bộ lọc không có kết quả phải có tổng 0 và danh sách rỗng. Test báo cáo không còn so sánh nhánh SQL/procedure; thay bằng hai trường hợp procedure có/không mô tả.

Kết quả trên không thay thế kiểm tra tại hosting sau khi người dùng import. Chưa triển khai code hoặc SQL lên online, chưa chạy lại mọi test dịch vụ email bên ngoài.

## 4. Cập nhật báo cáo đồ án

Các tài liệu đối chiếu/Word trước ngày chuyển đổi là mô tả phiên bản cũ. Với phiên bản này, sửa các câu nói còn SQL trực tiếp/fallback, cập nhật số procedure và thêm RoutineResult/Database::call vào mô tả kiến trúc. Không còn dùng số 81 procedure để mô tả toàn bộ bộ cài hiện tại: 81 là số định nghĩa trong 15 file module cũ, bộ tổng hợp hiện có 237 tên procedure khác nhau.
