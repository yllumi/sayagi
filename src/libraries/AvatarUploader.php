<?php

namespace Yllumi\Sayagi\libraries;

use Webman\Http\UploadFile;

/**
 * Stores user avatars as square WebP files under public/uploads/avatars.
 *
 * Every upload is re-encoded through GD, which strips EXIF metadata and any
 * embedded payload. The public path stored in `mein_users.avatar` is the
 * relative URL consumed directly by `<img src>`.
 */
final class AvatarUploader
{
    public const MAX_BYTES = 3 * 1024 * 1024;

    public const MAX_DIMENSION = 512;

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    private const QUALITY = 85;

    private const PATH_PATTERN = '~^/uploads/avatars/[A-Za-z0-9/_.-]+$~';

    /**
     * Validate, crop to a square and store the avatar.
     *
     * @return string Relative public path, e.g. /uploads/avatars/2026/11/abc.webp
     */
    public function store(UploadFile $file): string
    {
        if (! $file->isValid()) {
            throw new \RuntimeException('Berkas gagal diunggah. Silakan coba lagi.', 422);
        }

        $size = $file->getSize();
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('Ukuran avatar maksimal 3 MB.', 422);
        }

        $source = $file->getPathname();
        if (! in_array($this->detectMime($source), self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Format avatar harus JPG, PNG, WebP, atau AVIF.', 422);
        }

        $contents = @file_get_contents($source);
        $image    = $contents !== false ? @imagecreatefromstring($contents) : false;
        if ($image === false) {
            throw new \RuntimeException('Avatar tidak dapat diproses.', 422);
        }

        if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $image = $this->applyExifOrientation($image, $source);
        $image = $this->squareCrop($image, self::MAX_DIMENSION);

        $relativeDir = '/uploads/avatars/' . date('Y/m');
        $absoluteDir = public_path($relativeDir);
        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            imagedestroy($image);
            throw new \RuntimeException('Folder avatar tidak dapat dibuat.', 500);
        }

        $name         = bin2hex(random_bytes(10));
        $relativePath = $relativeDir . '/' . $name . '.webp';

        if (! imagewebp($image, $absoluteDir . '/' . $name . '.webp', self::QUALITY)) {
            imagedestroy($image);
            throw new \RuntimeException('Avatar gagal disimpan.', 500);
        }

        imagedestroy($image);
        @unlink($source);

        return $relativePath;
    }

    /**
     * Only paths produced by this uploader may be passed back to delete().
     */
    public static function sanitizePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return preg_match(self::PATH_PATTERN, $path) ? $path : null;
    }

    public static function delete(?string $path): bool
    {
        $path = self::sanitizePath($path);
        if ($path === null) {
            return false;
        }

        $root = realpath(public_path('/uploads/avatars'));
        $full = realpath(public_path($path));

        if ($root === false || $full === false || ! str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return false;
        }

        return @unlink($full);
    }

    /**
     * Absolute URL for a stored avatar path.
     */
    public static function url(?string $path): ?string
    {
        $path = self::sanitizePath($path);

        return $path !== null ? site_url($path) : null;
    }

    private function detectMime(string $path): string
    {
        if (! function_exists('finfo_open')) {
            return (string) mime_content_type($path);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return (string) mime_content_type($path);
        }

        $mime = (string) finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime;
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif     = @exif_read_data($path);
        $rotation = match ((int) ($exif['Orientation'] ?? 0)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($rotation === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $rotation, 0);
        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    private function squareCrop(\GdImage $source, int $maxDimension): \GdImage
    {
        $width  = imagesx($source);
        $height = imagesy($source);

        $side = min($width, $height, $maxDimension);
        $srcX = (int) (($width - $side) / 2);
        $srcY = (int) (($height - $side) / 2);

        $target = imagecreatetruecolor($side, $side);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, $srcX, $srcY, $side, $side, $side, $side);
        imagedestroy($source);

        return $target;
    }
}
