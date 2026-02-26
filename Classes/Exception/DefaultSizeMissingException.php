<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Exception;

use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use RuntimeException;
use Throwable;

class DefaultSizeMissingException extends RuntimeException
{
    /**
     * @param SizeDefinition[] $sizes
     */
    public function __construct(
        public readonly array $sizes,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}
