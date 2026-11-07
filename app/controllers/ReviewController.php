<?php
// URL: /review   [C - Thiện: Đánh giá & Bình luận tin đăng]
class ReviewController extends Controller
{
    /** Khách gửi hoặc cập nhật đánh giá cho bất động sản. */
    public function submit(): void
    {
        $isJson = $this->wantsJson();

        if (!Auth::check()) {
            if ($isJson) {
                $this->json(['need_login' => true], false, 'Vui lòng đăng nhập để đánh giá.', 401);
            }
            flash('warning', 'Vui lòng đăng nhập để đánh giá tin đăng.');
            $this->redirect('auth/login');
        }

        if (!$this->isPost()) {
            $this->redirect('');
        }

        $this->verifyCsrf();

        $propertyId = (int)($_POST['property_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($propertyId <= 0) {
            $this->respond('Bất động sản không hợp lệ.', false, 400);
            return;
        }

        if ($rating < 1 || $rating > 5) {
            $this->respond('Vui lòng chọn mức đánh giá từ 1 đến 5 sao.', false, 400);
            return;
        }

        // Không cho phép chủ tin tự đánh giá tin của chính mình
        $property = (new Property())->find($propertyId);
        if (!$property) {
            $this->respond('Không tìm thấy bất động sản.', false, 404);
            return;
        }

        if ((int)$property['user_id'] === (int)Auth::id()) {
            $this->respond('Bạn không thể tự đánh giá tin đăng của chính mình.', false, 403);
            return;
        }

        $reviewModel = new Review();
        $ok = $reviewModel->saveReview($propertyId, Auth::id(), $rating, $comment ?: null);

        if ($ok) {
            $stats = $reviewModel->getStats($propertyId);
            $this->respond('Cảm ơn bạn đã gửi đánh giá!', true, 200, [
                'stats' => $stats,
            ]);
        } else {
            $this->respond('Không thể lưu đánh giá. Vui lòng thử lại sau.', false, 500);
        }
    }

    /** Xóa đánh giá của chính mình. */
    public function delete(int|string $id): void
    {
        $this->requireLogin();
        if (!$this->isPost()) {
            $this->redirect('');
        }
        $this->verifyCsrf();

        $reviewId = (int)$id;
        $propertyId = (int)($_POST['property_id'] ?? 0);

        $ok = (new Review())->deleteByUser($reviewId, Auth::id());

        if ($this->wantsJson()) {
            if ($ok) {
                $stats = $propertyId > 0 ? (new Review())->getStats($propertyId) : null;
                $this->json(['stats' => $stats], true, 'Đã xóa đánh giá của bạn.');
            } else {
                $this->json(null, false, 'Không thể xóa đánh giá này.', 400);
            }
        }

        if ($ok) {
            flash('success', 'Đã xóa đánh giá của bạn.');
        } else {
            flash('danger', 'Không thể xóa đánh giá này.');
        }

        $this->redirect($propertyId > 0 ? 'property/detail/' . $propertyId : '');
    }

    private function respond(string $message, bool $success, int $status = 200, array $data = []): void
    {
        if ($this->wantsJson()) {
            $this->json($data ?: null, $success, $message, $status);
        }
        flash($success ? 'success' : 'danger', $message);
        $redirectUrl = !empty($_POST['property_id']) ? 'property/detail/' . (int)$_POST['property_id'] : '';
        $this->redirect($redirectUrl);
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json')
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
}
