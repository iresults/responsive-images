<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\Domain\ValueObject\ResizedImage;
use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageResizingService
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    public function resize(
        File|FileReference $image,
        SizeDefinition $sizeDefinition,
        float $pixelDensity,
        ?Area $crop = null,
        string $fileExtension = ''
    ): ResizedImage {
        //        $processedFile = $this->resize(
        //            $image,
        //            $pixelDensity * $sizeDefinition->imageWidth,
        //            false,
        //            $crop,
        //            $fileExtension
        //        );

        $processingInstructions = [
            'width' => $pixelDensity * $sizeDefinition->imageWidth,
            // 'height' => $height,
            'crop'  => $crop,
        ];
        if ($fileExtension) {
            $processingInstructions['fileExtension'] = $fileExtension;
        }

        $processedFile = $this->imageService->applyProcessingInstructions($image, $processingInstructions);

        return new ResizedImage($processedFile, $sizeDefinition, $pixelDensity);
    }
}
