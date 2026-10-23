<?php
// [B] Khu vực môi giới: quản lý tin đăng
class AgentController extends Controller
{
    public function __construct() { $this->requireRole('agent', 'admin'); }

    public function index(): void
    {
        // TODO [B]: dashboard "tin của tôi" (danh sách + trạng thái + sửa/ẩn/xóa)
        $this->view('stub', ['title' => 'Tin của tôi', 'owner' => 'B', 'todo' => 'Dashboard môi giới: danh sách tin, đổi trạng thái (AJAX), sửa/xóa.']);
    }

    public function create(): void
    {
        if (!$this->isPost()) {
            $this->showCreateForm();
            return;
        }

        $this->verifyCsrf();
        $input = $_POST;
        foreach ($input as $key => $value) {
            if ($key !== 'amenities' && !is_string($value)) $input[$key] = '';
        }
        $errors = [];

        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $listingType = $input['listing_type'] ?? '';
        $typeId = filter_var($input['type_id'] ?? '', FILTER_VALIDATE_INT);
        $provinceId = filter_var($input['province_id'] ?? '', FILTER_VALIDATE_INT);
        $districtId = filter_var($input['district_id'] ?? '', FILTER_VALIDATE_INT);
        $wardId = filter_var($input['ward_id'] ?? '', FILTER_VALIDATE_INT);
        $price = $input['price'] ?? '';
        $area = $input['area'] ?? '';
        $bedrooms = filter_var($input['bedrooms'] ?? 0, FILTER_VALIDATE_INT);
        $bathrooms = filter_var($input['bathrooms'] ?? 0, FILTER_VALIDATE_INT);

        $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        if ($title === '' || $titleLength > 200) $errors[] = 'Tiêu đề không được để trống và tối đa 200 ký tự.';
        if (!in_array($listingType, ['sale', 'rent'], true)) $errors[] = 'Vui lòng chọn loại tin bán hoặc cho thuê.';
        if (!$typeId || !(new Property())->typeExists((int)$typeId)) $errors[] = 'Vui lòng chọn loại bất động sản hợp lệ.';
        if (!is_numeric($price) || (float)$price <= 0) $errors[] = 'Giá phải là số lớn hơn 0.';
        if (!is_numeric($area) || (float)$area <= 0) $errors[] = 'Diện tích phải là số lớn hơn 0.';
        if ($bedrooms === false || $bedrooms < 0 || $bedrooms > 255
            || $bathrooms === false || $bathrooms < 0 || $bathrooms > 255) {
            $errors[] = 'Số phòng ngủ và phòng tắm không hợp lệ.';
        }
        if (strlen($input['direction'] ?? '') > 120 || strlen($input['legal_status'] ?? '') > 400
            || strlen($input['address_detail'] ?? '') > 800) {
            $errors[] = 'Thông tin hướng, pháp lý hoặc địa chỉ vượt quá độ dài cho phép.';
        }
        if (!$provinceId || !$districtId || !$wardId
            || !(new Location())->isValidWard((int)$provinceId, (int)$districtId, (int)$wardId)) {
            $errors[] = 'Vui lòng chọn đầy đủ tỉnh/thành, quận/huyện và phường/xã.';
        }

        $deposit = null;
        $furniture = null;
        $petsAllowed = 0;
        if ($listingType === 'rent') {
            if (($input['deposit'] ?? '') !== '') {
                if (!is_numeric($input['deposit']) || (float)$input['deposit'] < 0) {
                    $errors[] = 'Tiền đặt cọc không hợp lệ.';
                } else {
                    $deposit = (float)$input['deposit'];
                }
            }
            $furniture = ($input['furniture'] ?? '') !== '' ? $input['furniture'] : null;
            if ($furniture !== null && !in_array($furniture, ['none', 'basic', 'full'], true)) {
                $errors[] = 'Tình trạng nội thất không hợp lệ.';
            }
            $petsAllowed = ($input['pets_allowed'] ?? '0') === '1' ? 1 : 0;
            if (!in_array($input['pets_allowed'] ?? '0', ['0', '1'], true)) $errors[] = 'Lựa chọn thú cưng không hợp lệ.';
        }

        $amenityIds = [];
        $submittedAmenities = $input['amenities'] ?? [];
        if (!is_array($submittedAmenities)) {
            $errors[] = 'Danh sách tiện ích không hợp lệ.';
            $submittedAmenities = [];
        }
        foreach ($submittedAmenities as $amenityId) {
            $id = filter_var($amenityId, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                $errors[] = 'Tiện ích đã chọn không hợp lệ.';
                break;
            }
            $amenityIds[] = (int)$id;
        }
        $amenityIds = array_values(array_unique($amenityIds));
        if (!$errors && !(new Amenity())->hasIds($amenityIds)) $errors[] = 'Một hoặc nhiều tiện ích không hợp lệ.';

        $images = $this->uploadedImages($errors);
        $coverIndex = filter_var($input['cover_image'] ?? 0, FILTER_VALIDATE_INT);
        if ($coverIndex === false || $coverIndex < 0 || $coverIndex >= count($images)) {
            $errors[] = 'Vui lòng chọn ảnh bìa hợp lệ.';
            $coverIndex = 0;
        }

        if ($errors) {
            foreach ($errors as $error) flash('danger', $error);
            $this->showCreateForm($input);
            return;
        }

        $uploadDir = config('upload_dir');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            flash('danger', 'Không thể tạo thư mục lưu ảnh. Vui lòng thử lại.');
            $this->showCreateForm($input);
            return;
        }

