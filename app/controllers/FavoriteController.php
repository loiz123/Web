<?php
// [D] Yêu thích (AJAX): POST favorite/toggle/{propertyId}
class FavoriteController extends Controller
{
    public function toggle($propertyId = 0): void
    {
        if (!Auth::check()) $this->json(null, false, 'Vui lòng đăng nhập.', 401);
        if (!$this->isPost()) $this->json(null, false, 'Method not allowed', 405);
        $this->verifyCsrf();
        // TODO [D]: thêm/xóa trong bảng favorites, trả về {liked: true|false}
        $this->json(['liked' => null], false, 'Chưa cài đặt.', 501);
    }
}
