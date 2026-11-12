<?php
/**
 * Helper xử lý nén ảnh WebP, tự động thu nhỏ (Resize), sinh ảnh Thumbnail và loại bỏ siêu dữ liệu EXIF.
 */
class ImageHelper
{
    /**
     * Nén và resize ảnh bất động sản sang định dạng WebP (tối đa 1600px, chất lượng 82)
     * đồng thời sinh ảnh thumbnail (450x300px) phục vụ tải trang nhanh.
     *
     * @return array{file_name: string, relative_path: string, full_path: string, thumb_path: ?string, thumb_relative_path: ?string}|null
     */
    public static function processPropertyImage(
        string $sourcePath,
        string $uploadDir,
        int $maxWidth = 1600,
        int $quality = 82,
        bool $createThumb = true
    ): ?array {
        if (!is_file($sourcePath)) {
            return null;
        }
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return null;
        }

        $origWidth = (int)$info[0];
        $origHeight = (int)$info[1];
        $mime = $info['mime'] ?? '';

        $hash = bin2hex(random_bytes(16));
        $uploadDirClean = rtrim($uploadDir, '/\\');

        $srcImg = self::createSourceImage($sourcePath, $mime);
        if ($srcImg !== null && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
            if ($origWidth > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = max(1, (int)round($origHeight * ($maxWidth / $origWidth)));
            }

            $dstImg = imagecreatetruecolor($newWidth, $newHeight);
            self::preserveTransparency($dstImg);
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            $useWebp = function_exists('imagewebp');
            $ext = $useWebp ? 'webp' : 'jpg';
            $fileName = $hash . '.' . $ext;
            $targetPath = $uploadDirClean . DIRECTORY_SEPARATOR . $fileName;

            $saved = $useWebp
                ? @imagewebp($dstImg, $targetPath, $quality)
                : @imagejpeg($dstImg, $targetPath, $quality);
            imagedestroy($dstImg);

            if (!$saved || !is_file($targetPath)) {
                imagedestroy($srcImg);
                return null;
            }

            $thumbPath = null;
            $thumbRelPath = null;
            if ($createThumb) {
                $thumbFileName = 'thumb_' . $fileName;
                $thumbTarget = $uploadDirClean . DIRECTORY_SEPARATOR . $thumbFileName;
                if (self::generateThumbnail($srcImg, $origWidth, $origHeight, $thumbTarget, 450, 300, $quality, $useWebp)) {
                    $thumbPath = $thumbTarget;
                    $thumbRelPath = 'uploads/properties/' . $thumbFileName;
                }
            }

            imagedestroy($srcImg);
            return [
                'file_name'           => $fileName,
                'relative_path'       => 'uploads/properties/' . $fileName,
                'full_path'           => $targetPath,
                'thumb_path'          => $thumbPath,
                'thumb_relative_path' => $thumbRelPath,
            ];
        }

        // Fallback nếu máy chủ không bật GD đầy đủ
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ext = $extMap[$mime] ?? 'jpg';
        $fileName = $hash . '.' . $ext;
        $targetPath = $uploadDirClean . DIRECTORY_SEPARATOR . $fileName;
        $moved = is_uploaded_file($sourcePath)
            ? @move_uploaded_file($sourcePath, $targetPath)
            : @copy($sourcePath, $targetPath);
        if (!$moved) {
            return null;
        }

