<?php
// Quản lý đăng nhập qua session.
class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'], 'full_name' => $user['full_name'],
            'email' => $user['email'], 'role' => $user['role_name'],
        ];
    }
    public static function logout(): void { $_SESSION = []; session_destroy(); }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function id(): ?int { return $_SESSION['user']['id'] ?? null; }
    public static function hasRole(string ...$roles): bool { return self::check() && in_array($_SESSION['user']['role'], $roles, true); }
}
