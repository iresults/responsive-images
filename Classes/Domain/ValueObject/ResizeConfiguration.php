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
        public SizeDefinition $size,
        public float $pixelDensity,
        public File|FileReference $file,
        public ?Area $crop,
        public string $fileExtension,
        public ?SpecialFunction $specialFunction,
        public bool $allowSmallerWidth,
        public ?string $fileNamePrefix,
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
            allowSmallerWidth: $configuration->allowSmallerWidth,
            fileNamePrefix: $configuration->fileNamePrefix,
        );
    }
}
