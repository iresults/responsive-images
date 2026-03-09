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
        public SizeDefinition $size,
        public array $pixelDensities,
        public File|FileReference $file,
        public ?Area $crop,
        public string $fileExtension,
        public bool $useAbsoluteUri,
        public ?SpecialFunction $specialFunction,
        public ?string $fileNamePrefix,
        public bool $allowSmallerWidth = false,
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
            fileNamePrefix: $this->fileNamePrefix,
            allowSmallerWidth: $this->allowSmallerWidth,
        );
    }
}
