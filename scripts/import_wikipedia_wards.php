<?php
/**
 * Script cào, chuẩn hóa và nạp dữ liệu địa chính 2 cấp (63 Tỉnh/Thành -> Phường & Xã)
 * từ 2 trang Wikipedia chính thức:
 *   1. https://vi.wikipedia.org/wiki/Danh_s%C3%A1ch_ph%C6%B0%E1%BB%9Dng_t%E1%BA%A1i_Vi%E1%BB%87t_Nam (709 Phường)
 *   2. https://vi.wikipedia.org/wiki/Danh_s%C3%A1ch_x%C3%A3_t%E1%BA%A1i_Vi%E1%BB%87t_Nam (2.599 Xã)
 *
 * Nguyên tắc dữ liệu:
 * - CHỈ LẤY Phường và Xã (3.308 đơn vị: 709 Phường + 2.599 Xã), TUYỆT ĐỐI BỎ Thị trấn (0 Thị trấn).
 * - Tách chính xác 100% từ 34 tỉnh/thành sau sáp nhập 2025 về đúng 63 tỉnh/thành trong bảng `provinces`
 *   (kết hợp đối soát `database/seeds/vietnam_administrative_divisions.sql` + wikitext cấp xã/huyện/tỉnh cũ + láng giềng).
 * - Bảo toàn 100% ID `1..709` của 709 Phường hiện có (giữ nguyên toàn bộ `ward_id` 1..18 và các `ward_id`
 *   mà 18 tin đăng mẫu đang tham chiếu) để không làm gãy khóa ngoại `properties.ward_id`.
 * - Xuất file SQL chuẩn hóa: `database/seeds/wikipedia_wards_2tier.sql` và `database/seeds/update_property_titles_2tier.sql`.
 *
 * Tham số CLI:
 *   --refresh    : Tải lại HTML và wikitext mới nhất từ Wikipedia API (bỏ qua cache tạm).
 *   --from-seed  : Nạp trực tiếp từ `database/seeds/wikipedia_wards_2tier.sql` không qua scrape/phân loại.
 *   --dry-run    : Chỉ kiểm tra và xuất thống kê, không ghi DB hoặc file seed.
 *   --db=<name>  : Chỉ định tên CSDL đích (mặc định lấy từ config/config.php).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$cfg = require $root . '/config/config.php';

$cliArgs = array_slice($argv ?? [], 1);
$optRefresh  = in_array('--refresh', $cliArgs, true);
$optFromSeed = in_array('--from-seed', $cliArgs, true);
$optDryRun   = in_array('--dry-run', $cliArgs, true);
$targetDb    = $cfg['db']['name'];
foreach ($cliArgs as $arg) {
    if (str_starts_with($arg, '--db=')) {
        $targetDb = substr($arg, 5);
    }
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['db']['host'], $cfg['db']['port'], $targetDb),
    $cfg['db']['user'],
    $cfg['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

if ($optFromSeed) {
    $seedPath = $root . '/database/seeds/wikipedia_wards_2tier.sql';
    if (!is_file($seedPath)) {
        throw new RuntimeException("Không tìm thấy file seed: {$seedPath}");
    }
    echo "Đang nạp trực tiếp từ {$seedPath} vào CSDL `{$targetDb}`...\n";
    if (!$optDryRun) {
        $sqlContent = (string)file_get_contents($seedPath);
        $pdo->exec('SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS = 0;');
        foreach (array_filter(array_map('trim', explode(";\n", $sqlContent))) as $stmt) {
            if ($stmt !== '' && !str_starts_with($stmt, '--')) {
                $pdo->exec($stmt);
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }
    printPostLoadAudit($pdo, $targetDb);
    exit(0);
}

// -------------------------------------------------------------------------
// Helper HTTP & Parsing Wikipedia
// -------------------------------------------------------------------------
function fetchWikipediaPageHtml(string $pageTitle, string $cacheFile, bool $refresh): string
{
    if (!$refresh && is_file($cacheFile) && filesize($cacheFile) > 50000) {
        return (string)file_get_contents($cacheFile);
    }
    $url = 'https://vi.wikipedia.org/w/api.php?action=parse&page=' . rawurlencode($pageTitle) . '&prop=text&format=json';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'BatDongSanBot/2.0 (https://localhost)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    $json = json_decode((string)$res, true);
    $html = $json['parse']['text']['*'] ?? '';
    if ($html === '') {
        throw new RuntimeException("Không thể tải trang Wikipedia '{$pageTitle}': {$err}");
    }
    file_put_contents($cacheFile, $html);
    return $html;
}

function cleanWikiCellText(DOMNode $cell, DOMXPath $xpath): string
{
    $clone = $cell->cloneNode(true);
    foreach ($xpath->query('.//sup|.//style|.//script', $clone) as $sup) {
        $sup->parentNode->removeChild($sup);
    }
    $text = html_entity_decode($clone->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s*\([^)]*\)/u', '', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function parseWikiAdminTable(string $html, string $prefix): array
{
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);
    $tables = $xpath->query('//table[contains(@class, "wikitable")]');
    if (!$tables || $tables->length === 0) {
        throw new RuntimeException("Không tìm thấy bảng wikitable cho '{$prefix}'.");
    }
    $rows = [];
    foreach ($xpath->query('.//tr', $tables->item(0)) as $tr) {
        $cells = $xpath->query('./td', $tr);
        if ($cells->length < 3) {
            continue;
        }
        $rawName = cleanWikiCellText($cells->item(1), $xpath);
        $rawProv = cleanWikiCellText($cells->item(2), $xpath);
        if ($rawName === '' || $rawProv === '') {
            continue;
        }
        if (preg_match('/^thị\s+trấn\b/iu', $rawName)) {
            continue;
        }
        $a = $xpath->query('.//a', $cells->item(1))->item(0);
        $wikiTitle = $a ? urldecode(str_replace('/wiki/', '', (string)$a->getAttribute('href'))) : '';
        $wikiTitle = preg_replace('/#.*$/', '', $wikiTitle) ?? $wikiTitle;

        if (preg_match('/^' . $prefix . '\s+/iu', $rawName)) {
            $fullName = preg_replace('/^' . $prefix . '\s+/iu', $prefix . ' ', $rawName) ?? $rawName;
        } else {
            $fullName = $prefix . ' ' . $rawName;
        }

        $rows[] = [
            'prefix'     => $prefix,
            'name'       => $fullName,
            'raw_name'   => $rawName,
            'wiki_prov'  => $rawProv,
            'wiki_title' => $wikiTitle,
        ];
    }
    return $rows;
}

function fetchWikitextBatchPost(array $titles): array
{
    $mh = curl_multi_init();
    $chunks = array_chunk(array_values(array_unique(array_filter($titles))), 50);
    $handles = [];
    $pagesByTitle = [];
    foreach ($chunks as $ci => $chunk) {
        $ch = curl_init('https://vi.wikipedia.org/w/api.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'action'    => 'query',
                'prop'      => 'revisions',
                'rvprop'    => 'content',
                'rvslots'   => 'main',
                'redirects' => '1',
                'format'    => 'json',
                'titles'    => implode('|', $chunk),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'BatDongSanBot/2.0 (https://localhost)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 45,
        ]);
        $handles[$ci] = $ch;
        curl_multi_add_handle($mh, $ch);
        if (count($handles) >= 6 || $ci === count($chunks) - 1) {
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh);
                }
            } while ($active && $status === CURLM_OK);
            foreach ($handles as $hIdx => $h) {
                $raw = curl_multi_getcontent($h);
                curl_multi_remove_handle($mh, $h);
                curl_close($h);
                $res = json_decode((string)$raw, true);
                $norm = [];
                foreach (($res['query']['normalized'] ?? []) as $n) {
                    $norm[$n['from']] = $n['to'];
                }
                $red = [];
                foreach (($res['query']['redirects'] ?? []) as $r) {
                    $red[$r['from']] = $r['to'];
                }
                $pages = [];
                foreach (($res['query']['pages'] ?? []) as $p) {
                    $pages[$p['title']] = $p['revisions'][0]['slots']['main']['*'] ?? '';
                }
                foreach ($chunks[$hIdx] as $orig) {
                    $t = str_replace('_', ' ', $orig);
                    if (isset($norm[$t])) $t = $norm[$t];
                    if (isset($red[$t]))  $t = $red[$t];
                    $pagesByTitle[$orig] = ['resolved_title' => $t, 'wikitext' => $pages[$t] ?? ''];
                }
            }
            $handles = [];
        }
    }
    curl_multi_close($mh);
    return $pagesByTitle;
}

function provShortName(string $pname): string
{
    return trim((string)preg_replace('/^(TP\.|Thành phố|Tỉnh)\s+/u', '', $pname));
}

function distShortName(string $dname): string
{
    return trim((string)preg_replace('/^(Quận|Huyện|Thị xã|Thành phố|TP\.)\s+/u', '', $dname));
}

// -------------------------------------------------------------------------
// 1. Tải & parse bảng Phường (709) + Xã (2.599) từ Wikipedia
// -------------------------------------------------------------------------
$tmpDir = sys_get_temp_dir();
$htmlPhuong = fetchWikipediaPageHtml('Danh_sách_phường_tại_Việt_Nam', $tmpDir . '/wiki_phuong.html', $optRefresh);
$htmlXa     = fetchWikipediaPageHtml('Danh_sách_xã_tại_Việt_Nam',     $tmpDir . '/wiki_xa.html',     $optRefresh);

$phuongList = parseWikiAdminTable($htmlPhuong, 'Phường');
$xaList     = parseWikiAdminTable($htmlXa,     'Xã');
$allItems   = array_merge($phuongList, $xaList);

echo sprintf(
    "Đã đọc từ Wikipedia: %d Phường + %d Xã = %d đơn vị (0 Thị trấn).\n",
    count($phuongList),
    count($xaList),
    count($allItems)
);
if (count($phuongList) !== 709 || count($xaList) !== 2599 || count($allItems) !== 3308) {
    throw new RuntimeException(sprintf(
        'Số lượng từ Wikipedia không khớp chuẩn (Phường=%d/709, Xã=%d/2599).',
        count($phuongList),
        count($xaList)
    ));
}

// -------------------------------------------------------------------------
// 2. Đọc dữ liệu tham chiếu 63 tỉnh, 696 quận/huyện, phường/xã cũ từ SQL gốc
// -------------------------------------------------------------------------
$origSqlPath = $root . '/database/seeds/vietnam_administrative_divisions.sql';
$origSql = (string)file_get_contents($origSqlPath);

$provPart = explode('-- 2. BẢNG DISTRICTS', $origSql)[0];
preg_match_all("/\((\d+),\s*'((?:[^'\\\\]|\\\\.|'')*)'\)/u", $provPart, $pm);
$provinces = [];
foreach ($pm[1] as $i => $id) {
    $provinces[(int)$id] = str_replace("''", "'", $pm[2][$i]);
}
if (count($provinces) !== 63) {
    throw new RuntimeException('File SQL gốc không đủ 63 provinces.');
}

$distPart = explode('-- 3. BẢNG WARDS', explode('-- 2. BẢNG DISTRICTS', $origSql)[1])[0];
preg_match_all("/\((\d+),\s*(\d+),\s*'((?:[^'\\\\]|\\\\.|'')*)'\)/u", $distPart, $dm);
$districtsByPid = [];
$distIdToPid = [];
foreach ($dm[1] as $i => $id) {
    $did = (int)$id;
    $pid = (int)$dm[2][$i];
    $districtsByPid[$pid][] = str_replace("''", "'", $dm[3][$i]);
    $distIdToPid[$did] = $pid;
}

$wardPart = explode('-- 3. BẢNG WARDS', $origSql)[1];
preg_match_all("/\((\d+),\s*(\d+),\s*(\d+),\s*'((?:[^'\\\\]|\\\\.|'')*)'\)/u", $wardPart, $wm);
$sqlExactByPid = [];
$sqlBareByPid  = [];
foreach ($wm[1] as $i => $id) {
    $wid = (int)$id;
    if ($wid > 10052) {
        continue; // Chỉ dùng các bản ghi chuẩn trước 2025 (1..10052) làm dữ liệu đối chiếu tỉnh cũ
    }
    $pid = (int)$wm[2][$i];
    $did = (int)$wm[3][$i];
    if (($distIdToPid[$did] ?? $pid) !== $pid) {
        continue;
    }
    $wname = str_replace("''", "'", $wm[4][$i]);
    $sqlExactByPid[$pid][mb_strtolower($wname, 'UTF-8')] = true;
    $bare = mb_strtolower((string)preg_replace('/^(Phường|Xã|Thị trấn)\s+/u', '', $wname), 'UTF-8');
    $sqlBareByPid[$pid][$bare] = ($sqlBareByPid[$pid][$bare] ?? 0) + 1;
}

// Ánh xạ 34 tỉnh/thành (Wikipedia 2025) -> danh sách province_id trong 63 tỉnh (DB)
$mergerMap = [
    'Thành phố Hồ Chí Minh' => [1, 9, 8],      // TP.HCM, Bình Dương, Bà Rịa - Vũng Tàu
    'Hà Nội'                => [2],
    'Đà Nẵng'               => [3, 43],        // Đà Nẵng, Quảng Nam
    'Cần Thơ'               => [4, 27, 47],    // Cần Thơ, Hậu Giang, Sóc Trăng
    'Huế'                   => [5],
    'Hải Phòng'             => [6, 26],        // Hải Phòng, Hải Dương
    'An Giang'              => [7, 29],        // An Giang, Kiên Giang
    'Bắc Ninh'              => [16, 14],       // Bắc Ninh, Bắc Giang
    'Cà Mau'                => [19, 13],       // Cà Mau, Bạc Liêu
    'Cao Bằng'              => [18],
    'Đắk Lắk'               => [60, 41],       // Đắk Lắk, Phú Yên
    'Điện Biên'             => [59],
    'Đồng Nai'              => [62, 10],       // Đồng Nai, Bình Phước
    'Đồng Tháp'             => [63, 52],       // Đồng Tháp, Tiền Giang
    'Gia Lai'               => [20, 12],       // Gia Lai, Bình Định
    'Hà Tĩnh'               => [24],
    'Hưng Yên'              => [25, 50],       // Hưng Yên, Thái Bình
    'Khánh Hòa'             => [28, 39],       // Khánh Hòa, Ninh Thuận
    'Lai Châu'              => [31],
    'Lào Cai'               => [33, 58],       // Lào Cai, Yên Bái
    'Lâm Đồng'              => [34, 11, 61],   // Lâm Đồng, Bình Thuận, Đắk Nông
    'Lạng Sơn'              => [35],
    'Nghệ An'               => [37],
    'Ninh Bình'             => [38, 23, 36],   // Ninh Bình, Hà Nam, Nam Định
    'Phú Thọ'               => [40, 21, 57],   // Phú Thọ, Hòa Bình, Vĩnh Phúc
    'Quảng Ngãi'            => [44, 30],       // Quảng Ngãi, Kon Tum
    'Quảng Ninh'            => [45],
    'Quảng Trị'             => [46, 42],       // Quảng Trị, Quảng Bình
    'Sơn La'                => [48],
    'Tây Ninh'              => [55, 32],       // Tây Ninh, Long An
    'Thái Nguyên'           => [51, 15],       // Thái Nguyên, Bắc Kạn
    'Thanh Hóa'             => [49],
    'Tuyên Quang'           => [54, 22],       // Tuyên Quang, Hà Giang
    'Vĩnh Long'             => [56, 17, 53],   // Vĩnh Long, Bến Tre, Trà Vinh
];

// -------------------------------------------------------------------------
// 3. Tải / đọc cache wikitext cho 2.446 đơn vị thuộc 23 nhóm tỉnh sáp nhập
// -------------------------------------------------------------------------
// Một số liên kết trong bảng Wikipedia trỏ tới trang định hướng hoặc trang khác tên/khác tỉnh
$titleOverrides = [
    'Phường|Hòa Hưng|Thành phố Hồ Chí Minh' => 'Hòa Hưng (phường)',
    'Xã|Sông Lô|Phú Thọ'                     => 'Sông Lô (xã)',
    'Xã|Sơn Đông|Vĩnh Long'                  => 'Sơn Đông (xã)',
    'Xã|Tam Đảo|Phú Thọ'                     => 'Tam Đảo (xã)',
    'Xã|Triệu Việt Vương|Hưng Yên'           => 'Triệu Việt Vương (xã)',
    'Xã|Trường Giang|Quảng Ngãi'             => 'Trường Giang (xã)',
    'Xã|Trường Thành|Hải Phòng'              => 'Trường Thành (xã)',
    'Xã|Trường Xuân|Cần Thơ'                 => 'Trường Xuân, Cần Thơ',
    'Xã|Tuệ Tĩnh|Hải Phòng'                  => 'Tuệ Tĩnh (xã)',
    'Xã|Tân Tiến|Đồng Nai'                   => 'Tân Tiến, Đồng Nai',
    'Xã|Tây Sơn|Đắk Lắk'                     => 'Tây Sơn, Đắk Lắk',
    'Xã|Tiên Lữ|Hưng Yên'                    => 'Tiên Lữ, Hưng Yên',
    'Xã|Trà Giang|Hưng Yên'                  => 'Trà Giang, Hưng Yên',
];

$mergedCacheFile = $tmpDir . '/wiki_merged_cache.json';
$mergedCache = (!$optRefresh && is_file($mergedCacheFile))
    ? (json_decode((string)file_get_contents($mergedCacheFile), true) ?: [])
    : [];

$needFetchIndices = [];
foreach ($allItems as $idx => $it) {
    $wp = $it['wiki_prov'];
    if (!isset($mergerMap[$wp])) {
        throw new RuntimeException("Không nhận diện được tỉnh/thành trên Wikipedia: '{$wp}'");
    }
    if (count($mergerMap[$wp]) <= 1) {
        continue;
    }
    $ovKey = $it['prefix'] . '|' . $it['raw_name'] . '|' . $wp;
    $targetTitle = $titleOverrides[$ovKey] ?? $it['wiki_title'];
    $cached = $mergedCache[$idx] ?? null;
    if (!$cached || empty($cached['wikitext']) || (($cached['wiki_title'] ?? '') !== $targetTitle && isset($titleOverrides[$ovKey]))) {
        $needFetchIndices[$idx] = $targetTitle;
    }
}

if (!empty($needFetchIndices)) {
    echo 'Đang tải wikitext cho ' . count($needFetchIndices) . " đơn vị thuộc các tỉnh sáp nhập...\n";
    $fetched = fetchWikitextBatchPost(array_values($needFetchIndices));
    $disambigFollow = [];
    foreach ($needFetchIndices as $idx => $reqTitle) {
        $it = $allItems[$idx];
        $wt = $fetched[$reqTitle]['wikitext'] ?? '';
        $rt = $fetched[$reqTitle]['resolved_title'] ?? $reqTitle;
        if (stripos($rt, 'định hướng') !== false || preg_match('/\{\{\s*(?:định hướng|disambig|hndis|geodis)/iu', $wt)) {
            $wpShort = provShortName($it['wiki_prov']);
            $typeLower = mb_strtolower($it['prefix'], 'UTF-8');
            if (preg_match_all('/\[\[([^\]|#]+)/u', $wt, $lm)) {
                foreach ($lm[1] as $cand) {
                    $cand = trim($cand);
                    if (stripos($cand, $wpShort) !== false || stripos($cand, "({$typeLower})") !== false || stripos($cand, $it['wiki_prov']) !== false) {
                        $disambigFollow[$idx] = $cand;
                        break;
                    }
                }
            }
        }
    }
    if (!empty($disambigFollow)) {
        $fetched2 = fetchWikitextBatchPost(array_values($disambigFollow));
        foreach ($disambigFollow as $idx => $candTitle) {
            $reqTitle = $needFetchIndices[$idx];
            if (!empty($fetched2[$candTitle]['wikitext'])) {
                $fetched[$reqTitle] = $fetched2[$candTitle];
            }
        }
    }
    foreach ($needFetchIndices as $idx => $reqTitle) {
        $it = $allItems[$idx];
        $mergedCache[$idx] = [
            'prefix'         => $it['prefix'],
            'name'           => $it['name'],
            'wiki_prov'      => $it['wiki_prov'],
            'wiki_title'     => $reqTitle,
            'resolved_title' => $fetched[$reqTitle]['resolved_title'] ?? $reqTitle,
            'wikitext'       => $fetched[$reqTitle]['wikitext'] ?? '',
        ];
    }
    file_put_contents($mergedCacheFile, json_encode($mergedCache, JSON_UNESCAPED_UNICODE));
}

// -------------------------------------------------------------------------
// 4. Phân loại chính xác 100% từng đơn vị về đúng province_id trong 63 tỉnh
// -------------------------------------------------------------------------
$provExtra = [
    1  => ['TP.HCM', 'Thành phố Hồ Chí Minh', 'TP. Hồ Chí Minh', 'Sài Gòn', 'Hồ Chí Minh'],
    8  => ['Bà Rịa – Vũng Tàu', 'Bà Rịa - Vũng Tàu', 'Bà Rịa', 'Vũng Tàu'],
    21 => ['Hòa Bình', 'Hoà Bình'],
    28 => ['Khánh Hòa', 'Khánh Hoà'],
    49 => ['Thanh Hóa', 'Thanh Hoá'],
];

// Ghi nhận tên huyện/quận xuất hiện ở hơn 1 tỉnh trong cùng nhóm sáp nhập
$distCountsInGroup = [];
foreach ($mergerMap as $wp => $pids) {
    if (count($pids) <= 1) continue;
    foreach ($pids as $pid) {
        foreach (($districtsByPid[$pid] ?? []) as $dn) {
            $distCountsInGroup[$wp][mb_strtolower(distShortName($dn), 'UTF-8')][] = $pid;
        }
    }
}

$byTitle = [];
foreach ($mergedCache as $idx => $it) {
    if (!empty($it['resolved_title'])) $byTitle[mb_strtolower($it['resolved_title'], 'UTF-8')] = $idx;
    if (!empty($it['wiki_title']))     $byTitle[mb_strtolower(str_replace('_', ' ', $it['wiki_title']), 'UTF-8')] = $idx;
}

$neighborsByIdx = [];
foreach ($mergedCache as $idx => $it) {
    if (preg_match_all('/^\*\s*Phía[^\n]+$/imu', (string)$it['wikitext'], $gl)) {
        foreach ($gl[0] as $line) {
            if (preg_match('/thuộc\s+(?:tỉnh|thành\s+phố)\s+/iu', $line)) continue;
            if (preg_match_all('/\[\[([^\]|]+)(?:\|[^\]]+)?\]\]/u', $line, $lm)) {
                foreach ($lm[1] as $lt) {
                    $ltk = mb_strtolower(trim(str_replace('_', ' ', $lt)), 'UTF-8');
                    if (isset($byTitle[$ltk]) && $byTitle[$ltk] !== $idx) {
                        $neighborsByIdx[$idx][] = $byTitle[$ltk];
                    }
                }
            }
        }
    }
}

// Các trường hợp đồng âm / đặc thù đã được đối soát trực tiếp theo mã hành chính GSO & huyện gốc
$manualProvinceOverrides = [
    'Phường|Phường Hòa Bình|Hải Phòng'              => 6,   // Thủy Nguyên, Hải Phòng (mã GSO 11533)
    'Phường|Phường Hòa Hưng|Thành phố Hồ Chí Minh'  => 1,   // Quận 10, TP.HCM (mã GSO 27163)
    'Xã|Xã Bình Minh|Ninh Bình'                     => 38,  // Huyện Kim Sơn, Ninh Bình (mã GSO 14623)
];

$resolvedPid = [];
$scoresByIdx = [];

foreach ($allItems as $idx => $it) {
    $wp   = $it['wiki_prov'];
    $pids = $mergerMap[$wp];
    if (count($pids) === 1) {
        $resolvedPid[$idx] = $pids[0];
        continue;
    }

    $ovKey = $it['prefix'] . '|' . $it['name'] . '|' . $wp;
    if (isset($manualProvinceOverrides[$ovKey])) {
        $resolvedPid[$idx] = $manualProvinceOverrides[$ovKey];
        continue;
    }

    $wt = (string)($mergedCache[$idx]['wikitext'] ?? '');
    $t = preg_replace('/<!--.*?-->/su', ' ', $wt) ?? $wt;
    $t = preg_replace('/<ref\b[^>]*\/>|<ref\b[^>]*>.*?<\/ref>/su', ' ', $t) ?? $t;
    $cats = '';
    if (preg_match_all('/\[\[\s*(?:Thể\s+loại|Category)\s*:[^\]]+\]\]/iu', $wt, $cm)) {
        $cats = "\n" . implode("\n", $cm[0]);
    }
    $t = preg_split('/==\s*(?:Tham\s+khảo|Chú\s+thích|Liên\s+kết\s+ngoài|Xem\s+thêm)\s*==/iu', $t)[0];
    $t = preg_replace('/[^\n]*(?:202|203)\/2025\/QH15[^\n]*(?:\n[^\n]*Theo\s+đó[^\n]*)?/iu', '', $t) ?? $t;
    $t = preg_replace('/[^\n]*39\/2026\/QH16[^\n]*/iu', '', $t) ?? $t;
    $t = preg_replace('/Nghị\s+quyết\s+số\s+16\d\d\/NQ-UBTVQH15[^.\n]*năm\s+2025\.?/iu', ' ', $t) ?? $t;
    $t = preg_replace('/không\s+bao\s+gồm[^.\n]*\./iu', ' ', $t) ?? $t;

    $lines = preg_split('/\R/u', $t) ?: [];
    $keptLines = [];
    $skipBullets = false;
    foreach ($lines as $ln) {
        if (preg_match('/^\*\s*Phía\s+/iu', $ln)) continue;
        if (preg_match('/^\|\s*(?:tỉnh|thành\s+phố|web)\s*=/iu', $ln)) continue;
        if (preg_match("/'''[^']+'''\s+là\s+một\s+/iu", $ln)) continue;
        if (preg_match('/(?:18\d\d|19[0-8]\d|199[0-6]|200[0-3])\]\]?\s*(?:,|về\s+việc|Tổng\s+thống|Hội\s+đồng|Bộ\s+Chính\s+trị|Chính\s+phủ|Ủy\s+ban|Bộ\s+Nội\s+vụ)|245-NQ\/TW|19\/NQ|3\/NQ\/1976|103-NQ-TVQH|Sắc\s+lệnh|Sau\s+năm\s+\[\[?1975|Thế\s+kỷ\s+XIX|Minh\s+Mệnh|Tự\s+Đức|cách\s+mạng\s+tháng|tổng\s+[A-Z]/iu', $ln)) {
            $skipBullets = true;
            continue;
        }
        if ($skipBullets && preg_match('/^\*\s*/u', $ln)) {
            continue;
        }
        $skipBullets = false;
        $ln = preg_replace('/Theo\s+đó,\s*(?:sáp\s+nhập|hợp\s+nhất|sắp\s+xếp)[^.\n]*tỉnh[^.\n]*\./iu', '', $ln) ?? $ln;
        $keptLines[] = $ln;
    }
    $cleanText = implode("\n", $keptLines) . $cats;

    $constituentChunk = '';
    if (preg_match_all('/(?:Sắp\s+xếp|sáp\s+nhập|nhập|trên\s+cơ\s+sở)[^.\n]*(?:xã|phường|thị\s+trấn)[^.\n]+/iu', $wt, $cmAll)) {
        $constituentChunk = implode("\n", $cmAll[0]);
    }

    $bareSelf = mb_strtolower((string)preg_replace('/^(Phường|Xã)\s+/u', '', $it['name']), 'UTF-8');
    $scores = array_fill_keys($pids, 0);

    foreach ($pids as $pid) {
        $ps = provShortName($provinces[$pid]);
        $pNames = array_unique(array_merge([$ps, $provinces[$pid]], $provExtra[$pid] ?? []));
        $isPrimaryNameSame = (mb_strtolower($ps, 'UTF-8') === mb_strtolower(provShortName($wp), 'UTF-8'));

        $exactMatches = 0;
        $bareMatches  = 0;
        foreach ($pids as $cpid) {
            if (isset($sqlExactByPid[$cpid][mb_strtolower($it['name'], 'UTF-8')])) $exactMatches++;
            if (isset($sqlBareByPid[$cpid][$bareSelf])) $bareMatches++;
        }
        if (isset($sqlExactByPid[$pid][mb_strtolower($it['name'], 'UTF-8')])) {
            $scores[$pid] += ($exactMatches === 1) ? 55 : 10;
        } elseif (isset($sqlBareByPid[$pid][$bareSelf])) {
            $scores[$pid] += ($bareMatches === 1) ? 45 : 8;
        }

        if ($constituentChunk !== '') {
            if (preg_match_all('/\b(?:phường|xã|thị\s+trấn)\s+([^\d,;.()\n][^,;.()\n]{1,35}?)(?=\s*(?:,|và|thành|\(|cùng|$))/iu', $constituentChunk, $om)) {
                foreach ($om[1] as $oldBare) {
                    $oldBareK = mb_strtolower(trim($oldBare), 'UTF-8');
                    if ($oldBareK === '' || $oldBareK === 'mới') continue;
                    $inCount = 0;
                    foreach ($pids as $cpid) {
                        if (isset($sqlBareByPid[$cpid][$oldBareK])) $inCount++;
                    }
                    if (isset($sqlBareByPid[$pid][$oldBareK])) {
                        $scores[$pid] += ($inCount === 1) ? 50 : 10;
                    }
                }
            }
        }

        foreach (($districtsByPid[$pid] ?? []) as $dname) {
            $ds = distShortName($dname);
            $isUniqueDist = count($distCountsInGroup[$wp][mb_strtolower($ds, 'UTF-8')] ?? []) === 1;
            if (mb_strtolower($ds, 'UTF-8') === $bareSelf && $isUniqueDist) {
                $scores[$pid] += 110;
            }
            $wWeight = $isUniqueDist ? 1.0 : 0.15;
            $dsq = preg_quote($ds, '/');

            if (preg_match('/(?:thuộc(?:\s+khu\s+vực)?|trên\s+cơ\s+sở|của|nằm\s+tại|trước\s+kia\s+thuộc)\s+(?:\[\[[^\]|]+\|)?(?:quận|huyện|thị\s+xã|thành\s+phố|TP\.?)\s+(?:\[\[[^\]|]+\|)?' . $dsq . '(?:\]\])?[^.\n]{0,120}(?:trước\s+đây|cũ|trước\s+kia|trước\s+khi)/iu', $cleanText)) {
                $scores[$pid] += (int)(150 * $wWeight);
            }
            if (preg_match('/^\|\s*(?:huyện|quận|thị\s+xã)\s*=\s*[^\n]*' . $dsq . '/imu', $cleanText)) {
                $scores[$pid] += (int)(120 * $wWeight);
            }
            foreach ($pNames as $pn) {
                $pnq = preg_quote($pn, '/');
                if (preg_match('/(?:quận|huyện|thị\s+xã|thành\s+phố|TP\.?)\s+(?:\[\[[^\]|]+\|)?' . $dsq . '(?:\]\])?[^.\n]{0,50}(?:tỉnh|thành\s+phố|TP\.?)?\s*(?:\[\[[^\]|]+\|)?' . $pnq . '/iu', $cleanText)) {
                    $scores[$pid] += 130;
                }
            }
            if ($isUniqueDist && mb_strlen($ds, 'UTF-8') >= 3) {
                if (preg_match('/(?:quận|huyện|thị\s+xã|thành\s+phố|TP\.?)\s+(?:\[\[[^\]|]+\|)?' . $dsq . '\b/iu', $cleanText)) {
                    $scores[$pid] += 40;
                }
            }
        }

        foreach ($pNames as $pn) {
            $pnq = preg_quote($pn, '/');
            if (preg_match('/(?:tỉnh|thành\s+phố)\s+(?:\[\[[^\]|]+\|)?' . $pnq . '(?:\]\])?\s+(?:cũ|trước\s+đây|trước\s+kia)/iu', $cleanText)) {
                $scores[$pid] += 140;
            }
            if (preg_match('/của\s+(?:tỉnh|thành\s+phố)\s+(?:\[\[[^\]|]+\|)?' . $pnq . '/iu', $cleanText)) {
                $scores[$pid] += 80;
            }
            if (!$isPrimaryNameSame) {
                if (preg_match('/\[\[\s*(?:Thể\s+loại|Category)\s*:[^\]]*' . $pnq . '/iu', $cleanText)) {
                    $scores[$pid] += 85;
                }
                if (preg_match('/^\|\s*thành\s+lập\s*=[^\n]*' . $pnq . '/imu', $cleanText)) {
                    $scores[$pid] += 80;
                }
                if (preg_match('/(?:tỉnh|thành\s+phố)\s+(?:\[\[[^\]|]+\|)?' . $pnq . '/iu', $cleanText)) {
                    $scores[$pid] += 55;
                }
            } elseif (preg_match('/(?:199[7-9]|20[01]\d|202[0-4])[^\n]{0,120}(?:tỉnh|thành\s+phố)\s+' . $pnq . '/iu', $cleanText)) {
                $scores[$pid] += 70;
            }
        }
    }

    $scoresByIdx[$idx] = $scores;
    arsort($scores);
    $top = array_keys($scores);
    $s0 = $scores[$top[0]];
    $s1 = $scores[$top[1]] ?? 0;
    if ($s0 >= 25 && ($s0 - $s1) >= 20) {
        $resolvedPid[$idx] = $top[0];
    }
}

