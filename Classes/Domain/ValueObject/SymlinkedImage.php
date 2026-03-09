<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class SymlinkedImage implements ProcessedImageInterface
{
    /**
     * @param non-empty-string $publicUrl
     */
    public function __construct(
        private ResizedImage $resizedImage,
        private string $publicUrl,
    ) {
    }

    /**
     * @return non-empty-string NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl(bool $absolute = false): string
    {
        return $absolute
            ? GeneralUtility::locationHeaderUrl($this->publicUrl)
            : $this->publicUrl;
    }

    public function getPixelDensity(): float
    {
        return $this->resizedImage->getPixelDensity();
    }

    public function getProcessedWidth(): int
    {
        return $this->resizedImage->getProcessedWidth();
    }

    public function getProcessedHeight(): int
    {
        return $this->resizedImage->getProcessedHeight();
    }

    public function getConfigurationChecksum(): string
    {
        return $this->resizedImage->getConfigurationChecksum();
    }

    public function getExtension(): string
    {
        return $this->resizedImage->getExtension();
    }

    public function getMimeType(): string
    {
        return $this->resizedImage->getMimeType();
    }

    public function getIdentifier(): string
    {
        return $this->resizedImage->getIdentifier();
    }

    /**
     * @return non-empty-string
     */
    public function getPath(): string
    {
        $publicUrl = $this->getPublicUrl();

        assert(str_starts_with($publicUrl, '/'));

        return Environment::getPublicPath() . $publicUrl;
    }
}
