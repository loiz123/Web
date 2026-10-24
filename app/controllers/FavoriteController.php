<?php
// [C/D] Quản lý tin đăng yêu thích
class FavoriteController extends Controller
{
    private Favorite $favorites;

    public function __construct()
    {
        $this->favorites = new Favorite();
    }

    /** GET /favorite - Danh sách tin BĐS đã lưu */
    public function index(): void
    {
        $this->requireLogin();
        $userId = (int)Auth::id();
        $properties = $this->favorites->forUser($userId);

        $this->view('favorite/index', [
            'title'      => 'Tin đăng đã lưu',
            'properties' => $properties,
        ]);
    }

    /** POST /favorite/toggle/{propertyId} - Lưu hoặc bỏ lưu tin (AJAX) */
    public function toggle($propertyId = 0): void
    {
        if (!$this->isPost()) {
            $this->json(null, false, 'Phương thức không được hỗ trợ.', 405);
            return;
        }

        if (!Auth::check()) {
            $this->json(['liked' => false, 'require_login' => true], false, 'Vui lòng đăng nhập để lưu tin yêu thích.', 401);
            return;
        }

        $this->verifyCsrf();

        $propertyId = (int)$propertyId;
        if ($propertyId <= 0) {
            $this->json(null, false, 'Bất động sản không hợp lệ.', 400);
            return;
        }

        $property = (new PropertyFinder())->findDetail($propertyId);
        if (!$property) {
            $this->json(null, false, 'Tin đăng không tồn tại hoặc đã bị gỡ.', 404);
            return;
        }

        $userId = (int)Auth::id();
        $liked = $this->favorites->toggle($userId, $propertyId);

        $this->json(
            [
                'liked'       => $liked,
                'property_id' => $propertyId,
                'total_fav'   => $this->favorites->countForUser($userId),
            ],
            true,
            $liked ? 'Đã lưu vào danh sách yêu thích.' : 'Đã bỏ lưu khỏi danh sách yêu thích.'
        );
    }
}
