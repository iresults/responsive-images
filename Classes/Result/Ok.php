<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Result;

use Iresults\ResponsiveImages\Result;

/**
 * @template T
 *
 * @extends Result<T,never>
 */
class Ok extends Result
{
    /**
     * @param T $value
     */
    public function __construct(mixed $value)
    {
        parent::__construct($value);
    }

    public function isOk(): bool
    {
        return true;
    }
}
