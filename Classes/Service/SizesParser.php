<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use InvalidArgumentException;
use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use TYPO3\CMS\Core\Utility\MathUtility;

use function array_map;
use function explode;
use function str_ends_with;
use function strrpos;
use function substr;

class SizesParser
{
    /**
     * Parse an <img> sizes attribute into SizeDefinitions
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#sizes
     *
     * @return SizeDefinition[]
     */
    public function parseSizes(string $sizesString): array
    {
        $sizes = array_map('trim', explode(',', $sizesString));
        $sizeDefinitions = [];
        foreach ($sizes as $size) {
            $lastSpacePosition = strrpos($size, ' ');
            if (str_ends_with($size, 'px')) {
                $size = substr($size, 0, -2);
            }
            if (false === $lastSpacePosition) {
                $sizeDefinitions[] = SizeDefinition::defaultSizeDefinition(
                    $this->parseAsFloat($size)
                );
            } else {
                $sizeDefinitions[] = SizeDefinition::withMediaCondition(
                    substr($size, 0, $lastSpacePosition),
                    $this->parseAsFloat(substr($size, $lastSpacePosition + 1))
                );
            }
        }

        return $sizeDefinitions;
    }

    private function parseAsFloat(string $input): float
    {
        if (!MathUtility::canBeInterpretedAsFloat($input)) {
            throw new InvalidArgumentException(
                sprintf('Size "%s" can not be parsed into a float size', $input),
                1771516559
            );
        }

        return (float) $input;
    }
}