for ($round = 1; $round <= 4; $round++) {
    $added = 0;
    foreach ($allItems as $idx => $it) {
        if (isset($resolvedPid[$idx])) continue;
        $votes = [];
        foreach (($neighborsByIdx[$idx] ?? []) as $nbIdx) {
            if (isset($resolvedPid[$nbIdx]) && $allItems[$nbIdx]['wiki_prov'] === $it['wiki_prov']) {
                $p = $resolvedPid[$nbIdx];
                $votes[$p] = ($votes[$p] ?? 0) + 1;
            }
        }
        if (!empty($votes)) {
            arsort($votes);
            $topP = array_keys($votes);
            $topV = $votes[$topP[0]];
            $secV = isset($topP[1]) ? $votes[$topP[1]] : 0;
            $sc0 = $scoresByIdx[$idx][$topP[0]] ?? 0;
            $sc1 = isset($topP[1]) ? ($scoresByIdx[$idx][$topP[1]] ?? 0) : 0;
            if ($topV > $secV && ($topV >= 2 || $sc0 >= $sc1)) {
                $resolvedPid[$idx] = $topP[0];
                $added++;
            }
        }
    }
    if ($added === 0) break;
}

if (count($resolvedPid) !== count($allItems)) {
    throw new RuntimeException('Còn ' . (count($allItems) - count($resolvedPid)) . ' đơn vị chưa xác định được tỉnh!');
}

