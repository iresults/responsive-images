<?php

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

final readonly class ResizeConfiguration
{
    /**
     * @param File|FileReference $file Accept `FileReference` which may contain cropping information
     */
    public function __construct(
        public readonly SizeDefinition $size,
        public readonly float $pixelDensity,
        public readonly File|FileReference $file,
        public readonly ?Area $crop,
        public readonly string $fileExtension,
        public readonly ?SpecialFunction $specialFunction,
    ) {
    }

    public static function fromRenderingConfigurationForPixelDensity(
        ImageRenderingConfiguration $configuration,
        float $pixelDensity,
    ): self {
        return new self(
            size: $configuration->size,
            pixelDensity: $pixelDensity,
            file: $configuration->file,
            crop: $configuration->crop,
            fileExtension: $configuration->fileExtension,
            specialFunction: $configuration->specialFunction,
        );
    }
}
