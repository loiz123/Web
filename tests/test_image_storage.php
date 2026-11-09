<?php
declare(strict_types=1);

$dir = sys_get_temp_dir() . '/bds-storage-test-' . bin2hex(random_bytes(6));
$GLOBALS['config'] = [
    'base_url' => '/demo',
    'upload_dir' => $dir,
    'image_storage' => 'local',
    'cloudinary' => ['cloud_name' => 'testcloud', 'api_key' => '', 'api_secret' => ''],
];
require dirname(__DIR__) . '/app/helpers/functions.php';
require dirname(__DIR__) . '/app/core/ImageStorage.php';

$name = str_repeat('a', 32);
$url = 'https://res.cloudinary.com/testcloud/image/upload/v123/bds/properties/' . $name . '.jpg';
if (image_url($url) !== $url || image_url('uploads/test.jpg') !== '/demo/uploads/test.jpg') throw new RuntimeException('Image URL');
$storage = new ImageStorage();
$extractId = new ReflectionMethod($storage, 'cloudinaryId');
if ($extractId->invoke($storage, $url) !== 'bds/properties/' . $name) throw new RuntimeException('Cloudinary ID');
if ($extractId->invoke($storage, 'https://example.com/image/upload/v123/bds/properties/' . $name . '.jpg') !== null) throw new RuntimeException('Untrusted image host');
mkdir($dir);
try {
    file_put_contents($dir . '/' . $name . '.jpg', 'test');
    $storage->remove('uploads/properties/' . $name . '.jpg');
    if (file_exists($dir . '/' . $name . '.jpg')) throw new RuntimeException('Image cleanup');
} finally {
    if (is_file($dir . '/' . $name . '.jpg')) unlink($dir . '/' . $name . '.jpg');
    rmdir($dir);
}
echo "Image storage checks passed\n";