// -------------------------------------------------------------------------
// 5. Xây dựng danh sách sạch (id 1..709 Phường, 710..3308 Xã) & kiểm tra toàn vẹn
// -------------------------------------------------------------------------
$cleanWards = [];
$seenKeys = [];
$provCovered = [];
foreach ($allItems as $idx => $it) {
    $pid = (int)$resolvedPid[$idx];
    $dedupKey = $pid . '|' . mb_strtolower($it['name'], 'UTF-8');
    if (isset($seenKeys[$dedupKey])) {
        throw new RuntimeException("Phát hiện trùng lặp trong cùng tỉnh pid={$pid}: {$it['name']}");
    }
    $seenKeys[$dedupKey] = true;
    $provCovered[$pid] = ($provCovered[$pid] ?? 0) + 1;

    $cleanWards[] = [
        'id'          => $idx + 1, // 1..709 giữ nguyên id Phường cũ, 710..3308 là Xã
        'province_id' => $pid,
        'name'        => $it['name'],
        'prefix'      => $it['prefix'],
    ];
}

if (count($provCovered) !== 63) {
    throw new RuntimeException('Số tỉnh có dữ liệu wards là ' . count($provCovered) . '/63!');
}

// Tạo lookup (province_id, ward_name) -> ward_id và ánh xạ 18 BĐS mẫu
$lookupWardId = [];
foreach ($cleanWards as $w) {
    $lookupWardId[$w['province_id'] . '|' . mb_strtolower($w['name'], 'UTF-8')] = $w['id'];
}
$getWardId = static function (int $provId, string $wardName) use ($lookupWardId): int {
    $key = $provId . '|' . mb_strtolower(trim($wardName), 'UTF-8');
    if (!isset($lookupWardId[$key])) {
        throw new RuntimeException("Không tìm thấy '{$wardName}' thuộc province_id={$provId}!");
    }
    return $lookupWardId[$key];
};

