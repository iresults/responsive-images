<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use Iresults\ResponsiveImages\ArrayUtility;

final readonly class SizeDefinition
{
    private function __construct(
        public string $mediaCondition,
        public float $imageWidth,
        public bool $isDefault,
    ) {
    }

    public static function withMediaCondition(string $mediaCondition, float $imageWidth): self
    {
        return new SizeDefinition($mediaCondition, $imageWidth, false);
    }

    public static function defaultSizeDefinition(float $imageWidth): self
    {
        return new SizeDefinition('', $imageWidth, true);
    }

    /**
     * @param SizeDefinition[] $sizes
     */
    public static function findDefault(array $sizes): ?self
    {
        return ArrayUtility::find(
            $sizes,
            fn (SizeDefinition $s) => $s->isDefault
        );
    }
}
