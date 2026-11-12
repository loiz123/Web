<?php
// Quản lý thông báo trong ứng dụng:
//   GET  /notification            -> danh sách thông báo (HTML hoặc JSON)
//   GET|POST /notification/read/{id} -> đánh dấu đã đọc và chuyển tới liên kết đích
//   POST /notification/readAll    -> đánh dấu tất cả là đã đọc
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index(): void
    {
        $userId = (int)Auth::id();
        $model = new Notification();
        $notifications = $model->getAllForUser($userId, 50);
        $unreadCount = $model->countUnreadForUser($userId);

        if ($this->wantsJson()) {
            $this->json([
                'unread_count'  => $unreadCount,
                'notifications' => $notifications,
            ], true);
            return;
        }

        $this->view('notification/index', [
            'title'         => 'Thông báo của tôi',
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
        ]);
    }

    public function read(int|string $id = 0): void
    {
        $id = (int)$id;
        $userId = (int)Auth::id();
        $model = new Notification();
        $notif = $model->findForUser($id, $userId);

        if (!$notif) {
            if ($this->wantsJson()) {
                $this->json(null, false, 'Không tìm thấy thông báo.', 404);
                return;
            }
            flash('warning', 'Không tìm thấy thông báo yêu cầu.');
            $this->redirect('notification');
            return;
        }

        $model->markAsRead($id, $userId);

        if ($this->wantsJson()) {
            $this->json([
                'id'           => $id,
                'link'         => !empty($notif['link']) ? base_url($notif['link']) : null,
                'unread_count' => $model->countUnreadForUser($userId),
            ], true, 'Đã đánh dấu đã đọc.');
            return;
        }

        if (!empty($notif['link'])) {
            $link = (string)$notif['link'];
            if (preg_match('#^https?://#i', $link)) {
                header('Location: ' . $link);
                exit;
            }
            $this->redirect($link);
            return;
        }

        $this->redirect('notification');
    }

    public function readAll(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
        }
        $userId = (int)Auth::id();
        $model = new Notification();
        $updated = $model->markAllAsRead($userId);

        if ($this->wantsJson()) {
            $this->json(['updated' => $updated, 'unread_count' => 0], true, 'Đã đánh dấu tất cả thông báo là đã đọc.');
            return;
        }

        flash('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
        $this->redirect('notification');
    }
}
