<?php
// Lớp cha cho mọi Controller.
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        extract($data);
        ob_start();
        require APP . "/views/$view.php";
        $content = ob_get_clean();
        require APP . "/views/$layout.php";
    }

    protected function redirect(string $path)
    {
        header('Location: ' . base_url($path));
        exit;
    }

    /** Trả JSON chuẩn cho AJAX: {success, message, data} */
    protected function json($data = null, bool $success = true, string $message = '', int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireLogin(): void
    {
        if (!Auth::check()) { flash('warning', 'Vui lòng đăng nhập.'); $this->redirect('auth/login'); }
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireLogin();
        if (!Auth::hasRole(...$roles)) { http_response_code(403); $this->view('errors/403'); exit; }
    }

    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = $_SESSION['csrf'] ?? '';
        if ($expected === '' || !hash_equals($expected, (string)$token)) {
            http_response_code(419);
            exit('CSRF token không hợp lệ.');
        }
    }
}
