<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\ArrayUtility;
use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\ImageRenderingConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizeConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use Iresults\ResponsiveImages\Exception\DefaultSizeMissingException;
use Iresults\ResponsiveImages\Exception\ImageRenderingException;
use Iresults\ResponsiveImages\Result;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class SourceElementBuilder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ImageResizingService $imageResizingService,
        private readonly MimeTypeService $mimeTypeService,
    ) {
    }

    /**
     * @param SizeDefinition[]    $sizes
     * @param array<string,mixed> $additionalArguments
     *
     * @return Result<TagBuilder,DefaultSizeMissingException>
     */
    public function buildImgTag(
        array $sizes,
        File|FileReference $image,
        ?Area $crop,
        string $fileExtension,
        bool $useAbsoluteUri,
        ?SpecialFunction $specialFunction,
        ?string $fileNamePrefix,
        array $additionalArguments,
    ): Result {
        $defaultSize = SizeDefinition::findDefault($sizes);
        if (!$defaultSize) {
            return new Result\Err(new DefaultSizeMissingException(
                $sizes,
                sprintf(
                    'No default size definition found in `%s`',
                    implode(', ', $sizes)
                ),
                1772113716
            ));
        }

        $resizedFallbackImageResult = $this->imageResizingService->resize(
            new ResizeConfiguration(
                size: $defaultSize,
                pixelDensity: 1.0,
                file: $image,
                crop: $crop,
                fileExtension: $fileExtension,
                specialFunction: $specialFunction,
                allowSmallerWidth: true,
                fileNamePrefix: $fileNamePrefix
            )
        );

        $imageTag = new TagBuilder('img');
        foreach ($additionalArguments as $argumentName => $argumentValue) {
            if (null !== $argumentValue && '' !== $argumentValue) {
                $imageTag->addAttribute($argumentName, $argumentValue);
            }
        }

        if ($resizedFallbackImageResult->isOk()) {
            $resizedFallbackImage = $resizedFallbackImageResult->unwrap();
            $uri = $resizedFallbackImage->getPublicUrl($useAbsoluteUri);

            $imageTag->addAttribute('src', $uri);
            $imageTag->addAttribute(
                'width',
                (string) $resizedFallbackImage->getProcessedWidth()
            );
            $imageTag->addAttribute(
                'height',
                (string) $resizedFallbackImage->getProcessedHeight()
            );
        } else {
            $publicUrl = $image->getPublicUrl();
            $uri = $useAbsoluteUri && $publicUrl
                ? GeneralUtility::locationHeaderUrl($publicUrl)
                : $publicUrl;

            $imageTag->addAttribute('src', $uri);
            $imageTag->addAttribute('width', $image->getProperty('width'));
            $imageTag->addAttribute('height', $image->getProperty('height'));
        }

        return new Result\Ok($imageTag);
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
        ?string $fileNamePrefix,
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
                fileNamePrefix: $fileNamePrefix
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

        $handleOkResult = fn (TagBuilder $tag) => $tag->render();
        $handleErrorResult = $this->handleErrorResult(...);

        $hasSources = null !== ArrayUtility::find(
            $renderedSources,
            fn (Result $r) => $r->isOk()
        );

        if ($hasSources) {
            return $this->mapJoin(
                $renderedSources,
                $handleOkResult,
                $handleErrorResult
            );
        }

        // If a preferred file extension was specified, try to at least render
        // a `<source>` tag with the original image's width and the preferred
        // file extension (e.g. provide a WebP version even if the image could
        // not be scaled)
        if ($generatePreferredFileExtensionFile) {
            $size = SizeDefinition::defaultSizeDefinition(
                $image->getProperty('width'),
                'px'
            );

            $configuration = new ImageRenderingConfiguration(
                size: $size,
                pixelDensities: $pixelDensities,
                file: $image,
                crop: $crop,
                fileExtension: $preferredFileExtension,
                useAbsoluteUri: $useAbsoluteUri,
                specialFunction: $specialFunction,
                fileNamePrefix: $fileNamePrefix,
            );

            return $this->buildSourceTag($configuration)
                ->doMatch($handleOkResult, $handleErrorResult);
        }

        return '';
    }

    /**
     * @return Result<TagBuilder,ImageRenderingException>
     */
    private function buildSourceTag(
        ImageRenderingConfiguration $configuration,
    ): Result {
        $sourceTag = new TagBuilder('source');
        $srcsetOutput = [];
        $publicUri = '';
        foreach ($configuration->pixelDensities as $pixelDensity) {
            $resizedImageResult = $this->imageResizingService->resize(
                ResizeConfiguration::fromRenderingConfigurationForPixelDensity(
                    $configuration,
                    $pixelDensity
                )
            );

            if ($resizedImageResult->isErr()) {
                // Image could not be resized to the requested size -> do not
                // create an entry for it
                continue;
            }

            $resizedImage = $resizedImageResult->unwrap();
            $publicUri = $resizedImage->getPublicUrl($configuration->useAbsoluteUri);
            $srcsetLine = $publicUri;
            if (1.0 !== $resizedImage->getPixelDensity()) {
                $srcsetLine .= ' ' . $resizedImage->getPixelDensity() . 'x';
            }

            if (empty($srcsetOutput)) {
                $sourceTag->addAttribute(
                    'width',
                    (string) $resizedImage->getProcessedWidth()
                );
                $sourceTag->addAttribute(
                    'height',
                    (string) $resizedImage->getProcessedHeight()
                );
            }

            $srcsetOutput[] = $srcsetLine;
        }

        if (empty($srcsetOutput)) {
            // No srcset images have been generated (e.g. because the source
            // files are to small)
            return new Result\Err(new ImageRenderingException(
                $configuration,
                'No `srcset` images were rendered',
                1772113741
            ));
        }

        $mimeType = $this->mimeTypeService->getMimeTypeForUri((string) $publicUri);
        if ($mimeType) {
            $sourceTag->addAttribute('type', $mimeType);
        }

        $sourceTag->addAttribute('srcset', implode(', ', $srcsetOutput));
        if ($configuration->size->mediaCondition) {
            $sourceTag->addAttribute('media', $configuration->size->mediaCondition);
        }

        return new Result\Ok($sourceTag);
    }

    /**
     * @template T
     * @template E of \Throwable
     *
     * @param Result<T,E>[]       $collection
     * @param callable(T): string $ok
     * @param callable(E): string $err
     */
    private function mapJoin(
        array $collection,
        callable $ok,
        callable $err,
    ): string {
        return implode(
            '',
            array_map(
                fn (Result $r) => $r->doMatch($ok, $err),
                $collection
            )
        );
    }

    private function handleErrorResult(ImageRenderingException $e): string
    {
        $this->logger?->debug(
            'Could not generate processed images for file "{filename}"',
            ['exception' => $e]
        );

        if (!$this->addDebugInformation()) {
            return '';
        }

        return sprintf(
            '<!-- %s @%s [%s] -->',
            $e->configuration->fileExtension,
            $e->configuration->size->imageWidth,
            $e->configuration->crop
        );
    }

    private function addDebugInformation(): bool
    {
        return Environment::getContext()->isDevelopment();
    }
}