        $imagePaths = [];
        $savedFiles = [];
        foreach ($images as $index => $image) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $image['extension'];
            $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            if (!move_uploaded_file($image['tmp_name'], $target)) {
                foreach ($savedFiles as $savedFile) unlink($savedFile);
                flash('danger', 'Không thể lưu ảnh đã tải lên. Vui lòng thử lại.');
                $this->showCreateForm($input);
                return;
            }
            $savedFiles[] = $target;
            $imagePaths[] = 'uploads/properties/' . $fileName;
        }

        $propertyData = [
            'user_id' => Auth::id(),
            'type_id' => (int)$typeId,
            'ward_id' => (int)$wardId,
            'title' => $title,
            'description' => $description,
            'listing_type' => $listingType,
            'price' => (float)$price,
            'deposit' => $deposit,
            'area' => (float)$area,
            'bedrooms' => (int)$bedrooms,
            'bathrooms' => (int)$bathrooms,
            'direction' => trim($input['direction'] ?? '') ?: null,
            'legal_status' => trim($input['legal_status'] ?? '') ?: null,
            'furniture' => $furniture,
            'pets_allowed' => $petsAllowed,
            'address_detail' => trim($input['address_detail'] ?? '') ?: null,
        ];

        try {
            (new Property())->createListing($propertyData, $imagePaths, $amenityIds, $coverIndex);
        } catch (PDOException $error) {
            foreach ($savedFiles as $savedFile) unlink($savedFile);
            error_log($error->getMessage());
            flash('danger', 'Không thể lưu tin đăng. Vui lòng thử lại.');
            $this->showCreateForm($input);
            return;
        }

        flash('success', 'Tin đăng đã được gửi và đang chờ duyệt (pending).');
        $this->redirect('agent/create');
    }

    private function showCreateForm(array $values = []): void
    {
        $this->view('agent/create', [
            'title' => 'Đăng tin mới',
            'values' => $values,
            'propertyTypes' => (new PropertyType())->allSorted(),
            'provinces' => (new Location())->provinces(),
            'amenities' => (new Amenity())->allSorted(),
        ]);
    }

    private function uploadedImages(array &$errors): array
    {
        $images = [];
        $files = $_FILES['images'] ?? null;
        if (!$files || !isset($files['name']) || !is_array($files['name'])) {
            $errors[] = 'Vui lòng tải lên ít nhất một ảnh.';
            return $images;
        }

        $count = count($files['name']);
        if ($count < 1 || $count > 10) {
            $errors[] = 'Tải lên từ 1 đến 10 ảnh.';
            return $images;
        }

        $types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        for ($index = 0; $index < $count; $index++) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || ($files['size'][$index] ?? 0) < 1
                || ($files['size'][$index] ?? 0) > 5 * 1024 * 1024
                || !is_uploaded_file($files['tmp_name'][$index] ?? '')) {
                $errors[] = 'Mỗi ảnh phải nhỏ hơn 5 MB và tải lên thành công.';
                return [];
            }
            $mime = $fileInfo->file($files['tmp_name'][$index]);
            if (!isset($types[$mime]) || !getimagesize($files['tmp_name'][$index])) {
                $errors[] = 'Ảnh chỉ được có định dạng JPG, PNG hoặc WEBP.';
                return [];
            }
            $images[] = [
                'tmp_name' => $files['tmp_name'][$index],
                'extension' => $types[$mime],
            ];
        }
        return $images;
    }
}
