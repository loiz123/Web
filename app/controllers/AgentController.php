<?php
// [B] Khu vực môi giới: quản lý tin đăng
class AgentController extends Controller
{
    public function __construct() { $this->requireRole('agent', 'admin'); }

    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $statuses = ['pending', 'approved', 'rejected', 'hidden', 'closed'];
        if ($status !== '' && !in_array($status, $statuses, true)) {
            flash('warning', 'Trạng thái lọc không hợp lệ.');
            $this->redirect('agent');
        }
        $this->view('agent/index', [
            'title' => 'Tin của tôi',
            'listings' => (new Property())->listingsForUser((int)Auth::id(), $status === '' ? null : $status),
            'selectedStatus' => $status,
        ]);
    }

    public function edit(int $id): void
    {
        $property = new Property();
        $listing = $property->findOwned($id, (int)Auth::id());
        if (!$listing) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if (!$this->isPost()) {
            $this->showEditForm($listing);
            return;
        }

        $this->verifyCsrf();
        $input = $_POST;
        foreach ($input as $key => $value) {
            if (!in_array($key, ['amenities', 'delete_images'], true) && !is_string($value)) {
                $input[$key] = '';
            }
        }

        $validated = $this->validateListingInput($input, true, $listing);
        $errors = $validated['errors'];
        $cleanData = $validated['data'];
        $amenityIds = $validated['amenity_ids'];

        $existingImages = (new PropertyImage())->findByPropertyId($id);
        $existingById = [];
        foreach ($existingImages as $img) {
            $existingById[(int)$img['id']] = $img;
        }

        $deleteImageIds = [];
        $submittedDeletes = $input['delete_images'] ?? [];
        if (is_array($submittedDeletes)) {
            foreach ($submittedDeletes as $delId) {
                $delIdInt = filter_var($delId, FILTER_VALIDATE_INT);
                if ($delIdInt && isset($existingById[(int)$delIdInt])) {
                    $deleteImageIds[] = (int)$delIdInt;
                }
            }
            $deleteImageIds = array_values(array_unique($deleteImageIds));
        }

        $hasUploadedFiles = isset($_FILES['images']['name'])
            && is_array($_FILES['images']['name'])
            && count(array_filter($_FILES['images']['name'], fn($n) => $n !== '')) > 0;

        $newImages = $hasUploadedFiles ? $this->uploadedImages($errors, false) : [];
        $remainingCount = count($existingImages) - count($deleteImageIds) + count($newImages);
        if (count($existingImages) > 0 && $remainingCount < 1) {
            $errors[] = 'Tin đăng phải giữ lại hoặc tải lên ít nhất 1 ảnh.';
        }
        if ($remainingCount > 10) {
            $errors[] = 'Tổng số ảnh của tin đăng không được vượt quá 10 ảnh.';
        }

        if ($errors) {
            foreach ($errors as $error) flash('danger', $error);
            $merged = array_merge($listing, $input);
            $merged['amenities'] = $amenityIds;
            $this->showEditForm($merged);
            return;
        }

        $newImagePaths = [];
        $savedFiles = [];
        if (!empty($newImages)) {
            $uploadDir = config('upload_dir');
            $stored = $this->storePropertyData($newImages, $uploadDir);
            if ($stored === null) {
                flash('danger', 'Không thể lưu hoặc nén ảnh đã tải lên. Vui lòng thử lại.');
                $this->showEditForm(array_merge($listing, $input));
                return;
            }
            $newImagePaths = $stored['image_paths'];
            $savedFiles = $stored['saved_files'];
        }

        $coverImageId = isset($input['cover_image_id']) && $input['cover_image_id'] !== ''
            ? (int)$input['cover_image_id']
            : null;

        try {
            $updated = $property->updateOwnedListing(
                $id,
                (int)Auth::id(),
                $cleanData,
                $amenityIds,
                $newImagePaths,
                $deleteImageIds,
                $coverImageId
            );
        } catch (Throwable $error) {
            foreach ($savedFiles as $savedFile) @unlink($savedFile);
            error_log($error->getMessage());
            $updated = false;
        }

        if (!$updated) {
            flash('danger', 'Không thể cập nhật tin đăng. Vui lòng thử lại.');
            $this->showEditForm(array_merge($listing, $input));
            return;
        }

        // Xóa file vật lý của các ảnh đã xóa (bao gồm cả ảnh thumbnail nếu có)
        foreach ($deleteImageIds as $delId) {
            $imagePath = $existingById[$delId]['image_url'] ?? '';
            $fileName = basename($imagePath);
            if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $fileName)) continue;
            $uploadBase = rtrim(config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR;
            $filePath = $uploadBase . $fileName;
            $thumbPath = $uploadBase . 'thumb_' . $fileName;
            if (is_file($filePath) && !@unlink($filePath)) {
                error_log('Unable to remove deleted property image: ' . $filePath);
            }
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        flash('success', 'Tin đăng đã được cập nhật và chuyển về trạng thái chờ duyệt.');
        $this->redirect('agent');
    }

    public function validateListingInput(array $input, bool $isEdit = false, ?array $existingListing = null): array
    {
        $property = new Property();
        $location = new Location();
        $errors = [];

        $title = trim((string)($input['title'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $listingType = (string)($input['listing_type'] ?? '');
        $typeId = filter_var($input['type_id'] ?? '', FILTER_VALIDATE_INT);
        $provinceId = filter_var($input['province_id'] ?? '', FILTER_VALIDATE_INT);
        $districtId = filter_var($input['district_id'] ?? '', FILTER_VALIDATE_INT);
        $rawWard = (string)($input['ward_id'] ?? '');
        $wardId = filter_var($rawWard, FILTER_VALIDATE_INT);
        if (($wardId === false || $wardId === 0) && str_starts_with($rawWard, 'auto_osm_')) {
            $osmWardName = trim(urldecode(substr($rawWard, 9)));
            if ($osmWardName !== '' && $provinceId) {
                $dbConn = Database::connection();
                $wStmt = $dbConn->prepare("SELECT id FROM wards WHERE province_id = ? AND name = ? LIMIT 1");
                $wStmt->execute([(int)$provinceId, $osmWardName]);
                $wRow = $wStmt->fetch();
                if ($wRow) {
                    $wardId = (int)$wRow['id'];
                } else {
                    $insW = $dbConn->prepare("INSERT INTO wards (province_id, district_id, name) VALUES (?, 0, ?)");
                    $insW->execute([(int)$provinceId, $osmWardName]);
                    $wardId = (int)$dbConn->lastInsertId();
                }
            }
        }
        $price = $input['price'] ?? '';
        $area = $input['area'] ?? '';
        $bedrooms = filter_var($input['bedrooms'] ?? 0, FILTER_VALIDATE_INT);
        $bathrooms = filter_var($input['bathrooms'] ?? 0, FILTER_VALIDATE_INT);
        $direction = trim((string)($input['direction'] ?? ''));
        $legalStatus = trim((string)($input['legal_status'] ?? ''));
        $address = trim((string)($input['address_detail'] ?? ''));
        $latitude = ($input['latitude'] ?? '') !== '' ? filter_var($input['latitude'], FILTER_VALIDATE_FLOAT) : null;
        $longitude = ($input['longitude'] ?? '') !== '' ? filter_var($input['longitude'], FILTER_VALIDATE_FLOAT) : null;

        $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        if ($title === '' || $titleLength > 200) {
            $errors[] = 'Tiêu đề không được để trống và tối đa 200 ký tự.';
        }
        if (!in_array($listingType, ['sale', 'rent'], true)) {
            $errors[] = 'Vui lòng chọn loại tin bán hoặc cho thuê.';
        }
        if (!$typeId || !$property->typeExists((int)$typeId)) {
            $errors[] = 'Vui lòng chọn loại bất động sản hợp lệ.';
        }
        if (!is_numeric($price) || (float)$price <= 0) {
            $errors[] = 'Giá phải là số lớn hơn 0.';
        }
        if (!is_numeric($area) || (float)$area <= 0) {
            $errors[] = 'Diện tích phải là số lớn hơn 0.';
        }
        if ($bedrooms === false || $bedrooms < 0 || $bedrooms > 255
            || $bathrooms === false || $bathrooms < 0 || $bathrooms > 255) {
            $errors[] = 'Số phòng ngủ và phòng tắm không hợp lệ.';
        }
        if (strlen($direction) > 120 || strlen($legalStatus) > 400 || strlen($address) > 200) {
            $errors[] = 'Thông tin hướng, pháp lý hoặc địa chỉ vượt quá độ dài cho phép.';
        }
        if ($address === '') {
            $errors[] = 'Vui lòng nhập địa chỉ chi tiết.';
        }
        if ($latitude === null || $latitude === false || $longitude === null || $longitude === false) {
            $errors[] = 'Vui lòng chọn đầy đủ vị trí trên bản đồ.';
        }
        if ($latitude !== null && $latitude !== false && ($latitude < -90 || $latitude > 90)) {
            $errors[] = 'Vĩ độ (latitude) không hợp lệ (-90 đến 90).';
        }
        if ($longitude !== null && $longitude !== false && ($longitude < -180 || $longitude > 180)) {
            $errors[] = 'Kinh độ (longitude) không hợp lệ (-180 đến 180).';
        }

        if ($provinceId && $wardId) {
            $wardRow = (new Ward())->find((int)$wardId);
            if (!$wardRow) {
                $errors[] = 'Phường/Xã đã chọn không hợp lệ.';
            }
        } elseif ($isEdit && !empty($existingListing['ward_id'])) {
            $wardId = (int)$existingListing['ward_id'];
        } else {
            $errors[] = 'Vui lòng chọn đầy đủ tỉnh/thành và phường/xã.';
        }

        $deposit = null;
        if (($input['deposit'] ?? '') !== '') {
            if (!is_numeric($input['deposit']) || (float)$input['deposit'] < 0) {
                $errors[] = 'Tiền đặt cọc không hợp lệ.';
            } else {
                $deposit = (float)$input['deposit'];
            }
        }

        $furniture = ($input['furniture'] ?? '') !== '' ? (string)$input['furniture'] : null;
        if ($furniture !== null && !in_array($furniture, ['none', 'basic', 'full'], true)) {
            $errors[] = 'Tình trạng nội thất không hợp lệ.';
        }

        $rawPets = (string)($input['pets_allowed'] ?? '0');
        if (!in_array($rawPets, ['0', '1'], true)) {
            $errors[] = 'Lựa chọn thú cưng không hợp lệ.';
        }
        $petsAllowed = $rawPets === '1' ? 1 : 0;

        if ($listingType === 'sale' && !$isEdit) {
            $deposit = null;
            $furniture = null;
            $petsAllowed = 0;
        }

        $amenityIds = [];
        $submittedAmenities = $input['amenities'] ?? ($isEdit && !array_key_exists('amenities', $input) ? null : []);
        if ($submittedAmenities !== null) {
            if (!is_array($submittedAmenities)) {
                $errors[] = 'Danh sách tiện ích không hợp lệ.';
            } else {
                foreach ($submittedAmenities as $amenityId) {
                    $aid = filter_var($amenityId, FILTER_VALIDATE_INT);
                    if (!$aid || $aid < 1) {
                        $errors[] = 'Tiện ích đã chọn không hợp lệ.';
                        break;
                    }
                    $amenityIds[] = (int)$aid;
                }
                $amenityIds = array_values(array_unique($amenityIds));
                if (!$errors && !(new Amenity())->hasIds($amenityIds)) {
                    $errors[] = 'Một hoặc nhiều tiện ích không hợp lệ.';
                }
            }
        } else {
            $amenityIds = $existingListing && !empty($existingListing['id'])
                ? $property->amenityIdsForListing((int)$existingListing['id'])
                : [];
        }

        return [
            'errors' => $errors,
            'amenity_ids' => $amenityIds,
            'data' => [
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
                'direction' => $direction !== '' ? $direction : null,
                'legal_status' => $legalStatus !== '' ? $legalStatus : null,
                'furniture' => $furniture,
                'pets_allowed' => $petsAllowed,
                'address_detail' => $address ?: null,
                'latitude' => ($latitude !== null && $latitude !== false) ? (float)$latitude : null,
                'longitude' => ($longitude !== null && $longitude !== false) ? (float)$longitude : null,
            ],
        ];
    }

    public function hide(int $id): void
    {
        $this->requirePostAndCsrf();
        if (!(new Property())->setOwnedVisibility($id, (int)Auth::id(), true)) {
            flash('danger', 'Chỉ có thể ẩn tin đã được duyệt của bạn.');
        } else {
            flash('success', 'Tin đăng đã được ẩn.');
        }
        $this->redirect('agent');
    }

    public function restore(int $id): void
    {
        $this->requirePostAndCsrf();
        if (!(new Property())->setOwnedVisibility($id, (int)Auth::id(), false)) {
            flash('danger', 'Không thể khôi phục tin đăng này.');
        } else {
            flash('success', 'Tin đăng đã được gửi lại để duyệt.');
        }
        $this->redirect('agent');
    }

    public function status(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Phương thức không được hỗ trợ'], 405);
            return;
        }
        $this->verifyCsrf();
        $status = $_POST['status'] ?? '';
        if (!is_string($status) || !in_array($status, ['pending', 'hidden', 'closed'], true)) {
            $this->json(null, false, 'Trạng thái cần chuyển không hợp lệ.', 422);
        }

        try {
            $changed = (new Property())->setOwnedStatus($id, (int)Auth::id(), $status);
        } catch (PDOException $error) {
            error_log('Unable to change owned property status: ' . $error->getMessage());
            $message = (int)($error->errorInfo[1] ?? 0) === 1644
                ? (string)($error->errorInfo[2] ?? 'Không thể đổi trạng thái tin.')
                : 'Không thể đổi trạng thái tin. Vui lòng thử lại.';
            $this->json(null, false, $message, 409);
        }

        if (!$changed) {
            $this->json(null, false, 'Tin không tồn tại hoặc không thể đổi trạng thái hiện tại.', 409);
        }
        $this->json(['id' => $id, 'status' => $status], true, 'Đã cập nhật trạng thái tin.');
    }

    public function renew(int $id): void
    {
        $this->requirePostAndCsrf();
        $ok = (new Property())->renewOwnedListing($id, (int)Auth::id(), 30);
        if ($this->wantsJson()) {
            $this->json(['id' => $id], $ok, $ok ? 'Đã gia hạn tin đăng thêm 30 ngày.' : 'Không thể gia hạn tin đăng này.', $ok ? 200 : 400);
            return;
        }
        if ($ok) {
            flash('success', 'Đã gia hạn tin đăng thêm 30 ngày hiển thị.');
        } else {
            flash('danger', 'Không tìm thấy tin đăng của bạn để gia hạn.');
        }
        $this->redirect('agent');
    }

    public function delete(int $id): void
    {
        $this->requirePostAndCsrf();
        $property = new Property();
        $listing = $property->findOwned($id, (int)Auth::id());
        if (!$listing) {
            flash('danger', 'Không tìm thấy tin đăng của bạn để xóa.');
            $this->redirect('agent');
        }

        $imagePaths = $property->imagesForListing($id);
        if (!$property->deleteOwned($id, (int)Auth::id())) {
            flash('danger', 'Không thể xóa tin đăng. Vui lòng thử lại.');
            $this->redirect('agent');
        }
        $storage = new ImageStorage();
        foreach ($imagePaths as $imagePath) {
            try {
                if (class_exists('ImageStorage') && config('image_storage') === 'cloudinary') {
                    $storage->remove($imagePath);
                } else {
                    $fileName = basename($imagePath);
                    if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $fileName)) {
                        $uploadBase = rtrim(config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR;
                        $filePath = $uploadBase . $fileName;
                        $thumbPath = $uploadBase . 'thumb_' . $fileName;
                        if (is_file($filePath) && !unlink($filePath)) {
                            error_log('Unable to remove deleted property image: ' . $filePath);
                        }
                        if (is_file($thumbPath)) {
                            @unlink($thumbPath);
                        }
                    }
                }
            } catch (Throwable $error) {
                error_log('Unable to remove deleted property image: ' . $error->getMessage());
            }
        }
        flash('success', 'Tin đăng đã được xóa.');
        $this->redirect('agent');
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

        // Kiểm tra ảnh trước các truy vấn xác thực thông tin tin đăng.
        // Không có ảnh thì trả lại form ngay, giữ nguyên các trường đã nhập.
        $errors = [];
        $images = $this->uploadedImages($errors);
        if (!$images) {
            foreach ($errors as $error) flash('danger', $error);
            $this->showCreateForm($input);
            return;
        }

        $validated = $this->validateListingInput($input, false);
        $errors = $validated['errors'];
        $propertyData = $validated['data'];
        $propertyData['user_id'] = Auth::id();
        $amenityIds = $validated['amenity_ids'];

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
        $stored = $this->storePropertyData($images, $uploadDir);
        if ($stored === null) {
            flash('danger', 'Không thể lưu hoặc nén ảnh đã tải lên. Vui lòng thử lại.');
            $this->showCreateForm($input);
            return;
        }
        $imagePaths = $stored['image_paths'];
        $savedFiles = $stored['saved_files'];

        try {
            (new Property())->createListing($propertyData, $imagePaths, $amenityIds, $coverIndex);
        } catch (Throwable $error) {
            foreach ($savedFiles as $savedFile) @unlink($savedFile);
            error_log($error->getMessage());
            flash('danger', 'Không thể lưu tin đăng. Vui lòng thử lại.');
            $this->showCreateForm($input);
            return;
        }

        flash('success', 'Tin đăng đã được gửi và đang chờ duyệt (pending).');
        $this->redirect('agent/create');
    }

    /**
     * Nén và resize ảnh tự động sang WebP (tối đa 1600px, quality 82) + sinh ảnh thumbnail 450x300px
     * sử dụng PHP GD (imagecreatetruecolor, imagecopyresampled, imagewebp).
     *
     * @param array<int, array{tmp_name: string, extension: string}> $images
     * @return array{image_paths: string[], saved_files: string[]}|null
     */
    public function storePropertyData(array $images, string $uploadDir): ?array
    {
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return null;
        }

        $imagePaths = [];
        $savedFiles = [];

        foreach ($images as $image) {
            $res = ImageHelper::processPropertyImage($image['tmp_name'], $uploadDir, 1600, 82, true);
            if ($res === null) {
                foreach ($savedFiles as $savedFile) {
                    if (is_file($savedFile)) @unlink($savedFile);
                }
                return null;
            }
            $savedFiles[] = $res['full_path'];
            if (!empty($res['thumb_path'])) {
                $savedFiles[] = $res['thumb_path'];
            }
            $imagePaths[] = $res['relative_path'];
        }

        return [
            'image_paths' => $imagePaths,
            'saved_files' => $savedFiles,
        ];
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

    private function showEditForm(array $listing): void
    {
        $location = new Location();
        $property = new Property();
        $provinceId = (int)($listing['province_id'] ?? 0);
        $districtId = (int)($listing['district_id'] ?? 0);
        $wardId = (int)($listing['ward_id'] ?? 0);

        if ($wardId > 0 && !$provinceId) {
            $wardRow = (new Ward())->find($wardId);
            if ($wardRow) {
                $provinceId = (int)($wardRow['province_id'] ?? 0);
                if (!$provinceId && !empty($wardRow['district_id'])) {
                    $provinceId = (int)($location->provinceByDistrict((int)$wardRow['district_id']) ?? 0);
                }
            }
            $listing['province_id'] = $provinceId;
        }

        $selectedAmenities = isset($listing['amenities']) && is_array($listing['amenities'])
            ? array_map('intval', $listing['amenities'])
            : (!empty($listing['id']) ? $property->amenityIdsForListing((int)$listing['id']) : []);

        $existingImages = !empty($listing['id'])
            ? (new PropertyImage())->findByPropertyId((int)$listing['id'])
            : [];

        $this->view('agent/edit', [
            'title' => 'Sửa tin đăng',
            'listing' => $listing,
            'propertyTypes' => (new PropertyType())->allSorted(),
            'provinces' => $location->provinces(),
            'districts' => $provinceId > 0 ? $location->districtsByProvince($provinceId) : [],
            'wards' => $provinceId > 0 ? $location->wardsByProvince($provinceId) : [],
            'amenities' => (new Amenity())->allSorted(),
            'selectedAmenities' => $selectedAmenities,
            'existingImages' => $existingImages,
        ]);
    }

    private function requirePostAndCsrf(): void
    {
        if (!$this->isPost()) {
            http_response_code(405);
            header('Allow: POST');
            exit('Phương thức không được hỗ trợ.');
        }
        $this->verifyCsrf();
    }

    private function uploadedImages(array &$errors, bool $requireAtLeastOne = true): array
    {
        $images = [];
        $files = $_FILES['images'] ?? null;
        if (!$files || !isset($files['name']) || !is_array($files['name'])) {
            if ($requireAtLeastOne) {
                $errors[] = 'Vui lòng tải lên ít nhất một ảnh.';
            }
            return $images;
        }

        $nonEmptyIndices = [];
        foreach ($files['name'] as $idx => $name) {
            if ($name !== '' && ($files['error'][$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $nonEmptyIndices[] = $idx;
            }
        }

        $count = count($nonEmptyIndices);
        if ($count < 1) {
            if ($requireAtLeastOne) {
                $errors[] = 'Vui lòng tải lên ít nhất một ảnh.';
            }
            return $images;
        }
        if ($count > 10) {
            $errors[] = 'Tải lên từ 1 đến 10 ảnh.';
            return $images;
        }

        $types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($nonEmptyIndices as $index) {
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
