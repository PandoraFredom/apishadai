<?php

namespace App\Utils\Repositories;

use App\Utils\Services\Base64UtilityService;
use function in_array;
use function is_string;
use function Safe\imagedestroy;
use function strlen;

class Base64UtilityRepository implements Base64UtilityService
{
    private const MAX_IMAGE_BYTES = 65536;

    private const MAX_IMAGE_PIXELS = 10000000;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function sanitize(string $data): ?string
    {
        $base64 = $this->extractBase64Payload($data);

        if ($base64 === null) {
            return null;
        }

        $decoded = base64_decode($base64, true);

        if (
            $decoded === false
            || $decoded === ''
            || strlen($decoded) > self::MAX_IMAGE_BYTES
            || $this->containsSuspiciousPayload($decoded)
        ) {
            return null;
        }

        $imageInfo = @getimagesizefromstring($decoded);

        if ($imageInfo === false || empty($imageInfo['mime'])) {
            return null;
        }

        $mimeType = strtolower($imageInfo['mime']);
        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);

        if (
            ! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)
            || $width <= 0
            || $height <= 0
            || ($width * $height) > self::MAX_IMAGE_PIXELS
            || ! function_exists('imagecreatefromstring')
        ) {
            return null;
        }

        $image = @imagecreatefromstring($decoded);

        if ($image === false) {
            return null;
        }

        try {
            $sanitized = $this->encodeCleanImage($image, $mimeType);
        } finally {
            imagedestroy($image);
        }

        if ($sanitized === null || strlen($sanitized) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        return base64_encode($sanitized);
    }

    private function extractBase64Payload(string $data): ?string
    {
        $data = trim($data);

        if ($data === '') {
            return null;
        }

        if (str_starts_with(strtolower($data), 'data:')) {
            if (! preg_match('/^data:(image\/[a-z0-9.+-]+);base64,(.*)$/is', $data, $matches)) {
                return null;
            }

            if (! in_array(strtolower($matches[1]), self::ALLOWED_MIME_TYPES, true)) {
                return null;
            }

            $data = $matches[2];
        }

        $data = preg_replace('/\s+/', '', $data);

        if (! is_string($data) || $data === '' || ! preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $data)) {
            return null;
        }

        if (strlen($data) % 4 !== 0) {
            return null;
        }

        return $data;
    }

    private function encodeCleanImage(\GdImage $image, string $mimeType): ?string
    {
        ob_start();

        $encoded = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, null, 85),
            'image/png' => $this->encodeCleanPng($image),
            'image/gif' => imagegif($image),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, null, 85) : false,
            default => false,
        };

        $contents = ob_get_clean();

        if ($encoded === false || $contents === false || $contents === '') {
            return null;
        }

        return $contents;
    }

    private function encodeCleanPng(\GdImage $image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image, null, 9);
    }

    private function containsSuspiciousPayload(string $decoded): bool
    {
        $payload = strtolower($decoded);

        $blockedPatterns = [
            '<?',
            '<script',
            '<svg',
            'javascript:',
            'data:text/html',
            'eval(',
            'base64_decode',
            'shell_exec',
            'system(',
            'passthru(',
            'proc_open',
            'popen(',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (str_contains($payload, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
