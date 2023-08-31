<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
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
        ?SpecialFunction $specialFunction = null,
        string $fileExtension = ''
    ): ResizedImage {
        $pixelWidth = $pixelDensity * $sizeDefinition->imageWidth;
        $processingInstructions = [
            'width' => $pixelWidth,
            'crop'  => $crop,
        ];
        if ($fileExtension) {
            $processingInstructions['fileExtension'] = $fileExtension;
        }
        if ($specialFunction === SpecialFunction::Square) {
            $processingInstructions['width'] = $pixelWidth . 'c';
            $processingInstructions['height'] = $pixelWidth;
        }

        return new ResizedImage(
            $this->imageService->applyProcessingInstructions($image, $processingInstructions),
            $sizeDefinition,
            $pixelDensity
        );
    }
}