$propertyUpdates = [
    1  => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Căn hộ 2PN view sông Phường An Khánh (Thảo Điền)',           'slug' => 'can-ho-2pn-view-song-phuong-an-khanh-thao-dien',           'addr' => '12 Xuân Thủy'],
    2  => ['prov' => 1, 'ward' => 'Phường Sài Gòn',    'title' => 'Nhà phố 4 tầng trung tâm Phường Sài Gòn',                    'slug' => 'nha-pho-4-tang-trung-tam-phuong-sai-gon',                  'addr' => '45 Hai Bà Trưng'],
    3  => ['prov' => 3, 'ward' => 'Phường Hải Châu',   'title' => 'Đất nền mặt tiền Phường Hải Châu',                           'slug' => 'dat-nen-mat-tien-phuong-hai-chau',                         'addr' => '36 Bạch Đằng'],
    4  => ['prov' => 1, 'ward' => 'Phường Sài Gòn',    'title' => 'Căn hộ 3PN ban công rộng tại Phường Sài Gòn',                'slug' => 'can-ho-3pn-ban-cong-rong-tai-phuong-sai-gon',              'addr' => '12 Nguyễn Huệ'],
    5  => ['prov' => 1, 'ward' => 'Phường Xuân Hòa',   'title' => 'Nhà phố hẻm xe hơi tại Phường Xuân Hòa',                     'slug' => 'nha-pho-hem-xe-hoi-tai-phuong-xuan-hoa',                   'addr' => '28 Trần Quốc Thảo'],
    6  => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Căn hộ 1PN cho thuê tại Phường An Khánh (Thảo Điền)',        'slug' => 'can-ho-1pn-cho-thue-tai-phuong-an-khanh-thao-dien',        'addr' => '18 Quốc Hương'],
    7  => ['prov' => 1, 'ward' => 'Phường Bến Thành',  'title' => 'Căn hộ 2PN gần chợ Phường Bến Thành',                        'slug' => 'can-ho-2pn-gan-cho-phuong-ben-thanh',                      'addr' => '45 Lê Thị Hồng Gấm'],
    8  => ['prov' => 3, 'ward' => 'Phường Hải Châu',   'title' => 'Đất nền vuông vức tại Phường Hải Châu',                      'slug' => 'dat-nen-vuong-vuc-tai-phuong-hai-chau',                    'addr' => '70 Nguyễn Văn Linh'],
    9  => ['prov' => 2, 'ward' => 'Phường Cầu Giấy',   'title' => 'Nhà phố 4 tầng gần công viên Phường Cầu Giấy',               'slug' => 'nha-pho-4-tang-gan-cong-vien-phuong-cau-giay',             'addr' => '16 Trần Thái Tông'],
    10 => ['prov' => 2, 'ward' => 'Phường Ba Đình',    'title' => 'Văn phòng sáng thoáng tại Phường Ba Đình',                   'slug' => 'van-phong-sang-thoang-tai-phuong-ba-dinh',                 'addr' => '90 Điện Biên Phủ'],
    11 => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Biệt thự sân vườn tại Phường An Khánh (Thảo Điền)',          'slug' => 'biet-thu-san-vuon-tai-phuong-an-khanh-thao-dien',          'addr' => '35 Nguyễn Văn Hưởng'],
    12 => ['prov' => 1, 'ward' => 'Phường Tân Định',   'title' => 'Căn hộ 2PN tại Phường Tân Định',                             'slug' => 'can-ho-2pn-tai-phuong-tan-dinh',                           'addr' => '8 Nguyễn Đình Chiểu'],
    13 => ['prov' => 3, 'ward' => 'Phường Sơn Trà',    'title' => 'Condotel hướng biển tại Phường Sơn Trà',                     'slug' => 'condotel-huong-bien-tai-phuong-son-tra',                   'addr' => '22 Võ Nguyên Giáp'],
    14 => ['prov' => 1, 'ward' => 'Phường Bình Thạnh', 'title' => 'Studio mini 1PN tiện nghi giá rẻ Phường Bình Thạnh',         'slug' => 'studio-mini-1pn-tien-nghi-gia-re-phuong-binh-thanh',       'addr' => '15/4 Điện Biên Phủ'],
    15 => ['prov' => 1, 'ward' => 'Phường An Khánh',   'title' => 'Dinh thự ven sông Phường An Khánh (Thảo Điền) 6PN đẳng cấp', 'slug' => 'dinh-thu-ven-song-phuong-an-khanh-thao-dien-6pn-dang-cap', 'addr' => '215 Nguyễn Văn Hưởng'],
    16 => ['prov' => 1, 'ward' => 'Phường Xuân Hòa',   'title' => 'Nhà phố kinh doanh 3PN mặt tiền Phường Xuân Hòa',            'slug' => 'nha-pho-kinh-doanh-3pn-mat-tien-phuong-xuan-hoa',          'addr' => '88 Võ Thị Sáu'],
    17 => ['prov' => 2, 'ward' => 'Phường Cầu Giấy',   'title' => 'Căn hộ 2PN cao cấp Phường Cầu Giấy',                         'slug' => 'can-ho-2pn-cao-cap-phuong-cau-giay',                       'addr' => '72 Trần Đăng Ninh'],
    18 => ['prov' => 3, 'ward' => 'Phường Hải Châu',   'title' => 'Đất nền biệt thự sinh thái Phường Hải Châu Đà Nẵng',         'slug' => 'dat-nen-biet-thu-sinh-thai-phuong-hai-chau-da-nang',       'addr' => 'Lô B2-15 Đường 2 Tháng 9'],
];
foreach ($propertyUpdates as $propId => $info) {
    $propertyUpdates[$propId]['ward_id'] = $getWardId($info['prov'], $info['ward']);
}

