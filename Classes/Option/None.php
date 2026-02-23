<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Option;

use Iresults\ResponsiveImages\Option;

/**
 * @extends Option<never>
 */
class None extends Option
{
    public function unwrapOr(mixed $fallback): mixed
    {
        return $fallback;
    }

    public function unwrap(): mixed
    {
        throw new OptionException('Called `unwrap` on None');
    }

    public function isSome(): bool
    {
        return false;
    }

    public function map(callable $closure): mixed
    {
        throw new OptionException('Called `map` on None');
    }

    public function mapOr(mixed $default, callable $closure): mixed
    {
        return $default;
    }
}
