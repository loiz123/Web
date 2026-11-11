<?php
/** HTTP/database regression for admin verification and status mutations.
 * Requires a disposable BDS_TEST_DB and BDS_TEST_HTTP_BASE pointing at the same DB.
 */
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$GLOBALS['config'] = require ROOT . '/config/config.php';
$name = getenv('BDS_TEST_DB') ?: '';
$base = rtrim(getenv('BDS_TEST_HTTP_BASE') ?: '', '/');
if (!preg_match('/^batdongsan_test_[a-z0-9_]+$/', $name) || !preg_match('~^http://(?:127\.0\.0\.1|localhost)(?::\d+)?(?:/.*)?$~', $base)) {
    fwrite(STDERR, "Set BDS_TEST_DB and a local BDS_TEST_HTTP_BASE for a disposable test installation.\n");
    exit(1);
}
$GLOBALS['config']['db']['name'] = $name;
require APP . '/helpers/functions.php';
require APP . '/core/Database.php';
$db = Database::connection();
$owner = (int)$db->query("SELECT id FROM users WHERE email='agent@demo.local'")->fetchColumn();
$type = (int)$db->query('SELECT id FROM property_types LIMIT 1')->fetchColumn();
$ward = (int)$db->query('SELECT id FROM wards LIMIT 1')->fetchColumn();
$title = 'Admin guard regression ' . bin2hex(random_bytes(6));
$db->prepare("INSERT INTO properties (user_id,type_id,ward_id,title,listing_type,price,area,status) VALUES (?,?,?,?,'sale',1000000000,75,'approved')")->execute([$owner, $type, $ward, $title]);
$id = (int)$db->lastInsertId();
$curl = curl_init();
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false]);
function adminRequest(string $path, ?array $data = null, ?string $csrf = null): array {
    global $curl, $base;
    curl_setopt($curl, CURLOPT_URL, $base . $path);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(['Accept: application/json'], $csrf !== null ? ['X-CSRF-Token: ' . $csrf] : []));
    if ($data === null) curl_setopt($curl, CURLOPT_HTTPGET, true);
    else { curl_setopt($curl, CURLOPT_POST, true); curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data)); }
    $body = curl_exec($curl);
    return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $body, 'json' => json_decode((string)$body, true)];
}
function checkAdmin(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
try {
    $login = adminRequest('/auth/login');
    preg_match('/name="_csrf" value="([a-f0-9]+)"/', $login['body'], $matches);
    $token = $matches[1] ?? '';
    checkAdmin($token !== '', 'Login form provides a CSRF token');
    $login = adminRequest('/auth/login', ['email' => 'admin@demo.local', 'password' => 'Admin@123', '_csrf' => $token]);
    checkAdmin($login['status'] === 302, 'Admin test account logs in');
    $detail = adminRequest('/property/detail/' . $id);
    checkAdmin(str_contains((string)$detail['body'], $title), 'HTTP server points at the same disposable test fixture');
    foreach (['verify', 'changeStatus'] as $action) {
        $path = '/admin/properties/' . $action . '/' . $id;
        checkAdmin(adminRequest($path . '?status=hidden')['status'] === 405, "$action rejects GET");
        checkAdmin(adminRequest($path, ['status' => 'hidden'])['status'] === 419, "$action rejects POST without CSRF");
        checkAdmin(adminRequest($path, ['status' => 'hidden'], 'invalid')['status'] === 419, "$action rejects invalid CSRF");
    }
    $row = $db->query('SELECT status,is_verified FROM properties WHERE id=' . $id)->fetch();
    checkAdmin($row['status'] === 'approved' && (int)$row['is_verified'] === 0, 'Rejected requests leave property unchanged');
    $res = adminRequest('/admin/properties/verify/' . $id, [], $token);
    checkAdmin($res['status'] === 200 && $res['json']['success'] && (int)$res['json']['is_verified'] === 1, 'Valid POST grants verification');
    $res = adminRequest('/admin/properties/changeStatus/' . $id, ['status' => 'hidden'], $token);
    checkAdmin($res['status'] === 200 && $res['json']['success'], 'Valid POST updates visibility');
} finally {
    curl_close($curl);
    $db->prepare('DELETE FROM properties WHERE id=?')->execute([$id]);
    $db->prepare("DELETE FROM notifications WHERE message LIKE '%Admin guard regression%'")->execute();
}
