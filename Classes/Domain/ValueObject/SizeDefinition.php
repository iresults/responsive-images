<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

use Iresults\ResponsiveImages\ArrayUtility;

final readonly class SizeDefinition
{
    private function __construct(
        public string $mediaCondition,
        public float $imageWidth,
        public string $unit,
        public bool $isDefault,
    ) {
    }

    public static function withMediaCondition(string $mediaCondition, float $imageWidth, string $unit): self
    {
        return new SizeDefinition($mediaCondition, $imageWidth, $unit, false);
    }

    public static function defaultSizeDefinition(float $imageWidth, string $unit): self
    {
        return new SizeDefinition('', $imageWidth, $unit, true);
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

    public function __toString(): string
    {
        return $this->mediaCondition . ' ' . $this->imageWidth . $this->unit;
    }
}
