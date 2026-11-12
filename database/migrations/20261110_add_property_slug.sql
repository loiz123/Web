-- Chạy trên database hiện có đã được chọn; không xóa hoặc cập nhật dữ liệu.
-- Có thể chạy lại trên cả MySQL và MariaDB.
SET @property_slug_ddl = IF(
    EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'properties' AND column_name = 'slug'
    ),
    'SELECT 1',
    'ALTER TABLE properties ADD COLUMN slug VARCHAR(255) NULL AFTER title'
);
PREPARE property_slug_stmt FROM @property_slug_ddl;
EXECUTE property_slug_stmt;
DEALLOCATE PREPARE property_slug_stmt;
