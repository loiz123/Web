<?php
// [A] Đăng nhập / đăng ký / đăng xuất
class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) $this->redirect('');
        if ($this->isPost()) {
            $this->verifyCsrf();
            $user = (new User())->findByEmail(trim($_POST['email'] ?? ''));
            if (!$user || !password_verify($_POST['password'] ?? '', $user['password_hash'])) {
                flash('danger', 'Email hoặc mật khẩu không đúng.');
            } elseif ($user['status'] === 'locked') {
                flash('danger', 'Tài khoản của bạn đã bị khóa.');
            } else {
                Auth::login($user);
                $this->redirect($user['role_name'] === 'admin' ? 'admin' : ($user['role_name'] === 'agent' ? 'agent' : ''));
            }
        }
        $this->view('auth/login', ['title' => 'Đăng nhập']);
    }

    public function register(): void
    {
        if (Auth::check()) $this->redirect('');
        if ($this->isPost()) {
            $this->verifyCsrf();
            $name  = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $role  = in_array($_POST['role'] ?? '', ['agent', 'customer'], true) ? $_POST['role'] : 'customer';

            $errors = [];
            if ($name === '') $errors[] = 'Vui lòng nhập họ tên.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
            if (strlen($pass) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
            $users = new User();
            if (!$errors && $users->findByEmail($email)) $errors[] = 'Email đã được đăng ký.';

            if ($errors) {
                foreach ($errors as $err) flash('danger', $err);
                $_SESSION['old'] = ['full_name' => $name, 'email' => $email, 'phone' => $phone];
            } else {
                $users->register($name, $email, $phone, $pass, $role);
                unset($_SESSION['old']);
                flash('success', 'Đăng ký thành công, hãy đăng nhập.');
                $this->redirect('auth/login');
            }
        }
        $this->view('auth/register', ['title' => 'Đăng ký']);
        unset($_SESSION['old']);
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        flash('info', 'Bạn đã đăng xuất.');
        $this->redirect('');
    }
}
