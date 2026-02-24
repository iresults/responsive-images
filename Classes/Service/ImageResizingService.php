<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizeConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizedImage;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageResizingService
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly MimeTypeService $mimeTypeService,
    ) {
    }

    public function resize(ResizeConfiguration $configuration): ?ResizedImage
    {
        $pixelWidth = $configuration->pixelDensity * $configuration->size->imageWidth;
        $processingInstructions = [
            'width' => $pixelWidth,
            'crop'  => $configuration->crop,
        ];
        if ($configuration->fileExtension) {
            $processingInstructions['fileExtension'] = $configuration->fileExtension;
        }
        if (SpecialFunction::Square === $configuration->specialFunction) {
            $processingInstructions['width'] = $pixelWidth . 'c';
            $processingInstructions['height'] = $pixelWidth;
        }

        $processedImage = $this->imageService->applyProcessingInstructions(
            $configuration->file,
            $processingInstructions
        );
        $processedImageWidth = (float) $processedImage->getProperty('width');

        if ($processedImage->getIdentifier() === $configuration->file->getIdentifier()) {
            return null;
        }

        $isRenderableVectorGraphic = $this->mimeTypeService
            ->isRenderableVectorGraphic($processedImage->getIdentifier());

        // Allow smaller processed images for SVG files, since they can be
        // scaled smoothly by the browser
        if (!$isRenderableVectorGraphic && $processedImageWidth < $pixelWidth) {
            return null;
        }

        assert($isRenderableVectorGraphic || $processedImageWidth === $pixelWidth);

        return new ResizedImage(
            $processedImage,
            $configuration->size,
            $configuration->pixelDensity
        );
    }
}
