<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

class SizeDefinition
{
    private function __construct(
        public readonly string $mediaCondition,
        public readonly string $imageWidth,
        public readonly bool $isDefault,
    ) {
    }

    public static function withMediaCondition(string $mediaCondition, string $imageWidth): self
    {
        return new SizeDefinition($mediaCondition, $imageWidth, false);
    }

    public static function defaultSizeDefinition(string $imageWidth): self
    {
        return new SizeDefinition('', $imageWidth, true);
    }
}
