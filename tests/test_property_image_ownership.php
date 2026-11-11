<?php
/** Database regression for newly uploaded cover images and image ownership. */
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$GLOBALS['config'] = require ROOT . '/config/config.php';
$name = getenv('BDS_TEST_DB') ?: '';
if (!preg_match('/^batdongsan_test_[a-z0-9_]+$/', $name)) {
    fwrite(STDERR, "Set BDS_TEST_DB to a disposable batdongsan_test_* database.\n");
    exit(1);
}
$GLOBALS['config']['db']['name'] = $name;
require APP . '/helpers/functions.php';
spl_autoload_register(function (string $class): void {
    foreach (['core', 'models'] as $dir) {
        $path = APP . "/$dir/$class.php";
        if (is_file($path)) { require_once $path; return; }
    }
});
$db = Database::connection();
$owner = (int)$db->query("SELECT id FROM users WHERE email='agent@demo.local'")->fetchColumn();
$type = (int)$db->query('SELECT id FROM property_types LIMIT 1')->fetchColumn();
$ward = (int)$db->query('SELECT id FROM wards LIMIT 1')->fetchColumn();
$label = 'Image ownership regression ' . bin2hex(random_bytes(6));
$db->prepare("INSERT INTO users (role_id,full_name,email,password_hash) SELECT id,?,?,? FROM roles WHERE name='agent'")->execute([$label, 'image.' . bin2hex(random_bytes(6)) . '@example.invalid', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
$other = (int)$db->lastInsertId();
$model = new Property();
$images = new PropertyImage();
$properties = [];
function checkImageOwnership(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
try {
    foreach ([$owner, $other] as $user) {
        $properties[] = $model->create(['user_id' => $user, 'type_id' => $type, 'ward_id' => $ward, 'title' => $label, 'listing_type' => 'sale', 'price' => 1000000000, 'area' => 75, 'status' => 'approved']);
    }
    [$a, $b] = $properties;
    $aImage = $images->createForProperty($a, 'uploads/properties/' . bin2hex(random_bytes(16)) . '.jpg', true);
    $bImage = $images->createForProperty($b, 'uploads/properties/' . bin2hex(random_bytes(16)) . '.jpg', true);
    $newUrl = 'uploads/properties/' . bin2hex(random_bytes(16)) . '.jpg';
    $data = $model->findOwned($a, $owner);
    checkImageOwnership($model->updateOwnedListing($a, $owner, $data, [], [$newUrl], [], $aImage, 0), 'Owner adds a new image selected as cover');
    checkImageOwnership($images->coverByPropertyId($a) === $newUrl, 'New image selection replaces previous cover');
    checkImageOwnership(count(array_filter($images->findByPropertyId($a), fn($r) => (int)$r['is_cover'] === 1)) === 1, 'Property has exactly one cover');
    $before = $images->findByPropertyId($a);
    checkImageOwnership(!$model->updateOwnedListing($a, $other, $data, [], [], [$aImage], $bImage), 'Other agent cannot edit property or images');
    checkImageOwnership($images->findByPropertyId($a) === $before, 'Denied cross-owner update leaves images unchanged');
    $otherData = $model->findOwned($b, $other);
    checkImageOwnership($model->updateOwnedListing($b, $other, $otherData, [], [], [$aImage], $aImage), 'Own property edit tolerates foreign IDs without applying them');
    checkImageOwnership($images->findByPropertyId($a) === $before && (int)$images->findByPropertyId($b)[0]['id'] === $bImage, 'Foreign delete/cover IDs cannot change either cover');
    checkImageOwnership(!$model->updateOwnedListing($a, $owner, $data, [], [], [], null, 5), 'Invalid new cover index is rejected');
} finally {
    foreach ($properties as $id) $db->prepare('DELETE FROM properties WHERE id=?')->execute([$id]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$other]);
}
