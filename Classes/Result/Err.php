<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Result;

use Iresults\ResponsiveImages\Result;
use Throwable;

/**
 * @template E of Throwable
 *
 * @extends Result<never,E>
 */
class Err extends Result
{
    /**
     * @param E $value
     */
    public function __construct(Throwable $value)
    {
        parent::__construct($value);
    }

    public function isOk(): bool
    {
        return false;
    }
}
