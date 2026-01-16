<?php

declare(strict_types=1);

namespace WPMigration\Service;

use DirectoryIterator;

final class MediaOptimizer
{
    /** @return array<string, mixed> */
    public function optimize(string $path): array
    {
        $uploads = rtrim($path, '/') . '/wp-content/uploads';
        if (!is_dir($uploads)) {
            return ['optimized' => 0, 'path' => $uploads];
        }

        $optimized = 0;
        foreach (new DirectoryIterator($uploads) as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) {
                $this->compressImage($file->getPathname(), 85, 'webp');
                $optimized++;
            }
        }

        return ['optimized' => $optimized, 'path' => $uploads];
    }

    public function compressImage(string $path, int $quality, string $format): void
    {
        $format = strtolower($format);
        if (!extension_loaded('gd')) {
            return;
        }

        $image = $this->createImageResource($path);
        if ($image === null) {
            return;
        }

        $output = preg_replace('/\.[^.]+$/', '.' . $format, $path);
        if ($output === null) {
            return;
        }

        if ($format === 'webp') {
            imagewebp($image, $output, $quality);
        } elseif ($format === 'jpeg' || $format === 'jpg') {
            imagejpeg($image, $output, $quality);
        } else {
            imagepng($image, $output);
        }

        imagedestroy($image);
    }

    private function createImageResource(string $path): mixed
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            default => null,
        };
    }
}