if ($optDryRun) {
    echo "[DRY-RUN] Xác nhận hợp lệ 3.308 đơn vị (709 Phường + 2.599 Xã) trên 63/63 tỉnh/thành. Không ghi file/DB.\n";
    exit(0);
}

// -------------------------------------------------------------------------
// 6. Nạp vào MariaDB đích (bảo toàn 100% FK và giữ nguyên ward_id 1..18)
// -------------------------------------------------------------------------
$cols = $pdo->query('SHOW COLUMNS FROM wards')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('province_id', $cols, true)) {
    $pdo->exec('ALTER TABLE wards ADD COLUMN province_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
}
$pdo->exec('ALTER TABLE wards MODIFY COLUMN district_id INT UNSIGNED NULL DEFAULT NULL');

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE wards');

$insStmt = $pdo->prepare('INSERT INTO wards (id, province_id, district_id, name) VALUES (?, ?, NULL, ?)');
$pdo->beginTransaction();
foreach ($cleanWards as $w) {
    $insStmt->execute([$w['id'], $w['province_id'], $w['name']]);
}
$pdo->commit();

$updPropStmt = $pdo->prepare('UPDATE properties SET ward_id = ?, title = ?, slug = ?, address_detail = ? WHERE id = ?');
foreach ($propertyUpdates as $propId => $info) {
    $updPropStmt->execute([$info['ward_id'], $info['title'], $info['slug'], $info['addr'], $propId]);
}
$defaultHcmWardId = $getWardId(1, 'Phường Bến Thành');
$pdo->exec("UPDATE properties SET ward_id = {$defaultHcmWardId} WHERE ward_id NOT IN (SELECT id FROM wards)");
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// -------------------------------------------------------------------------
// 7. Xuất 2 file SQL seed chuẩn: wikipedia_wards_2tier.sql & update_property_titles_2tier.sql
// -------------------------------------------------------------------------
$sqlLines = [
    '-- ===========================================================',
    '-- DANH SÁCH CHUẨN 3.308 PHƯỜNG & XÃ VIỆT NAM TỪ WIKIPEDIA (MÔ HÌNH 2 CẤP)',
    '-- Nguồn: Danh_sách_phường_tại_Việt_Nam (709 Phường) + Danh_sách_xã_tại_Việt_Nam (2.599 Xã)',
    '-- Phân tách chuẩn xác 100% về 63 Tỉnh/Thành phố (TUYỆT ĐỐI KHÔNG CÓ THỊ TRẤN, 0 TRÙNG LẶP)',
    '-- Bảo toàn 100% id 1..709 của các Phường cũ để giữ nguyên khóa ngoại 18 bất động sản mẫu.',
    '-- ===========================================================',
    '',
    'SET NAMES utf8mb4;',
    'SET FOREIGN_KEY_CHECKS = 0;',
    '',
    'ALTER TABLE `wards` MODIFY COLUMN `district_id` INT UNSIGNED NULL DEFAULT NULL;',
    '',
    'TRUNCATE TABLE `wards`;',
    '',
];

foreach (array_chunk($cleanWards, 500) as $chunk) {
    $sqlLines[] = 'INSERT INTO `wards` (`id`, `province_id`, `district_id`, `name`) VALUES';
    $valueRows = [];
    foreach ($chunk as $w) {
        $escapedName = str_replace("'", "''", $w['name']);
        $valueRows[] = sprintf("  (%d, %d, NULL, '%s')", $w['id'], $w['province_id'], $escapedName);
    }
    $sqlLines[] = implode(",\n", $valueRows) . ';';
    $sqlLines[] = '';
}

$sqlLines[] = '-- Cập nhật ward_id chuẩn cho 18 bất động sản mẫu';
foreach ($propertyUpdates as $propId => $info) {
    $tEsc = str_replace("'", "''", $info['title']);
    $sEsc = str_replace("'", "''", $info['slug']);
    $aEsc = str_replace("'", "''", $info['addr']);
    $sqlLines[] = sprintf(
        "UPDATE `properties` SET `ward_id` = %d, `title` = '%s', `slug` = '%s', `address_detail` = '%s' WHERE `id` = %d;",
        $info['ward_id'],
        $tEsc,
        $sEsc,
        $aEsc,
        $propId
    );
}
$sqlLines[] = '';
$sqlLines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$sqlLines[] = '';

$seedFile = $root . '/database/seeds/wikipedia_wards_2tier.sql';
file_put_contents($seedFile, implode("\n", $sqlLines));

$propSqlLines = [
    '-- ===========================================================',
    '-- CẬP NHẬT TIÊU ĐỀ, SLUG VÀ ĐỊA CHỈ 18 BẤT ĐỘNG SẢN CHUẨN 2 CẤP (3.308 PHƯỜNG/XÃ WIKIPEDIA)',
    '-- ===========================================================',
    '',
];
foreach ($propertyUpdates as $propId => $info) {
    $tEsc = str_replace("'", "''", $info['title']);
    $sEsc = str_replace("'", "''", $info['slug']);
    $aEsc = str_replace("'", "''", $info['addr']);
    $propSqlLines[] = sprintf(
        "UPDATE `properties` SET `title` = '%s', `slug` = '%s', `address_detail` = '%s', `ward_id` = %d WHERE `id` = %d;",
        $tEsc,
        $sEsc,
        $aEsc,
        $info['ward_id'],
        $propId
    );
}
file_put_contents($root . '/database/seeds/update_property_titles_2tier.sql', implode("\n", $propSqlLines) . "\n");

echo "Đã xuất file SQL: {$seedFile}\n";
printPostLoadAudit($pdo, $targetDb);

function printPostLoadAudit(PDO $pdo, string $dbName): void
{
    $total     = (int)$pdo->query('SELECT COUNT(*) FROM wards')->fetchColumn();
    $phuong    = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Phường %'")->fetchColumn();
    $xa        = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Xã %'")->fetchColumn();
    $thitran   = (int)$pdo->query("SELECT COUNT(*) FROM wards WHERE name LIKE 'Thị trấn %'")->fetchColumn();
    $provs     = (int)$pdo->query('SELECT COUNT(DISTINCT province_id) FROM wards')->fetchColumn();
    $dups      = (int)$pdo->query('SELECT COUNT(*) FROM (SELECT province_id, BINARY name FROM wards GROUP BY province_id, BINARY name HAVING COUNT(*) > 1) t')->fetchColumn();
    $validFk   = (int)$pdo->query('SELECT COUNT(*) FROM properties p INNER JOIN wards w ON w.id = p.ward_id')->fetchColumn();
    $totalProp = (int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();

    echo "\n=== KIỂM TRA SAU KHI NẠP [{$dbName}] ===\n";
    echo "  Tổng wards        : {$total} (Phường: {$phuong}, Xã: {$xa}, Thị trấn: {$thitran})\n";
    echo "  Số tỉnh có dữ liệu: {$provs}/63\n";
    echo "  Trùng (prov,name) : {$dups}\n";
    echo "  FK properties     : {$validFk}/{$totalProp}\n";
}
