<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Option;

use Iresults\ResponsiveImages\Option;

/**
 * @template T
 *
 * @extends Option<T>
 */
class Some extends Option
{
    /**
     * @param T $value
     */
    public function __construct(protected readonly mixed $value)
    {
    }

    public function unwrapOr(mixed $fallback): mixed
    {
        return $this->value;
    }

    /**
     * @return T
     */
    public function unwrap(): mixed
    {
        return $this->value;
    }

    public function isSome(): bool
    {
        return true;
    }

    public function map(callable $closure): mixed
    {
        return $closure($this->value);
    }

    public function mapOr(mixed $default, callable $closure): mixed
    {
        return $closure($this->value);
    }
}
