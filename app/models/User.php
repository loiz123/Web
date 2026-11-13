<?php
// [A] Người dùng
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['role_id', 'full_name', 'email', 'phone', 'password_hash', 'avatar', 'bio', 'status'];

    public function findByEmail(string $email): ?array
    {
        $row = $this->call('sp_user_by_email', [$email])[0] ?? null;
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        return $this->call('sp_user_by_id', [$id])[0] ?? null;
    }

    public function sessionUser(int $id): ?array
    {
        return $this->call('sp_user_session', [$id])[0] ?? null;
    }

    public function register(string $name, string $email, string $phone, string $password, string $roleName): int
    {
        $rows = $this->call('sp_user_register', [$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT), $roleName]);
        return (int)($rows[0]['id'] ?? 0);
    }

    public function updateProfile(int $id, string $name, string $phone, ?string $bio = null, ?string $avatar = null): void
    {
        $this->call('sp_user_profile_full', [$id, $name, $phone !== '' ? $phone : null,
            $bio !== null ? 1 : 0, $bio !== '' ? $bio : null,
            $avatar !== null ? 1 : 0, $avatar !== '' ? $avatar : null]);
    }

    public function setPassword(int $id, string $password): string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->call('sp_user_set_password', [$id, $hash]);
        return $hash;
    }

    // ---------- Đặt lại mật khẩu (password_resets) ----------

    public function createPasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 phút

        $this->routine('sp_rt_user_create_password_reset_token_1', [$email]);
        $this->routine('sp_rt_user_create_password_reset_token_2', [$email, $token, $expiresAt, $now]);
        return $token;
    }

    public function findValidPasswordReset(string $token): ?array
    {
        if ($token === '') return null;
        $row = $this->routine('sp_rt_user_find_valid_password_reset_1', [$token, date('Y-m-d H:i:s')])->fetch();
        return $row ?: null;
    }

    public function deletePasswordResetsForEmail(string $email): void
    {
        $this->routine('sp_rt_user_delete_password_resets_for_email_1', [$email]);
    }

    public function deletePasswordResetToken(string $token): void
    {
        $this->routine('sp_rt_user_delete_password_reset_token_1', [$token]);
    }

    // ---------- Kiểm tra dữ liệu dùng chung cho đăng ký và hồ sơ ----------

    /** Chuẩn hóa số điện thoại: bỏ khoảng trắng, dấu chấm, gạch. */
    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s.\-]/', '', trim($phone));
    }

    /** @return string[] danh sách lỗi (rỗng nếu hợp lệ). Điện thoại không bắt buộc. */
    public static function validateProfile(string $name, string $phone): array
    {
        $errors = [];
        $len = strlen($name);
        if ($len < 2 || $len > 100) $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
        if ($phone !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
            $errors[] = 'Số điện thoại không hợp lệ (VD: 0901234567 hoặc +84901234567).';
        }
        return $errors;
    }

    /** Mật khẩu 6–72 ký tự (72 là giới hạn của bcrypt) và phải khớp ô nhập lại. */
    public static function validatePassword(string $password, string $confirm): array
    {
        $errors = [];
        if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
        elseif (strlen($password) > 72) $errors[] = 'Mật khẩu tối đa 72 ký tự.';
        if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';
        return $errors;
    }
}
