<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;

class CropInformation
{
    public function __construct(
        public readonly CropVariantCollection $variantCollection,
        public readonly string $variant,
        public readonly Area|null $area
    ) {
    }
}
