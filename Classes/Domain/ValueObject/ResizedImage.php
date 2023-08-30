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
        public readonly float $pixelDensity
    ) {
    }

    /**
     * Get public url of image depending on the environment
     *
     * @param bool|false $absolute Force absolute URL
     */
    public function getPublicUrl(bool $absolute = false): string
    {
        $imageUrl = $this->file->getPublicUrl();
        if (!$absolute || $imageUrl === null) {
            return (string)$imageUrl;
        }

        return GeneralUtility::locationHeaderUrl($imageUrl);
    }


    public function getSizeDefinitionWidthInPx(): string
    {
        return (int)$this->sizeDefinition->imageWidth . 'px';
    }

    /**
     * @see https://developer.mozilla.org/en-US/docs/Glossary/Intrinsic_Size
     * @return string
     */
    public function getImageIntrinsicWidth(): string
    {
        return $this->gets->getProperty('width') . 'w';
    }

    public function getImageFileWidth(): int
    {
        return $this->file->getProperty('width');
    }

}
