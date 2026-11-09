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