        return [
            'file_name'           => $fileName,
            'relative_path'       => 'uploads/properties/' . $fileName,
            'full_path'           => $targetPath,
            'thumb_path'          => null,
            'thumb_relative_path' => null,
        ];
    }

    /**
     * Xử lý ảnh đại diện (Avatar) của người dùng: resize tối đa 400x400px, nén sang WebP chất lượng 85.
     *
     * @return array{file_name: string, relative_path: string, full_path: string}|null
     */
    public static function processAvatarImage(
        string $sourcePath,
        string $avatarDir,
        int $maxSize = 400,
        int $quality = 85
    ): ?array {
        if (!is_file($sourcePath)) {
            return null;
        }
        if (!is_dir($avatarDir) && !mkdir($avatarDir, 0755, true) && !is_dir($avatarDir)) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return null;
        }

        $origWidth = (int)$info[0];
        $origHeight = (int)$info[1];
        $mime = $info['mime'] ?? '';
        $hash = bin2hex(random_bytes(16));
        $avatarDirClean = rtrim($avatarDir, '/\\');

        $srcImg = self::createSourceImage($sourcePath, $mime);
        if ($srcImg !== null && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
            $side = min($origWidth, $origHeight);
            $srcX = (int)floor(($origWidth - $side) / 2);
            $srcY = (int)floor(($origHeight - $side) / 2);
            $targetSide = min($maxSize, $side);

            $dstImg = imagecreatetruecolor($targetSide, $targetSide);
            self::preserveTransparency($dstImg);
            imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetSide, $targetSide, $side, $side);

            $useWebp = function_exists('imagewebp');
            $ext = $useWebp ? 'webp' : 'jpg';
            $fileName = 'avatar_' . $hash . '.' . $ext;
            $targetPath = $avatarDirClean . DIRECTORY_SEPARATOR . $fileName;

            $saved = $useWebp
                ? @imagewebp($dstImg, $targetPath, $quality)
                : @imagejpeg($dstImg, $targetPath, $quality);

            imagedestroy($dstImg);
            imagedestroy($srcImg);

            if ($saved && is_file($targetPath)) {
                return [
                    'file_name'     => $fileName,
                    'relative_path' => 'uploads/avatars/' . $fileName,
                    'full_path'     => $targetPath,
                ];
            }
        }

        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ext = $extMap[$mime] ?? 'jpg';
        $fileName = 'avatar_' . $hash . '.' . $ext;
        $targetPath = $avatarDirClean . DIRECTORY_SEPARATOR . $fileName;
        $moved = is_uploaded_file($sourcePath)
            ? @move_uploaded_file($sourcePath, $targetPath)
            : @copy($sourcePath, $targetPath);
        if (!$moved) {
            return null;
        }

        return [
            'file_name'     => $fileName,
            'relative_path' => 'uploads/avatars/' . $fileName,
            'full_path'     => $targetPath,
        ];
    }

    private static function createSourceImage(string $sourcePath, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? (@imagecreatefromjpeg($sourcePath) ?: null) : null,
            'image/png'  => function_exists('imagecreatefrompng') ? (@imagecreatefrompng($sourcePath) ?: null) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($sourcePath) ?: null) : null,
            default      => null,
        };
    }

    private static function preserveTransparency($img): void
    {
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($img);
        }
        if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
            @imagealphablending($img, false);
            @imagesavealpha($img, true);
        }
    }

    private static function generateThumbnail(
        $srcImg,
        int $origWidth,
        int $origHeight,
        string $thumbTarget,
        int $thumbWidth = 450,
        int $thumbHeight = 300,
        int $quality = 82,
        bool $useWebp = true
    ): bool {
        $srcRatio = $origWidth / max(1, $origHeight);
        $dstRatio = $thumbWidth / max(1, $thumbHeight);

        if ($srcRatio > $dstRatio) {
            $cropHeight = $origHeight;
            $cropWidth = (int)round($origHeight * $dstRatio);
            $srcX = (int)floor(($origWidth - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $origWidth;
            $cropHeight = (int)round($origWidth / $dstRatio);
            $srcX = 0;
            $srcY = (int)floor(($origHeight - $cropHeight) / 2);
        }

        $thumbImg = imagecreatetruecolor($thumbWidth, $thumbHeight);
        self::preserveTransparency($thumbImg);
        imagecopyresampled(
            $thumbImg,
            $srcImg,
            0,
            0,
            $srcX,
            $srcY,
            $thumbWidth,
            $thumbHeight,
            max(1, $cropWidth),
            max(1, $cropHeight)
        );

        $ok = $useWebp
            ? @imagewebp($thumbImg, $thumbTarget, $quality)
            : @imagejpeg($thumbImg, $thumbTarget, $quality);
        imagedestroy($thumbImg);
        return (bool)$ok && is_file($thumbTarget);
    }
}
