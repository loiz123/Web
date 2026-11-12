<?php
// [D] Quốc: truy vấn cho trang quản lý người dùng của admin.
class AdminUser extends Model
{
    protected string $table = 'users';

    public const ROLES = ['admin', 'agent', 'customer'];
    public const STATUSES = ['active', 'locked'];

    public function paginate(string $role, string $status, string $keyword, int $page, int $perPage): array
    {
        $params = [in_array($role, self::ROLES, true) ? $role : null, in_array($status, self::STATUSES, true) ? $status : null, $keyword !== '' ? '%' . addcslashes($keyword, '\\%_') . '%' : null];
        $total = (int)($this->call('sp_admin_users_count', $params)[0]['total'] ?? 0);

        $perPage = max(1, $perPage);
        $offset = (max(1, $page) - 1) * $perPage;
        $items = $this->call('sp_admin_users_list', [...$params, $perPage, $offset]);

        return ['items' => $items, 'total' => $total];
    }

    public function setLocked(int $id, bool $lock): array
    {
        $user = $this->call('sp_admin_user_find', [$id])[0] ?? null;

        if (!$user) return ['ok' => false, 'message' => 'Tài khoản không tồn tại.'];

        if ($lock) {
            if ($user['role_name'] === 'admin') {
                return ['ok' => false, 'message' => 'Không thể khóa tài khoản admin.'];
            }
            if ($user['status'] === 'locked') {
                return ['ok' => false, 'message' => 'Tài khoản này đã bị khóa trước đó.'];
            }
            $this->call('sp_admin_user_status', [$id, 'locked']);
            return ['ok' => true, 'message' => 'Đã khóa tài khoản.', 'status' => 'locked'];
        }

        if ($user['status'] === 'active') {
            return ['ok' => false, 'message' => 'Tài khoản này đang hoạt động.'];
        }
        $this->call('sp_admin_user_status', [$id, 'active']);
        return ['ok' => true, 'message' => 'Đã mở khóa tài khoản.', 'status' => 'active'];
    }
}
