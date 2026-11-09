<?php
/** Lưu ảnh ở máy cục bộ hoặc Cloudinary, theo cấu hình của môi trường. */
class ImageStorage
{
    public function store(string $temporaryPath, string $extension): string
    {
        if (!in_array($extension, ['jpg', 'png', 'webp'], true)) throw new InvalidArgumentException('Invalid image extension');
        $name = bin2hex(random_bytes(16));
        if ($this->driver() === 'cloudinary') {
            $publicId = 'bds/properties/' . $name;
            $result = $this->cloudinaryRequest('upload', ['public_id' => $publicId], $temporaryPath);
            $url = (string)($result['secure_url'] ?? '');
            if ($this->cloudinaryId($url) !== $publicId || strlen($url) > 255) {
                try { $this->cloudinaryRequest('destroy', ['public_id' => $publicId]); } catch (Throwable $ignored) { error_log('Unable to clean up Cloudinary image'); }
                throw new RuntimeException('Invalid Cloudinary upload response');
            }
            return $url;
        }
        $directory = (string)config('upload_dir');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Unable to create upload directory');
        $filename = $name . '.' . $extension;
        if (!move_uploaded_file($temporaryPath, rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename)) throw new RuntimeException('Unable to save uploaded image');
        return 'uploads/properties/' . $filename;
    }

    public function remove(string $path): void
    {
        if (str_starts_with($path, 'https://')) {
            $id = $this->cloudinaryId($path);
            if ($id !== null) $this->cloudinaryRequest('destroy', ['public_id' => $id]);
            return;
        }
        if (!preg_match('~^uploads/properties/([a-f0-9]{32}\.(?:jpg|png|webp))$~', $path, $matches)) return;
        $file = rtrim((string)config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . $matches[1];
        if (is_file($file) && !unlink($file)) throw new RuntimeException('Unable to remove image');
    }

    private function driver(): string
    {
        $driver = (string)config('image_storage');
        if (!in_array($driver, ['local', 'cloudinary'], true)) throw new RuntimeException('Invalid image storage configuration');
        return $driver;
    }

    private function cloudinaryId(string $url): ?string
    {
        $cloud = (string)config('cloudinary.cloud_name');
        if ($cloud === '') return null;
        $pattern = '~^https://res\.cloudinary\.com/' . preg_quote($cloud, '~') . '/image/upload/(?:v[0-9]+/)?(bds/properties/[a-f0-9]{32})\.(?:jpg|png|webp)$~';
        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }

    private function cloudinaryRequest(string $action, array $params, ?string $file = null): array
    {
        $cloud = (string)config('cloudinary.cloud_name');
        $key = (string)config('cloudinary.api_key');
        $secret = (string)config('cloudinary.api_secret');
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $cloud) || $key === '' || $secret === '') throw new RuntimeException('Cloudinary is not configured');
        $params['timestamp'] = time();
        ksort($params);
        $pairs = [];
        foreach ($params as $name => $value) $pairs[] = $name . '=' . $value;
        $params['signature'] = sha1(implode('&', $pairs) . $secret);
        $params['api_key'] = $key;
        if ($file !== null) $params['file'] = new CURLFile($file);
        $curl = curl_init('https://api.cloudinary.com/v1_1/' . rawurlencode($cloud) . '/image/' . $action);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 45]);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        $data = is_string($response) ? json_decode($response, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($data)) throw new RuntimeException('Cloudinary request failed');
        if ($action === 'destroy' && !in_array($data['result'] ?? '', ['ok', 'not found'], true)) throw new RuntimeException('Cloudinary image deletion failed');
        return $data;
    }
}
