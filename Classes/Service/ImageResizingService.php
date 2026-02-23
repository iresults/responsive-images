<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizeConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizedImage;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageResizingService
{
    public function __construct(private readonly ImageService $imageService)
    {
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
        if ($processedImageWidth < $pixelWidth) {
            return null;
        }

        assert($processedImageWidth === $pixelWidth);

        return new ResizedImage(
            $processedImage,
            $configuration->size,
            $configuration->pixelDensity
        );
    }
}
