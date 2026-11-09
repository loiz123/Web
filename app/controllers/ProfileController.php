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
        $userId = (int)Auth::id();
        $users  = new User();
        $currentProfile = $users->find($userId);

        $name  = trim(preg_replace('/\s+/u', ' ', (string)($_POST['full_name'] ?? '')));
        $phone = User::normalizePhone($_POST['phone'] ?? '');
        $bio   = trim((string)($_POST['bio'] ?? ''));

        $errors = User::validateProfile($name, $phone);
        $bioLen = function_exists('mb_strlen') ? mb_strlen($bio, 'UTF-8') : strlen($bio);
        if ($bioLen > 1000) {
            $errors[] = 'Tiểu sử / Giới thiệu bản thân tối đa 1000 ký tự.';
        }

        $newAvatarRelPath = null;
        if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $avatarFile = $_FILES['avatar'];
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            $tmpName = (string)($avatarFile['tmp_name'] ?? '');
            $fileSize = (int)($avatarFile['size'] ?? 0);

            if (($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || $fileSize < 1
                || $fileSize > 5 * 1024 * 1024
                || (!is_uploaded_file($tmpName) && !is_file($tmpName))) {
                $errors[] = 'Ảnh đại diện phải nhỏ hơn 5 MB và tải lên thành công.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpName);
                if (!isset($allowedMimes[$mime]) || !@getimagesize($tmpName)) {
                    $errors[] = 'Ảnh đại diện chỉ hỗ trợ định dạng JPG, PNG hoặc WEBP.';
                } else {
                    $avatarDir = ROOT . '/public/uploads/avatars';
                    $processed = ImageHelper::processAvatarImage($tmpName, $avatarDir, 400, 85);
                    if (!$processed) {
                        $errors[] = 'Không thể lưu ảnh đại diện. Vui lòng thử lại.';
                    } else {
                        $newAvatarRelPath = $processed['relative_path'];
                    }
                }
            }
        }

        if ($errors) {
            if ($newAvatarRelPath !== null) {
                $fullNewAvatar = ROOT . '/public/' . $newAvatarRelPath;
                if (is_file($fullNewAvatar)) @unlink($fullNewAvatar);
            }
            foreach ($errors as $err) flash('danger', $err);
        } else {
            if ($newAvatarRelPath !== null && !empty($currentProfile['avatar'])) {
                $oldFile = basename((string)$currentProfile['avatar']);
                if (preg_match('/^avatar_[a-f0-9]{32}\.(jpg|png|webp)$/', $oldFile)) {
                    $oldFullPath = ROOT . '/public/uploads/avatars/' . $oldFile;
                    if (is_file($oldFullPath)) @unlink($oldFullPath);
                }
            }

            $users->updateProfile($userId, $name, $phone, $bio, $newAvatarRelPath);
            $_SESSION['user']['full_name'] = $name;
            $_SESSION['user']['bio'] = $bio !== '' ? $bio : null;
            if ($newAvatarRelPath !== null) {
                $_SESSION['user']['avatar'] = $newAvatarRelPath;
            }
            flash('success', 'Đã cập nhật hồ sơ cá nhân thành công.');
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
                $newHash = $users->setPassword((int)Auth::id(), $new);
                session_regenerate_id(true);
                Auth::updatePasswordSignature($newHash);
                flash('success', 'Đã đổi mật khẩu thành công. Các phiên đăng nhập cũ trên thiết bị khác đã được thu hồi.');
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
