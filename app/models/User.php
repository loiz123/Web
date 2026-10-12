<?php
// [A] Người dùng
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['role_id', 'full_name', 'email', 'phone', 'password_hash', 'avatar', 'bio', 'status'];

    public function findByEmail(string $email): ?array
    {
        $row = $this->query(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?',
            [$email]
        )->fetch();
        return $row ?: null;
    }

    public function register(string $name, string $email, string $phone, string $password, string $roleName): int
    {
        $roleId = (int)$this->query('SELECT id FROM roles WHERE name = ?', [$roleName])->fetchColumn();
        return $this->create([
            'role_id' => $roleId, 'full_name' => $name, 'email' => $email, 'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }
}
