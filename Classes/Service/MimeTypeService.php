<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

final class MimeTypeService
{
    private const IMAGE_FILES = [
        'ai'   => 'application/pdf',
        'avif' => 'image/avif',
        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'tga'  => 'image/x-tga',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'webp' => 'image/webp',
    ];

    /**
     * @param non-empty-string $fileExtension
     */
    public function getMimeTypeForExtension(string $fileExtension): ?string
    {
        return self::IMAGE_FILES[strtolower($fileExtension)] ?? null;
    }

    public function getMimeTypeForUri(string $publicUri): ?string
    {
        $fileExtensionPart = strrchr($publicUri, '.');
        if (false === $fileExtensionPart) {
            return null;
        }

        $fileExtension = substr($fileExtensionPart, 1);
        if (!$fileExtension) {
            return null;
        }

        return $this->getMimeTypeForExtension($fileExtension);
    }
}
