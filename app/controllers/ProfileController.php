<?php
// [A] Hồ sơ cá nhân: xem, đổi họ tên/số điện thoại, đổi mật khẩu
//   GET  /profile            -> form
//   POST /profile/update     -> đổi họ tên, số điện thoại
//   POST /profile/password   -> đổi mật khẩu
class ProfileController extends Controller
{
    public function __construct() { $this->requireLogin(); }

    public function index(): void
    {
        $this->view('profile/index', [
            'title'   => 'Hồ sơ cá nhân',
            'profile' => (new User())->find((int)Auth::id()),
            'role'    => Auth::user()['role'],
        ]);
    }

    public function update(): void
    {
        $this->postOnly();
        $name  = trim(preg_replace('/\s+/u', ' ', (string)($_POST['full_name'] ?? '')));
        $phone = User::normalizePhone($_POST['phone'] ?? '');

        $errors = User::validateProfile($name, $phone);
        if ($errors) {
            foreach ($errors as $err) flash('danger', $err);
        } else {
            $users = new User();
            $users->updateProfile((int)Auth::id(), $name, $phone);
            $_SESSION['user']['full_name'] = $name;
            flash('success', 'Đã cập nhật hồ sơ.');
        }
        $this->redirect('profile');
    }

    public function password(): void
    {
        $this->postOnly();
        $users   = new User();
        $row     = $users->find((int)Auth::id());
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');

        if (!$row || !password_verify($current, $row['password_hash'])) {
            flash('danger', 'Mật khẩu hiện tại không đúng.');
        } else {
            $errors = User::validatePassword($new, $confirm);
            if ($new === $current) $errors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
            if ($errors) {
                foreach ($errors as $err) flash('danger', $err);
            } else {
                $users->setPassword((int)Auth::id(), $new);
                session_regenerate_id(true);
                flash('success', 'Đã đổi mật khẩu.');
            }
        }
        $this->redirect('profile');
    }

    private function postOnly(): void
    {
        if (!$this->isPost()) $this->redirect('profile');
        $this->verifyCsrf();
    }
}
