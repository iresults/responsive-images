<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class ResizedImage implements ProcessedImageInterface
{
    public function __construct(
        public readonly ProcessedFile $file,
        public readonly SizeDefinition $sizeDefinition,
        public readonly float $pixelDensity,
    ) {
    }

    /**
     * @return non-empty-string|null NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl(bool $absolute = false): ?string
    {
        $imageUrl = $this->file->getPublicUrl();
        if (null === $imageUrl) {
            return null;
        }

        return $absolute
            ? GeneralUtility::locationHeaderUrl($imageUrl)
            : $imageUrl;
    }

    public function getPixelDensity(): float
    {
        return $this->pixelDensity;
    }

    public function getProcessedWidth(): int
    {
        return $this->file->getProperty('width');
    }

    public function getProcessedHeight(): int
    {
        return $this->file->getProperty('height');
    }

    public function getConfigurationChecksum(): string
    {
        return $this->file->getTask()->getConfigurationChecksum();
    }

    public function getExtension(): string
    {
        return $this->file->getExtension();
    }

    /**
     * Get the MIME type of this file
     *
     * @return non-empty-string mime type
     */
    public function getMimeType(): string
    {
        return $this->file->getMimeType();
    }

    public function isStoredLocally(): bool
    {
        $driverType = $this->file->getStorage()->getDriverType();

        return 'local' === strtolower($driverType);
    }

    /**
     * @see ProcessedFile::getIdentifier()
     *
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->file->getIdentifier();
    }

    /**
     * @return non-empty-string
     */
    public function getPath(): ?string
    {
        $publicUrl = $this->getPublicUrl();
        if (null === $publicUrl) {
            return null;
        }

        assert(str_starts_with($publicUrl, '/'));

        return Environment::getPublicPath() . $publicUrl;
    }
}
