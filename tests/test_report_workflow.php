<?php
/** Regression for report submission on the hosting enum: open/resolved/dismissed.
 * Run only against a disposable database, for example:
 * BDS_TEST_DB=batdongsan_test_20261111 DB_PORT=3311 php tests/test_report_workflow.php
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$GLOBALS['config'] = require ROOT . '/config/config.php';
$testDatabase = getenv('BDS_TEST_DB') ?: '';
if (!preg_match('/^batdongsan_test_[a-z0-9_]+$/', $testDatabase)) {
    fwrite(STDERR, "Set BDS_TEST_DB to a disposable batdongsan_test_* database.\n");
    exit(1);
}
$GLOBALS['config']['db']['name'] = $testDatabase;
spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'helpers'] as $dir) {
        $path = APP . "/$dir/$class.php";
        if (is_file($path)) { require_once $path; return; }
    }
});
require APP . '/helpers/functions.php';

$db = Database::connection();
$db->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
$statusType = $db->query("SHOW COLUMNS FROM reports LIKE 'status'")->fetch()['Type'];
if ($statusType !== "enum('open','resolved','dismissed')") {
    fwrite(STDERR, "Use the hosting-compatible reports enum without pending to exercise this regression.\n");
    exit(1);
}
$agent = (int)$db->query("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='agent' LIMIT 1")->fetchColumn();
$customer = (int)$db->query("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='customer' LIMIT 1")->fetchColumn();
$type = (int)$db->query('SELECT id FROM property_types LIMIT 1')->fetchColumn();
$ward = (int)$db->query('SELECT id FROM wards LIMIT 1')->fetchColumn();
if (!$agent || !$customer || !$type || !$ward) throw new RuntimeException('Missing test seed data');

$property = (new Property())->create([
    'user_id' => $agent, 'type_id' => $type, 'ward_id' => $ward,
    'title' => 'Report regression ' . bin2hex(random_bytes(6)), 'listing_type' => 'sale',
    'price' => 1000000000, 'area' => 75, 'status' => 'approved',
]);
$checks = 0;
function checkReport(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
    echo "PASS $message\n";
}
try {
    foreach ([null, 'Description on initial report'] as $description) {
        $mode = $description === null ? 'procedure without description' : 'procedure with description';
        $db->prepare("UPDATE properties SET status='approved' WHERE id=?")->execute([$property]);
        $model = new Report();
        $id = $model->submit($property, $customer, "$mode first report", $description);
        $created = $model->getDetail($id);
        checkReport($id > 0 && $created['status'] === 'open' && $created['description'] === $description, "$mode: create open report and preserve description");
        checkReport($model->hasOpenReport($property, $customer), "$mode: active report found");
        checkReport($model->submit($property, $customer, 'duplicate') === 0, "$mode: duplicate rejected");
        $ids = array_column($model->paginate('pending', 1, 50)['items'], 'id');
        checkReport(in_array($id, array_map('intval', $ids), true), "$mode: pending UI filter finds open report");
        checkReport($model->resolve($id, 'dismissed', false, 'Regression dismissal'), "$mode: admin dismisses report");
        checkReport(!$model->hasOpenReport($property, $customer), "$mode: dismissed report no longer active");
        $id2 = $model->submit($property, $customer, "$mode second report", 'Detailed regression description');
        checkReport($id2 > 0 && $model->getDetail($id2)['description'] === 'Detailed regression description', "$mode: new report retains description");
        checkReport($model->resolve($id2, 'resolved', true, 'Regression resolution'), "$mode: admin resolves report");
        checkReport($db->query('SELECT status FROM properties WHERE id=' . $property)->fetchColumn() === 'hidden', "$mode: resolved violation hides property");
        $ids = array_map('intval', array_column($model->paginate('pending', 1, 50)['items'], 'id'));
        checkReport(!in_array($id, $ids, true) && !in_array($id2, $ids, true), "$mode: pending filter excludes finished reports");
        checkReport($model->submit($property, $customer, 'Hidden property') === 0, "$mode: hidden property cannot be reported");
    }
    echo "Report workflow: $checks checks passed\n";
} finally {
    $db->prepare('DELETE FROM reports WHERE property_id=?')->execute([$property]);
    $db->prepare('DELETE FROM properties WHERE id=?')->execute([$property]);
}
