<?php
// URL: /admin/catalog   [A]  Quản lý danh mục: loại BĐS, tiện ích, tỉnh/thành, quận/huyện, phường/xã
class AdminCatalogController extends Controller
{
    // Danh sách tab hợp lệ (whitelist) -> nhãn, lớp Model, độ dài tên tối đa, cột cha
    private const TABS = [
        'types'     => ['label' => 'Loại BĐS',    'noun' => 'loại BĐS', 'class' => 'PropertyType', 'max' => 50,  'parent' => null],
        'amenities' => ['label' => 'Tiện ích',    'noun' => 'tiện ích', 'class' => 'Amenity',      'max' => 100, 'parent' => null],
        'provinces' => ['label' => 'Tỉnh/Thành',  'noun' => 'tỉnh/thành', 'class' => 'Province',     'max' => 100, 'parent' => null],
        'districts' => ['label' => 'Quận/Huyện',  'noun' => 'quận/huyện', 'class' => 'District',     'max' => 100, 'parent' => 'province_id'],
        'wards'     => ['label' => 'Phường/Xã',   'noun' => 'phường/xã', 'class' => 'Ward',         'max' => 100, 'parent' => 'district_id'],
    ];

    public function __construct() { $this->requireRole('admin'); }

    public function index(): void
    {
        $tab = $this->tab($_GET['tab'] ?? '');
        $fProvince = (int)($_GET['province'] ?? 0);
        $fDistrict = (int)($_GET['district'] ?? 0);

        $model = new (self::TABS[$tab]['class'])();
        $rows = $model->adminList(['province_id' => $fProvince, 'district_id' => $fDistrict]);

        $this->view('admin/catalog', [
            'title'      => 'Quản lý danh mục',
            'tabs'       => self::TABS,
            'tab'        => $tab,
            'rows'       => $rows,
            'provinces'  => (new Location())->provinces(),
            'fProvince'  => $fProvince,
            'fDistrict'  => $fDistrict,
        ]);
    }

    /** POST admin/catalog/save: thêm (item_id rỗng) hoặc sửa */
    public function save(): void
    {
        $this->postOnly();
        $tab = $this->tab($_POST['tab'] ?? '');
        $cfg = self::TABS[$tab];
        $model = new $cfg['class']();
        $id = (int)($_POST['item_id'] ?? 0);
        $name = trim(preg_replace('/\s+/u', ' ', (string)($_POST['name'] ?? '')));
        $parentId = null;

        $error = null;
        if ($name === '' || str_len($name) > $cfg['max']) {
            $error = "Tên {$cfg['noun']} bắt buộc, tối đa {$cfg['max']} ký tự.";
        } elseif ($id && !$model->find($id)) {
            $error = 'Không tìm thấy mục cần sửa.';
        } elseif ($cfg['parent']) {
            $parentId = (int)($_POST[$cfg['parent']] ?? 0);
            $parentModel = $cfg['parent'] === 'province_id' ? new Province() : new District();
            if (!$parentId || !$parentModel->find($parentId)) {
                $error = $cfg['parent'] === 'province_id' ? 'Vui lòng chọn Tỉnh/Thành.' : 'Vui lòng chọn Quận/Huyện.';
            }
        }
        if (!$error && $model->nameExists($name, $parentId, $id)) {
            $error = "“{$name}” đã tồn tại.";
        }

        if ($error) {
            flash('danger', $error);
        } else {
            $data = ['name' => $name];
            if ($cfg['parent']) $data[$cfg['parent']] = $parentId;
            if ($id) { $model->update($id, $data); flash('success', 'Đã cập nhật.'); }
            else     { $model->create($data);      flash('success', 'Đã thêm mới.'); }
        }
        $this->back($tab);
    }

    /** POST admin/catalog/delete: chỉ xóa khi không có tin/mục con đang dùng */
    public function delete(): void
    {
        $this->postOnly();
        $tab = $this->tab($_POST['tab'] ?? '');
        $model = new (self::TABS[$tab]['class'])();
        $id = (int)($_POST['item_id'] ?? 0);

        if (!$id || !$model->find($id)) {
            flash('danger', 'Không tìm thấy mục cần xóa.');
        } elseif (($n = $model->usageCount($id)) > 0) {
            flash('danger', "Không thể xóa: đang được dùng ở $n tin đăng.");
        } elseif (($c = $model->childCount($id)) > 0) {
            flash('danger', "Không thể xóa: còn $c mục con. Hãy xóa các mục con trước.");
        } else {
            try { $model->delete($id); flash('success', 'Đã xóa.'); }
            catch (PDOException $e) { flash('danger', 'Không thể xóa mục này vì còn dữ liệu liên quan.'); }
        }
        $this->back($tab);
    }

    private function tab(string $t): string { return isset(self::TABS[$t]) ? $t : 'types'; }

    private function postOnly(): void
    {
        if (!$this->isPost()) $this->redirect('admin/catalog');
        $this->verifyCsrf();
    }

    /** Quay lại đúng tab + bộ lọc (chỉ nhận số nguyên, tránh open redirect). */
    private function back(string $tab): void
    {
        $q = ['tab' => $tab];
        if ((int)($_POST['f_province'] ?? 0)) $q['province'] = (int)$_POST['f_province'];
        if ((int)($_POST['f_district'] ?? 0)) $q['district'] = (int)$_POST['f_district'];
        $this->redirect('admin/catalog?' . http_build_query($q));
    }
}
