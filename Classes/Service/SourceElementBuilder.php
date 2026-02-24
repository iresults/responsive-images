<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\ArrayUtility;
use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\ImageRenderingConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizeConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use Iresults\ResponsiveImages\Option;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class SourceElementBuilder
{
    public function __construct(
        private readonly ImageResizingService $imageResizingService,
        private readonly MimeTypeService $mimeTypeService,
    ) {
    }

    /**
     * @param SizeDefinition[]    $sizes
     * @param array<string,mixed> $additionalArguments
     *
     * @return Option<TagBuilder>
     */
    public function buildImgTag(
        array $sizes,
        File|FileReference $image,
        ?Area $crop,
        string $fileExtension,
        bool $useAbsoluteUri,
        ?SpecialFunction $specialFunction,
        array $additionalArguments,
    ): Option {
        $defaultSize = SizeDefinition::findDefault($sizes);
        if (!$defaultSize) {
            return new Option\None();
        }

        $resizedFallbackImage = $this->imageResizingService->resize(
            new ResizeConfiguration(
                size: $defaultSize,
                pixelDensity: 1.0,
                file: $image,
                crop: $crop,
                fileExtension: $fileExtension,
                specialFunction: $specialFunction,
            )
        );

        $imageTag = new TagBuilder('img');
        foreach ($additionalArguments as $argumentName => $argumentValue) {
            if (null !== $argumentValue && '' !== $argumentValue) {
                $imageTag->addAttribute($argumentName, $argumentValue);
            }
        }

        if ($resizedFallbackImage) {
            $uri = $resizedFallbackImage->getPublicUrl($useAbsoluteUri);

            $imageTag->addAttribute('src', $uri);
            $imageTag->addAttribute(
                'width',
                $resizedFallbackImage->file->getProperty('width')
            );
            $imageTag->addAttribute(
                'height',
                $resizedFallbackImage->file->getProperty('height')
            );
        } else {
            $uri = $useAbsoluteUri
                ? GeneralUtility::locationHeaderUrl($image->getPublicUrl())
                : $image->getPublicUrl();

            $imageTag->addAttribute('src', $uri);
            $imageTag->addAttribute('width', $image->getProperty('width'));
            $imageTag->addAttribute('height', $image->getProperty('height'));
        }

        return new Option\Some($imageTag);
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
        string $fileExtension,
        string $preferredFileExtension,
        bool $useAbsoluteUri,
        ?SpecialFunction $specialFunction,
    ): string {
        $sourceIsRenderableVectorGraphic = $this->mimeTypeService
            ->isRenderableVectorGraphic($image->getMimeType());
        $generatePreferredFileExtensionFile = $preferredFileExtension
            && !$sourceIsRenderableVectorGraphic;

        /** @var ImageRenderingConfiguration[] $renderingConfigurations */
        $renderingConfigurations = [];
        foreach ($sizes as $size) {
            $configuration = new ImageRenderingConfiguration(
                size: $size,
                pixelDensities: $pixelDensities,
                file: $image,
                crop: $crop,
                fileExtension: $fileExtension,
                useAbsoluteUri: $useAbsoluteUri,
                specialFunction: $specialFunction,
            );

            // Render the `<source>` tags for the preferred file extension
            // before the default file extension `<source>` tags
            // This e.g. allows users to offer WebP images as preferred option
            // and the image file's native type as fallback (this is skipped
            // for SVG files)
            if ($generatePreferredFileExtensionFile) {
                $renderingConfigurations[] = $configuration->withFileExtension(
                    $preferredFileExtension
                );
            }
            $renderingConfigurations[] = $configuration;
        }

        $renderedSources = array_map(
            $this->buildSourceTag(...),
            $renderingConfigurations
        );
        $hasSources = null !== ArrayUtility::find(
            $renderedSources,
            fn (Option $o) => $o->isSome()
        );

        if ($hasSources) {
            return $this->mapJoin(
                $renderedSources,
                fn (Option $o) => $o->mapOr(
                    '<!-- skip -->',
                    fn ($tag) => $tag->render()
                ),
            );
        }

        // If a preferred file extension was specified, try to at least render
        // a `<source>` tag with the original image's width and the preferred
        // file extension (e.g. provide a WebP version even if the image could
        // not be scaled)
        if ($generatePreferredFileExtensionFile) {
            $size = SizeDefinition::defaultSizeDefinition(
                $image->getProperty('width')
            );

            $configuration = new ImageRenderingConfiguration(
                size: $size,
                pixelDensities: $pixelDensities,
                file: $image,
                crop: $crop,
                fileExtension: $preferredFileExtension,
                useAbsoluteUri: $useAbsoluteUri,
                specialFunction: $specialFunction,
            );

            return $this->buildSourceTag($configuration)
                ->mapOr('', fn (TagBuilder $tag) => $tag->render());
        }

        return '';
    }

    /**
     * @return Option<TagBuilder>
     */
    private function buildSourceTag(
        ImageRenderingConfiguration $configuration,
    ): Option {
        $sourceTag = new TagBuilder('source');
        $srcsetOutput = [];
        $publicUri = '';
        foreach ($configuration->pixelDensities as $pixelDensity) {
            $resizedImage = $this->imageResizingService->resize(
                ResizeConfiguration::fromRenderingConfigurationForPixelDensity(
                    $configuration,
                    $pixelDensity
                )
            );

            if (null === $resizedImage) {
                // Image could not be resized to the requested size -> do not
                // create an entry for it
                continue;
            }

            $publicUri = $resizedImage->getPublicUrl($configuration->useAbsoluteUri);
            $srcsetLine = $publicUri;
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

        if (empty($srcsetOutput)) {
            // No srcset images have been generated (e.g. because the source
            // files are to small)
            return new Option\None();
        }

        $mimeType = $this->mimeTypeService->getMimeTypeForUri($publicUri);
        if ($mimeType) {
            $sourceTag->addAttribute('type', $mimeType);
        }

        $sourceTag->addAttribute('srcset', implode(', ', $srcsetOutput));
        if ($configuration->size->mediaCondition) {
            $sourceTag->addAttribute('media', $configuration->size->mediaCondition);
        }

        return new Option\Some($sourceTag);
    }

    /**
     * @template T
     *
     * @param callable(Option<T>):string $callable
     * @param Option<T>[]                $collection
     */
    private function mapJoin(array $collection, callable $callable): string
    {
        return implode(
            '',
            array_map(
                $callable,
                $collection
            )
        );
    }
}
