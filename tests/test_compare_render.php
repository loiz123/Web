<?php
/** View regression: sale and rent prices have different units. No database needed. */
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
$GLOBALS['config'] = ['base_url' => ''];
require APP . '/helpers/functions.php';
$base = [
    'id' => 1, 'title' => 'Comparison fixture', 'price' => 15000000, 'area' => 50,
    'listing_type' => 'rent', 'ward_name' => 'Phường An Khánh',
    'district_name' => 'Phường An Khánh', 'province_name' => 'TP. Hồ Chí Minh',
];
function renderCompare(array $properties): string {
    $allAmenitiesMap = [];
    ob_start();
    require APP . '/views/property/compare.php';
    return ob_get_clean();
}
function checkCompare(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
$mixed = renderCompare([$base, array_replace($base, ['id' => 2, 'price' => 1800000000, 'listing_type' => 'sale'])]);
checkCompare(!str_contains($mixed, 'Giá tốt nhất'), 'Mixed sale/rent listings do not rank prices against one another');
checkCompare(str_contains($mixed, '15 triệu/tháng'), 'Rent price includes monthly unit');
checkCompare(str_contains($mixed, 'tr/m²/tháng'), 'Rent price per area includes monthly unit');
checkCompare(!str_contains($mixed, 'Phường An Khánh, Phường An Khánh'), 'Location does not repeat ward as district');
$sale = renderCompare([array_replace($base, ['listing_type' => 'sale']), array_replace($base, ['id' => 2, 'price' => 1800000000, 'listing_type' => 'sale'])]);
checkCompare(substr_count($sale, 'Giá tốt nhất') === 1, 'Comparable sale listings still highlight lowest price');
checkCompare(!str_contains($sale, '/tháng'), 'Sale prices do not show monthly units');
$rent = renderCompare([$base, array_replace($base, ['id' => 2, 'price' => 18000000])]);
checkCompare(substr_count($rent, 'Giá tốt nhất') === 1, 'Comparable rental listings still highlight lowest price');
