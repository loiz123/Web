<?php
// Quản lý đăng nhập qua session.
class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        self::store($user);
    }

    public static function passwordSignature(string $passwordHash): string
    {
        return substr(hash('sha256', $passwordHash), 0, 16);
    }

    public static function updatePasswordSignature(string $passwordHash): void
    {
        $sig = self::passwordSignature($passwordHash);
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['pwd_sig'] = $sig;
            $_SESSION['user']['password_signature'] = $sig;
        }
        $_SESSION['password_signature'] = $sig;
    }

    /** Ghi lại thông tin hiển thị vào session (dùng khi đăng nhập và sau khi sửa hồ sơ). */
    public static function store(array $user): void
    {
        $pwdSig = !empty($user['password_hash'])
            ? self::passwordSignature((string)$user['password_hash'])
            : ($_SESSION['user']['pwd_sig'] ?? ($_SESSION['password_signature'] ?? null));

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role_name'] ?? ($user['role'] ?? 'user'),
            'role_id' => (int)($user['role_id'] ?? ($_SESSION['user']['role_id'] ?? (($user['role_name'] ?? ($user['role'] ?? '')) === 'admin' ? 1 : 2))),
            'avatar' => array_key_exists('avatar', $user) ? $user['avatar'] : ($_SESSION['user']['avatar'] ?? null),
            'bio' => array_key_exists('bio', $user) ? $user['bio'] : ($_SESSION['user']['bio'] ?? null),
            'pwd_sig' => $pwdSig,
            'password_signature' => $pwdSig,
        ];
        if ($pwdSig !== null) {
            $_SESSION['password_signature'] = $pwdSig;
        }
    }

    /**
     * Gọi ở đầu mỗi request: đối chiếu session với CSDL.
     * Tài khoản bị khóa, bị xóa hoặc đã đổi mật khẩu ở thiết bị khác sẽ bị đăng xuất ngay.
     */
    public static function validate(): void
    {
        if (!self::check()) return;
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.full_name, u.email, u.password_hash, u.avatar, u.bio, u.status, u.role_id, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?'
        );
        $stmt->execute([self::id()]);
        $u = $stmt->fetch();
        if ($u && $u['status'] !== 'locked') {
            $sessionSig = $_SESSION['user']['pwd_sig'] ?? ($_SESSION['password_signature'] ?? null);
            $currentSig = self::passwordSignature((string)($u['password_hash'] ?? ''));
            if ($sessionSig !== null && $sessionSig !== '' && !hash_equals($currentSig, (string)$sessionSig)) {
                unset($_SESSION['user'], $_SESSION['password_signature']);
                session_regenerate_id(true);
                flash('danger', 'Mật khẩu tài khoản đã được thay đổi ở phiên khác. Vui lòng đăng nhập lại.');
                return;
            }
            self::store($u);
            return;
        }

        unset($_SESSION['user'], $_SESSION['password_signature']);
        session_regenerate_id(true);
        flash('danger', $u ? 'Tài khoản của bạn đã bị khóa.' : 'Phiên đăng nhập không còn hợp lệ.');
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function id(): ?int { return $_SESSION['user']['id'] ?? null; }
    public static function isAdmin(): bool
    {
        return self::check() && ((int)($_SESSION['user']['role_id'] ?? 0) === 1 || ($_SESSION['user']['role'] ?? '') === 'admin');
    }
    public static function hasRole(string ...$roles): bool
    {
        if (!self::check()) return false;
        $currentRole = $_SESSION['user']['role'] ?? '';
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        return in_array($currentRole, $roles, true) || ($roleId === 1 && in_array('admin', $roles, true));
    }
}
