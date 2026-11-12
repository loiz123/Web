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
    protected function json($data = null, $success = true, string $message = '', int $status = 200)
    {
        if (is_int($success) && is_array($data) && array_key_exists('success', $data)) {
            $status = $success;
            $message = (string)($data['message'] ?? '');
            $success = (bool)$data['success'];
            $data = $data['data'] ?? null;
        } else {
            $success = (bool)$success;
        }
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

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json')
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_GET['ajax']);
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = $_SESSION['csrf'] ?? '';
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            // Apache chuyển mã không chuẩn 419 thành 500; dùng mã từ chối chuẩn.
            $message = 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang và thử lại.';
            if ($this->wantsJson()) {
                $this->json(null, false, $message, 403);
            }
            http_response_code(403);
            exit($message);
        }
    }
}
