<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class SourceElementBuilder
{
    public function __construct(
        private readonly ImageResizingService $imageResizingService,
    ) {
    }

    /**
     * @param SizeDefinition[] $sizes
     */
    public function buildImgTag(
        array $sizes,
        File|FileReference $image,
        ?Area $crop,
        ?string $fileExtension,
        bool $useAbsoluteUri,
        ?SpecialFunction $specialFunction,
    ): ?TagBuilder {
        foreach ($sizes as $size) {
            if ($size->isDefault) {
                $imageTag = new TagBuilder('img');
                $resizedFallbackImage = $this->imageResizingService->resize(
                    $image,
                    $size,
                    1.0,
                    $crop,
                    $specialFunction,
                    $fileExtension,
                );
                $imageTag->addAttribute(
                    'src',
                    $resizedFallbackImage->getPublicUrl($useAbsoluteUri)
                );
                $imageTag->addAttribute(
                    'width',
                    $resizedFallbackImage->file->getProperty('width')
                );
                $imageTag->addAttribute(
                    'height',
                    $resizedFallbackImage->file->getProperty('height')
                );

                return $imageTag;
            }
        }

        return null;
    }

    /**
     * @param SizeDefinition[]       $sizes
     * @param non-empty-array<float> $pixelDensities
     */
    public function renderSourceElements(
        array $sizes,
        array $pixelDensities,
        File|FileReference $image,
        ?Area $crop,
        ?string $fileExtension,
        bool $useAbsoluteUri,
        ?SpecialFunction $specialFunction,
    ): string {
        return implode(
            '',
            array_map(fn ($size) => $this->renderSize(
                $size,
                $pixelDensities,
                $image,
                $crop,
                $fileExtension,
                $useAbsoluteUri,
                $specialFunction
            ), $sizes)
        );
    }

    /**
     * @param non-empty-array<float> $pixelDensities
     */
    private function renderSize(
        SizeDefinition $size,
        array $pixelDensities,
        File|FileReference $image,
        ?Area $crop,
        ?string $fileExtension,
        bool $useAbsoluteUri,
        ?SpecialFunction $specialFunction,
    ): string {
        $sourceTag = new TagBuilder('source');
        $srcsetOutput = [];
        foreach ($pixelDensities as $pixelDensity) {
            $resizedImage = $this->imageResizingService->resize(
                $image,
                $size,
                $pixelDensity,
                $crop,
                $specialFunction,
                $fileExtension,
            );

            $srcsetLine = $resizedImage->getPublicUrl($useAbsoluteUri);
            if (1.0 !== $resizedImage->pixelDensity) {
                $srcsetLine .= ' ' . $resizedImage->pixelDensity . 'x';
            }

            if (empty($srcsetOutput)) {
                $sourceTag->addAttribute(
                    'width',
                    $resizedImage->file->getProperty('width')
                );
                $sourceTag->addAttribute(
                    'height',
                    $resizedImage->file->getProperty('height')
                );
            }

            $srcsetOutput[] = $srcsetLine;
        }

        $sourceTag->addAttribute('srcset', implode(', ', $srcsetOutput));
        if ($size->mediaCondition) {
            $sourceTag->addAttribute('media', $size->mediaCondition);
        }

        return $sourceTag->render();
    }
}
