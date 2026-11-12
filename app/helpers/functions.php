<?php
// Hàm tiện ích dùng chung trong view/controller.

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return $default;
        $value = $value[$part];
    }
    return $value;
}

function base_url(string $path = ''): string
{
    $base = config('base_url');
    if ($base === '' || $base === null) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    } else {
        $base = rtrim((string)$base, '/');
    }
    return $base . '/' . ltrim($path, '/');
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        return base_url($path);
    }
}

function request_scheme(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($proto === 'https' || $proto === 'http') {
            return $proto;
        }
    }
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return 'https';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && in_array(strtolower(trim((string)$_SERVER['HTTP_X_FORWARDED_SSL'])), ['on', '1', 'true'], true)) {
        return 'https';
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return 'https';
    }
    return 'http';
}

function request_host(): string
{
    $candidates = [];
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $candidates[] = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        $candidates[] = trim((string)$_SERVER['HTTP_HOST']);
    }
    foreach ($candidates as $host) {
        if ($host !== '' && preg_match('/^[a-zA-Z0-9.\-]+(?::\d+)?$/', $host)) {
            return $host;
        }
    }
    return 'localhost';
}

function full_url(string $path = ''): string
{
    $target = base_url($path);
    if (preg_match('#^https?://#i', $target)) {
        return $target;
    }
    return request_scheme() . '://' . request_host() . '/' . ltrim($target, '/');
}

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

/** Encode data embedded in a <script> block without allowing </script> breakout. */
function json_for_script($value): string
{
    return json_encode(
        $value,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    );
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }

function flash(string $type, string $message): void { $_SESSION['flash'][] = compact('type', 'message'); }
function pull_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function old(string $key, string $default = ''): string { return e($_SESSION['old'][$key] ?? $default); }

function format_price($price): string
{
    $p = (float)$price;
    if ($p >= 1e9) return rtrim(rtrim(number_format($p / 1e9, 2, ',', '.'), '0'), ',') . ' tỷ';
    if ($p >= 1e6) return rtrim(rtrim(number_format($p / 1e6, 1, ',', '.'), '0'), ',') . ' triệu';
    return number_format($p, 0, ',', '.') . ' đ';
}

/**
 * Trả về URL ảnh đại diện BĐS hoặc ảnh mặc định nếu không có.
 */
function image_url(string $path): string
{
    if (preg_match('~^https://res\.cloudinary\.com/[a-zA-Z0-9_-]+/image/upload/~', $path)) return $path;
    return base_url($path);
}

function property_avatar_url(?string $path): string
{
    if (!empty($path)) {
        return image_url($path);
    }
    return base_url('uploads/sample-property-01.jpg');
}

/**
 * Hiển thị thời gian tương đối thân thiện (Vừa xong, 5 phút trước, 2 giờ trước, ...).
 */
function time_ago(?string $datetime): string
{
    if (empty($datetime)) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return $datetime;
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Vừa xong';
    }
    if ($diff < 3600) {
        return (int)floor($diff / 60) . ' phút trước';
    }
    if ($diff < 86400) {
        return (int)floor($diff / 3600) . ' giờ trước';
    }
    if ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days === 1 ? 'Hôm qua lúc ' . date('H:i', $ts) : $days . ' ngày trước';
    }
    return date('H:i d/m/Y', $ts);
}

/**
 * Chuyển chuỗi tiếng Việt có dấu thành URL slug không dấu chuẩn SEO.
 */
function slugify(?string $text): string
{
    if (empty($text)) return '';
    $text = (string)$text;
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D' => 'Đ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
    ];
    foreach ($unicode as $nonAccent => $accent) {
        $text = preg_replace("/($accent)/u", $nonAccent, $text);
    }
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9\-\s]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', (string)$text);
    return trim((string)$text, '-');
}


