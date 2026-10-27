<?php
// Quản lý đăng nhập qua session.
class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        self::store($user);
    }

    /** Ghi lại thông tin hiển thị vào session (dùng khi đăng nhập và sau khi sửa hồ sơ). */
    public static function store(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int)$user['id'], 'full_name' => $user['full_name'],
            'email' => $user['email'], 'role' => $user['role_name'],
        ];
    }

    /**
     * Gọi ở đầu mỗi request: đối chiếu session với CSDL.
     * Tài khoản bị khóa hoặc đã bị xóa sẽ bị đăng xuất ngay, đổi vai trò/tên có hiệu lực ngay.
     */
    public static function validate(): void
    {
        if (!self::check()) return;
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.full_name, u.email, u.status, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?'
        );
        $stmt->execute([self::id()]);
        $u = $stmt->fetch();
        if ($u && $u['status'] !== 'locked') { self::store($u); return; }

        unset($_SESSION['user']);
        session_regenerate_id(true);
        flash('danger', $u ? 'Tài khoản của bạn đã bị khóa.' : 'Phiên đăng nhập không còn hợp lệ.');
    }

    public static function logout(): void { $_SESSION = []; session_destroy(); }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function id(): ?int { return $_SESSION['user']['id'] ?? null; }
    public static function hasRole(string ...$roles): bool { return self::check() && in_array($_SESSION['user']['role'], $roles, true); }
}
