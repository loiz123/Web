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
        $this->requireLogin();
        if (!$this->isPost()) {
            http_response_code(405);
            header('Allow: POST');
            $this->view('errors/405', ['title' => 'Phương thức không được hỗ trợ']);
            return;
        }
        $this->verifyCsrf();
        Auth::logout();
        session_start();
        flash('info', 'Bạn đã đăng xuất.');
        $this->redirect('');
    }

    public function forgot(): void
    {
        if (Auth::check()) $this->redirect('');

        if ($this->isPost()) {
            $this->verifyCsrf();
            $email = strtolower(trim((string)($_POST['email'] ?? '')));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('danger', 'Vui lòng nhập địa chỉ email hợp lệ.');
                $_SESSION['old'] = ['email' => $email];
            } else {
                $users = new User();
                $user = $users->findByEmail($email);
                if (!$user) {
                    unset($_SESSION['old']);
                    flash('success', 'Một liên kết đặt lại mật khẩu đã được gửi tới email của bạn (nếu tài khoản tồn tại trong hệ thống). Vui lòng kiểm tra hộp thư đến hoặc hòm thư rác.');
                } elseif ($user['status'] === 'locked') {
                    flash('danger', 'Tài khoản này hiện đang bị khóa. Vui lòng liên hệ bộ phận hỗ trợ.');
                } else {
                    $token = $users->createPasswordResetToken($email);
                    $fullResetUrl = full_url('auth/reset?token=' . urlencode($token));

                    unset($_SESSION['old']);
                    $mailer = new Mailer();
                    $sent = $mailer->sendPasswordResetEmail(
                        $email,
                        (string)($user['full_name'] ?? 'Quý khách'),
                        $fullResetUrl,
                        30
                    );

                    // Chuẩn OWASP chống User Enumeration & phục vụ demo: Luôn hiển thị thông báo thành công
                    // Nếu môi trường chưa cấu hình SMTP hoặc lỗi gửi, liên kết vẫn được ghi vào storage/logs/mail.log
                    flash('success', 'Một liên kết đặt lại mật khẩu đã được gửi tới email của bạn (nếu tài khoản tồn tại trong hệ thống). Vui lòng kiểm tra hộp thư đến hoặc hòm thư rác.');
                }
            }
        }

        $this->view('auth/forgot', [
            'title' => 'Quên mật khẩu',
        ]);
        unset($_SESSION['old']);
    }

    public function reset(string $tokenParam = ''): void
    {
        if (Auth::check()) $this->redirect('');
        $token = trim((string)($_GET['token'] ?? ($_POST['token'] ?? $tokenParam)));
        $users = new User();
        $resetRow = $users->findValidPasswordReset($token);

        if (!$resetRow) {
            flash('danger', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn (30 phút). Vui lòng gửi lại yêu cầu.');
            $this->redirect('auth/forgot');
            return;
        }

        if ($this->isPost()) {
            $this->verifyCsrf();
            $pass    = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');
            $errors  = User::validatePassword($pass, $confirm);

            if ($errors) {
                foreach ($errors as $err) flash('danger', $err);
            } else {
                $user = $users->findByEmail($resetRow['email']);
                if (!$user) {
                    flash('danger', 'Tài khoản không tồn tại.');
                    $this->redirect('auth/forgot');
                    return;
                }
                $users->setPassword((int)$user['id'], $pass);
                $users->deletePasswordResetsForEmail($resetRow['email']);
                flash('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
                $this->redirect('auth/login');
                return;
            }
        }

        $this->view('auth/reset', [
            'title' => 'Đặt lại mật khẩu mới',
            'token' => $token,
            'email' => $resetRow['email'],
        ]);
    }
}
