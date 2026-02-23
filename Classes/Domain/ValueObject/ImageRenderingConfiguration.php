<?php

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

final readonly class ImageRenderingConfiguration
{
    /**
     * @param File|FileReference     $file           Accept `FileReference` which may contain cropping information
     * @param non-empty-array<float> $pixelDensities
     */
    public function __construct(
        public readonly SizeDefinition $size,
        public readonly array $pixelDensities,
        public readonly File|FileReference $file,
        public readonly ?Area $crop,
        public readonly string $fileExtension,
        public readonly bool $useAbsoluteUri,
        public readonly ?SpecialFunction $specialFunction,
    ) {
    }

    /**
     * @param non-empty-string $fileExtension
     */
    public function withFileExtension(string $fileExtension): self
    {
        return new ImageRenderingConfiguration(
            size: $this->size,
            pixelDensities: $this->pixelDensities,
            file: $this->file,
            crop: $this->crop,
            fileExtension: $fileExtension,
            useAbsoluteUri: $this->useAbsoluteUri,
            specialFunction: $this->specialFunction,
        );
    }
}
