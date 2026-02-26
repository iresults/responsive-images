<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizeConfiguration;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizedImage;
use Iresults\ResponsiveImages\Exception\ImageResizeException;
use Iresults\ResponsiveImages\Result;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageResizingService
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly MimeTypeService $mimeTypeService,
    ) {
    }

    /**
     * @return Result<ResizedImage,ImageResizeException>
     */
    public function resize(ResizeConfiguration $configuration): Result
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
            return new Result\Err(new ImageResizeException(
                $configuration,
                'File was not changed',
                1772113756
            ));
        }

        $isRenderableVectorGraphic = $this->mimeTypeService
            ->isRenderableVectorGraphic($processedImage->getIdentifier());

        // Allow smaller processed images for SVG files, since they can be
        // scaled smoothly by the browser
        if (!$isRenderableVectorGraphic && $processedImageWidth < $pixelWidth) {
            return new Result\Err(new ImageResizeException(
                $configuration,
                'Processed image width too small',
                1772113759
            ));
        }

        assert($isRenderableVectorGraphic || $processedImageWidth === $pixelWidth);

        return new Result\Ok(new ResizedImage(
            $processedImage,
            $configuration->size,
            $configuration->pixelDensity
        ));
    }
}
