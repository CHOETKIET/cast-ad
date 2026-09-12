<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

class MediaUploadService
{
    private const MAX_UPLOAD_BYTES = 524288000;
    private const IMAGE_DIRECTORY = 'uploads/images';
    private const VIDEO_DIRECTORY = 'uploads/videos';

    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'video/mp4' => 'mp4',
        'video/mpeg4' => 'mp4',
        'application/mp4' => 'mp4',
    ];

    public static function validateAndStore(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new InvalidArgumentException('ข้อมูลอัปโหลดไม่ถูกต้อง');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(self::uploadErrorMessage((int) $file['error']));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException('ไฟล์ที่อัปโหลดว่างเปล่า');
        }

        if ($size > self::MAX_UPLOAD_BYTES) {
            throw new InvalidArgumentException('ไฟล์ที่อัปโหลดมีขนาดใหญ่เกินไป');
        }

        $tmpName = (string) $file['tmp_name'];
        $originalName = basename((string) $file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = self::detectMimeType($tmpName);
        $safeExtension = self::MIME_TO_EXTENSION[$mimeType] ?? null;

        if ($safeExtension === null || !in_array($extension, ['jpg', 'jpeg', 'png', 'mp4'], true)) {
            throw new InvalidArgumentException('รองรับเฉพาะไฟล์ JPG, PNG และ MP4');
        }

        if (($extension === 'jpg' || $extension === 'jpeg') && $mimeType !== 'image/jpeg') {
            throw new InvalidArgumentException('ชนิดไฟล์ JPG ไม่ตรงกับเนื้อหาไฟล์');
        }

        if ($extension === 'png' && $mimeType !== 'image/png') {
            throw new InvalidArgumentException('ชนิดไฟล์ PNG ไม่ตรงกับเนื้อหาไฟล์');
        }

        if ($extension === 'mp4' && $safeExtension !== 'mp4') {
            throw new InvalidArgumentException('ชนิดไฟล์ MP4 ไม่ตรงกับเนื้อหาไฟล์');
        }

        $mediaType = strpos($mimeType, 'image/') === 0 ? 'image' : 'video';
        $relativeDirectory = $mediaType === 'image' ? self::IMAGE_DIRECTORY : self::VIDEO_DIRECTORY;
        $destinationDirectory = public_path($relativeDirectory);
        if (!is_dir($destinationDirectory)) {
            if (!mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
                throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้: ' . $destinationDirectory);
            }
        }

        if (!is_writable($destinationDirectory)) {
            throw new RuntimeException('โฟลเดอร์อัปโหลดไม่สามารถเขียนไฟล์ได้: ' . $destinationDirectory);
        }

        $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $safeExtension;
        $destination = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new RuntimeException('ไม่สามารถบันทึกไฟล์ที่อัปโหลดได้');
        }

        $dimensions = self::imageDimensions($destination, $mediaType);
        $relativePath = $relativeDirectory . '/' . $fileName;

        return [
            'media_type' => $mediaType,
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'public_url' => rtrim(app_config('base_url'), '/') . '/' . $relativePath,
            'mime_type' => $mimeType,
            'file_size' => filesize($destination) ?: $size,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'duration_seconds' => null,
        ];
    }

    private static function detectMimeType(string $filePath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);
        return is_string($mimeType) ? $mimeType : '';
    }

    private static function imageDimensions(string $filePath, string $mediaType): array
    {
        if ($mediaType !== 'image') {
            return ['width' => null, 'height' => null];
        }

        $size = getimagesize($filePath);
        if ($size === false) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => (int) $size[0],
            'height' => (int) $size[1],
        ];
    }

    private static function uploadErrorMessage(int $error): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์มีขนาดเกินข้อจำกัดของเซิร์ฟเวอร์',
            UPLOAD_ERR_FORM_SIZE => 'ไฟล์มีขนาดเกินข้อจำกัดของฟอร์ม',
            UPLOAD_ERR_PARTIAL => 'ไฟล์ถูกอัปโหลดมาไม่ครบ',
            UPLOAD_ERR_NO_FILE => 'กรุณาเลือกไฟล์',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่พบโฟลเดอร์ชั่วคราวสำหรับอัปโหลดบนเซิร์ฟเวอร์',
            UPLOAD_ERR_CANT_WRITE => 'เซิร์ฟเวอร์ไม่สามารถเขียนไฟล์ที่อัปโหลดได้',
            UPLOAD_ERR_EXTENSION => 'การอัปโหลดถูกบล็อกโดยส่วนขยายของเซิร์ฟเวอร์',
        ];

        return $messages[$error] ?? 'อัปโหลดไม่สำเร็จ';
    }
}
