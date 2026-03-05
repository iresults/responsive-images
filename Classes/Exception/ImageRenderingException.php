<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Exception;

use Iresults\ResponsiveImages\Domain\ValueObject\ImageRenderingConfiguration;
use RuntimeException;
use Throwable;

class ImageRenderingException extends RuntimeException
{
    public function __construct(
        public readonly ImageRenderingConfiguration $configuration,
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
