<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ResizedImage
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

    public function getSizeDefinitionWidthInPx(): string
    {
        return (int) $this->sizeDefinition->imageWidth . 'px';
    }

    public function getImageFileWidth(): int
    {
        return $this->file->getProperty('width');
    }
}
