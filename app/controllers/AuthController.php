<?php
// [A] Đăng nhập / đăng ký / đăng xuất
class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) $this->redirect('');
        if ($this->isPost()) {
            $this->verifyCsrf();
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pass  = (string)($_POST['password'] ?? '');
            $user  = $email !== '' ? (new User())->findByEmail($email) : null;

            if ($email === '' || $pass === '') {
                flash('danger', 'Vui lòng nhập email và mật khẩu.');
            } elseif (!$user || !password_verify($pass, $user['password_hash'])) {
                flash('danger', 'Email hoặc mật khẩu không đúng.');   // không tiết lộ email có tồn tại hay không
            } elseif ($user['status'] === 'locked') {
                flash('danger', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
            } else {
                unset($_SESSION['old']);
                Auth::login($user);
                $this->redirect(['admin' => 'admin', 'agent' => 'agent'][$user['role_name']] ?? '');
            }
            $_SESSION['old'] = ['email' => $email];
        }
        $this->view('auth/login', ['title' => 'Đăng nhập']);
        unset($_SESSION['old']);
    }

    public function register(): void
    {
        if (Auth::check()) $this->redirect('');
        if ($this->isPost()) {
            $this->verifyCsrf();
            $name    = trim(preg_replace('/\s+/u', ' ', (string)($_POST['full_name'] ?? '')));
            $email   = strtolower(trim($_POST['email'] ?? ''));
            $phone   = User::normalizePhone($_POST['phone'] ?? '');
            $pass    = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');
            $role    = in_array($_POST['role'] ?? '', ['agent', 'customer'], true) ? $_POST['role'] : 'customer';

            $errors = User::validateProfile($name, $phone);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) $errors[] = 'Email không hợp lệ.';
            $errors = array_merge($errors, User::validatePassword($pass, $confirm));
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
