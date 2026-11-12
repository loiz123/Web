<?php
/** Image lifecycle regression. Cloudinary transport is replaced with an in-memory fake. */
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$directory = sys_get_temp_dir() . '/bds-agent-images-' . bin2hex(random_bytes(6));
$GLOBALS['config'] = ['image_storage' => 'cloudinary', 'upload_dir' => $directory, 'base_url' => ''];
require APP . '/helpers/functions.php';
require APP . '/core/Controller.php';
require APP . '/helpers/ImageHelper.php';
require APP . '/controllers/AgentController.php';
class ImageStorage
{
    public static array $stored = [];
    public static array $removed = [];
    public static ?int $failAt = null;
    public function store(string $path, string $extension): string
    {
        if (!is_file($path)) throw new RuntimeException('Missing processed image');
        if (self::$failAt === count(self::$stored) + 1) throw new RuntimeException('Simulated upload failure');
        $url = 'https://res.cloudinary.com/test/image/upload/bds/properties/' . bin2hex(random_bytes(16)) . '.' . $extension;
        self::$stored[] = $url;
        return $url;
    }
    public function remove(string $path): void { self::$removed[] = $path; }
}
$controller = (new ReflectionClass(AgentController::class))->newInstanceWithoutConstructor();
$file = ROOT . '/map_test_verified.png';
$input = ['tmp_name' => $file, 'extension' => 'png'];
function checkAgentImages(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
try {
    $stored = $controller->storePropertyData([$input, $input], $directory);
    checkAgentImages($stored !== null && count($stored['image_paths']) === 2, 'Process and upload every selected image');
    checkAgentImages($stored['image_paths'] === ImageStorage::$stored, 'Persist Cloudinary URLs instead of ephemeral local paths');
    checkAgentImages(glob($directory . '/*') === [], 'Successful cloud upload removes local originals and thumbnails');
    $discard = new ReflectionMethod(AgentController::class, 'discardStoredImages');
    $discard->invoke($controller, $stored['image_paths'], $stored['saved_files']);
    checkAgentImages(ImageStorage::$removed === ImageStorage::$stored, 'Image deletion and database rollback use cloud storage cleanup');

    ImageStorage::$stored = ImageStorage::$removed = [];
    ImageStorage::$failAt = 2;
    $failed = $controller->storePropertyData([$input, $input, $input], $directory);
    checkAgentImages($failed === null, 'Partial upload failure rejects the image batch');
    checkAgentImages(count(ImageStorage::$stored) === 1 && ImageStorage::$removed === ImageStorage::$stored, 'Partial upload failure removes images already uploaded');
    checkAgentImages(glob($directory . '/*') === [], 'Partial upload failure leaves no local temporary images');

    $GLOBALS['config']['image_storage'] = 'local';
    $local = $controller->storePropertyData([$input], $directory);
    checkAgentImages($local !== null && str_starts_with($local['image_paths'][0], 'uploads/properties/'), 'Local storage preserves local URL behavior');
    checkAgentImages(is_file($local['saved_files'][0]), 'Local storage retains the processed image');
} finally {
    foreach (glob($directory . '/*') ?: [] as $path) if (is_file($path)) unlink($path);
    if (is_dir($directory)) rmdir($directory);
}
