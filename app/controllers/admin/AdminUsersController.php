<?php
// URL: /admin/users              -> trang danh sách người dùng
//      /admin/users/data         -> GET  JSON danh sách, lọc: q, role, status, page
//      /admin/users/toggle/5     -> POST khóa | mở khóa (AJAX JSON), action = lock | unlock
class AdminUsersController extends Controller
{
    private const PER_PAGE = 10;

    private AdminUser $users;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->users = new AdminUser();
    }

    public function index(): void
    {
        $this->view('admin/users', ['title' => 'Quản lý người dùng']);
    }

    public function data(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->users->paginate(
            (string)($_GET['role'] ?? ''),
            (string)($_GET['status'] ?? ''),
            trim((string)($_GET['q'] ?? '')),
            $page,
            self::PER_PAGE
        );

        $items = array_map(function (array $u): array {
            return [
                'id'         => (int)$u['id'],
                'full_name'  => $u['full_name'],
                'email'      => $u['email'],
                'phone'      => $u['phone'],
                'role'       => $u['role_name'],
                'status'     => $u['status'],
                'created_at' => $u['created_at'],
                'can_toggle' => $u['role_name'] !== 'admin',
            ];
        }, $result['items']);

        $this->json([
            'items'    => $items,
            'total'    => $result['total'],
            'page'     => $page,
            'per_page' => self::PER_PAGE,
        ]);
    }

    public function toggle($id = 0): void
    {
        if (!$this->isPost()) {
            $this->json(null, false, 'Phương thức không hợp lệ.', 405);
            return;
        }
        $this->verifyCsrf();

        $action = (string)($_POST['action'] ?? '');
        if (!in_array($action, ['lock', 'unlock'], true)) {
            $this->json(null, false, 'Hành động không hợp lệ.', 422);
            return;
        }

        $res = $this->users->setLocked((int)$id, $action === 'lock');
        $this->json(
            $res['ok'] ? ['status' => $res['status']] : null,
            $res['ok'],
            $res['message'],
            $res['ok'] ? 200 : 422
        );
    }
}
