<?php
/** Architectural and integration regression for procedure-only runtime access.
 * SQL in this file is test setup/inspection, never application data access.
 * Requires an isolated BDS_TEST_DB initialized with schema, seed and procedures-online.sql.
 */
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$GLOBALS['config'] = require ROOT . '/config/config.php';
$testName = getenv('BDS_TEST_DB') ?: '';
if (!preg_match('/^batdongsan_test_[a-z0-9_]+$/D', $testName)) {
    fwrite(STDERR, "Set BDS_TEST_DB to an isolated test database.\n");
    exit(1);
}
$GLOBALS['config']['db']['name'] = $testName;
require APP . '/helpers/functions.php';
spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'helpers', 'controllers', 'controllers/admin'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require_once $file; return; }
    }
});
$checks = 0;
function verifyProcedure(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException($label);
    $checks++;
    echo "PASS $label\n";
}

// Enforce that all runtime DB execution is centralized and CALL-only.
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP));
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $source = file_get_contents($file->getPathname());
    if ($file->getFilename() !== 'Database.php') {
        verifyProcedure(!preg_match('/->\s*(?:prepare|query|exec)\s*\(/', $source), $file->getFilename() . ' has no native SQL execution');
    }
    verifyProcedure(!preg_match('/[\'"]\s*(?:SELECT|INSERT\s+INTO|UPDATE\s+\w+\s+SET|DELETE\s+FROM)\b/i', $source), $file->getFilename() . ' contains no SQL data statement');
}
$db = Database::connection();
$db->exec("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
$procedures = $db->query('SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_TYPE=\'PROCEDURE\'')->fetchAll(PDO::FETCH_COLUMN);
$bundle = file_get_contents(ROOT . '/deploy/procedures-online.sql');
preg_match_all('/CREATE\s+PROCEDURE\s+(\w+)/i', $bundle, $matches);
verifyProcedure(array_diff(array_unique($matches[1]), $procedures) === [], 'Online bundle installs every declared procedure');
verifyProcedure(!preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE|DROP\s+TABLE|TRUNCATE\s|ALTER\s+TABLE)/mi', $bundle), 'Online bundle preserves tables and the selected database');

$db->beginTransaction();
try {
    $users = new User();
    $user = $users->findByEmail('agent@demo.local');
    verifyProcedure($user !== null, 'User lookup through procedure');
    $session = $users->sessionUser((int)$user['id']);
    verifyProcedure(isset($session['password_hash'], $session['role_id']) && array_key_exists('avatar', $session) && array_key_exists('bio', $session), 'Session procedure preserves authentication and profile fields');
    $users->updateProfile((int)$user['id'], 'Procedure regression', '0900000002', 'Biography', 'uploads/avatar-test.webp');
    $updated = $users->find((int)$user['id']);
    verifyProcedure($updated['bio'] === 'Biography' && $updated['avatar'] === 'uploads/avatar-test.webp', 'Optional profile fields persist');
    $users->updateProfile((int)$user['id'], $user['full_name'], $user['phone']);
    verifyProcedure($users->find((int)$user['id'])['bio'] === 'Biography', 'Omitted profile fields remain unchanged');
    $token = $users->createPasswordResetToken($user['email']);
    verifyProcedure($users->findValidPasswordReset($token) !== null, 'Password reset token is readable');
    $users->deletePasswordResetToken($token);
    verifyProcedure($users->findValidPasswordReset($token) === null, 'Password reset token can be revoked');

    $location = new Location();
    $province = $location->provinces()[0];
    $ward = $location->wardsByProvince((int)$province['id'])[0];
    verifyProcedure($location->isValidWard((int)$province['id'], (int)$ward['id']), 'Two-tier location validation uses procedure');
    verifyProcedure(is_array($location->searchAdministrative('Sai Gon')), 'Administrative search remains callable');
    $catalog = new Ward();
    $newWard = $catalog->create(['name'=>'Procedure ward', 'province_id'=>$province['id']]);
    verifyProcedure($newWard > 0 && $catalog->find($newWard)['district_id'] === null, 'Catalog create returns the inserted ID with nullable district');
    verifyProcedure($catalog->nameExists('Procedure ward', (int)$province['id']), 'Catalog duplicate lookup');
    $catalog->update($newWard, ['name'=>'Procedure ward updated', 'province_id'=>$province['id']]);
    verifyProcedure($catalog->find($newWard)['name'] === 'Procedure ward updated', 'Catalog update');
    verifyProcedure($catalog->delete($newWard), 'Catalog delete affected-row result');

    $notification = new Notification();
    $notificationId = Notification::create((int)$user['id'], 'Procedure test', 'Body');
    verifyProcedure($notificationId > 0 && $notification->findForUser($notificationId, (int)$user['id']) !== null, 'Notification insert ID and ownership lookup');
    verifyProcedure(!$notification->markAsRead($notificationId, 99999999), 'Foreign user cannot mark notification read');
    verifyProcedure($notification->markAsRead($notificationId, (int)$user['id']), 'Owner can mark notification read');
    verifyProcedure(!$notification->markAsRead($notificationId, (int)$user['id']), 'Unchanged write reports zero affected rows');

    $property = new Property();
    $id = $property->create(['user_id'=>$user['id'],'type_id'=>1,'ward_id'=>$ward['id'], 'title'=>'Procedure JSON test',
        'listing_type'=>'sale','price'=>1234567890.25,'area'=>75.5,'status'=>'approved']);
    $p = $property->find($id);
    verifyProcedure($p['status'] === 'approved' && abs((float)$p['price']-1234567890.25)<0.01 && (int)$p['bedrooms']===0, 'Generic create preserves decimals and schema defaults');
    $property->update($id, ['description'=>null, 'title'=>'Procedure JSON updated']);
    verifyProcedure($property->find($id)['description'] === null && (float)$property->find($id)['area']===75.5, 'Sparse generic update preserves omitted columns and explicit null');
    $finder = new PropertyFinder();
    verifyProcedure(count($finder->forMap(['keyword'=>'Procedure JSON updated'])) === 1, 'Map filtering uses stored procedure');
    verifyProcedure($finder->countSearch(['keyword'=>"' OR 1=1 --"])===0, 'Search values cannot become SQL');
    verifyProcedure(count((new AdminStats())->propertiesByProvince()) > 0, 'Province dashboard procedure returns data');
    verifyProcedure((new AdminStats())->counts()['Tin đã duyệt'] > 0, 'Dashboard counts include approved listings');
    $stats = (new PropertyModeration())->paginate('all', 1, 5);
    verifyProcedure(count($stats['items']) > 0 && $stats['total'] >= count($stats['items']), 'All-status moderation page');
    verifyProcedure((new PropertyModeration())->moderate($id, 'reject', 'Test rejection')['ok'], 'Moderation rejection');
    verifyProcedure((new PropertyModeration())->moderate($id, 'approve')['ok'] && $property->find($id)['expired_at'] !== null, 'Reapproval initializes expiry inside procedure');

    // Deliberately missing routines must fail visibly, never return empty fake results.
    try {
        Database::call('sp_required_missing_for_regression');
        throw new LogicException('Missing procedure was swallowed');
    } catch (RuntimeException $error) {
        verifyProcedure(str_contains($error->getMessage(), 'Missing required procedure'), 'Missing procedure produces an installation error');
    }
    verifyProcedure($users->find((int)$user['id']) !== null, 'Connection remains usable after failed CALL');
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
echo "$checks procedure-only checks passed\n";
