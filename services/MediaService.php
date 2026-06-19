<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Image upload pipeline.
 * Re-encodes every image via GD — strips EXIF/GPS/IPTC by construction (hard law 5).
 * Whitelist: jpeg, png, webp (finfo-verified). SVG rejected (XSS surface).
 * Max edge: 1600px. Max file size: 2M (enforced before GD).
 * Stores under public/media/YYYY/MM/<random-hex>.<ext>
 */
class MediaService
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2M
    private const MAX_EDGE  = 1600;

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Store an uploaded file for a given post.
     *
     * @param array $upload  $_FILES['field'] entry
     * @param int   $postId
     * @return int  new media row id
     * @throws \RuntimeException on any validation or encode failure
     */
    public static function store(array $upload, int $postId): int
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error code: ' . $upload['error']);
        }

        $tmpPath = $upload['tmp_name'];
        $origName = basename($upload['name'] ?? 'upload');

        if (!is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Not an uploaded file.');
        }

        $bytes = filesize($tmpPath);
        if ($bytes === false || $bytes > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds 2M limit.');
        }

        // finfo MIME check (never trust the upload's claim)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new \RuntimeException("Unsupported image type: {$mime}");
        }
        $ext = self::ALLOWED_MIME[$mime];

        // Decode via GD (re-encoding drops EXIF/GPS by construction)
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
        };
        if (!$src) {
            throw new \RuntimeException('GD could not decode image.');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Resize if max edge exceeds 1600
        if ($origW > self::MAX_EDGE || $origH > self::MAX_EDGE) {
            if ($origW >= $origH) {
                $newW = self::MAX_EDGE;
                $newH = (int) round($origH * self::MAX_EDGE / $origW);
            } else {
                $newH = self::MAX_EDGE;
                $newW = (int) round($origW * self::MAX_EDGE / $origH);
            }
            $dst = imagecreatetruecolor($newW, $newH);
            // Preserve transparency for png/webp
            if ($mime !== 'image/jpeg') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
                imagealphablending($dst, true);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
            $finalW = $newW;
            $finalH = $newH;
        } else {
            $finalW = $origW;
            $finalH = $origH;
        }

        // Destination path
        $subDir = date('Y/m');
        $dir = APP_ROOT . '/public/media/' . $subDir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $subDir . '/' . bin2hex(random_bytes(12)) . '.' . $ext;
        $destPath = APP_ROOT . '/public/media/' . $filename;

        // Encode clean (no EXIF)
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($src, $destPath, 85),
            'image/png'  => imagepng($src, $destPath, 6),
            'image/webp' => imagewebp($src, $destPath, 85),
        };
        imagedestroy($src);

        if (!$ok) {
            throw new \RuntimeException('GD could not write output image.');
        }

        $finalBytes = (int) filesize($destPath);

        $id = (int) Database::insert('media', [
            'post_id'       => $postId,
            'filename'      => $filename,
            'original_name' => mb_substr($origName, 0, 160),
            'mime'          => $mime,
            'width'         => $finalW,
            'height'        => $finalH,
            'bytes'         => $finalBytes,
        ]);

        return $id;
    }
}
