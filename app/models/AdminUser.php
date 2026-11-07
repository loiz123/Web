<?php
// [D] Quốc: truy vấn cho trang quản lý người dùng của admin.
class AdminUser extends Model
{
    protected string $table = 'users';

    public const ROLES = ['admin', 'agent', 'customer'];
    public const STATUSES = ['active', 'locked'];

    public function paginate(string $role, string $status, string $keyword, int $page, int $perPage): array
    {
        $where = [];
        $params = [];
        if (in_array($role, self::ROLES, true)) {
            $where[] = 'r.name = ?';
            $params[] = $role;
        }
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
            $like = '%' . addcslashes($keyword, '\\%_') . '%';
            array_push($params, $like, $like);
        }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $from = "FROM users u JOIN roles r ON r.id = u.role_id $sqlWhere";

        $total = (int)$this->query("SELECT COUNT(*) $from", $params)->fetchColumn();

        $perPage = max(1, $perPage);
        $offset = (max(1, $page) - 1) * $perPage;
        $items = $this->query(
            "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at, r.name AS role_name
             $from ORDER BY u.id DESC LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();

        return ['items' => $items, 'total' => $total];
    }

    public function setLocked(int $id, bool $lock): array
    {
        $user = $this->query(
            'SELECT u.id, u.status, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [$id]
        )->fetch();

        if (!$user) return ['ok' => false, 'message' => 'Tài khoản không tồn tại.'];

        if ($lock) {
            if ($user['role_name'] === 'admin') {
                return ['ok' => false, 'message' => 'Không thể khóa tài khoản admin.'];
            }
            if ($user['status'] === 'locked') {
                return ['ok' => false, 'message' => 'Tài khoản này đã bị khóa trước đó.'];
            }
            $this->query(
                "UPDATE users SET status = 'locked'
                 WHERE id = ? AND role_id <> (SELECT id FROM roles WHERE name = 'admin')",
                [$id]
            );
            return ['ok' => true, 'message' => 'Đã khóa tài khoản.', 'status' => 'locked'];
        }

        if ($user['status'] === 'active') {
            return ['ok' => false, 'message' => 'Tài khoản này đang hoạt động.'];
        }
        $this->query("UPDATE users SET status = 'active' WHERE id = ?", [$id]);
        return ['ok' => true, 'message' => 'Đã mở khóa tài khoản.', 'status' => 'active'];
    }
}
