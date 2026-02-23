<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages;

use Iresults\ResponsiveImages\Option\OptionException;

/**
 * @template T
 */
abstract class Option
{
    /**
     * @return T
     *
     * @throws OptionException if invoked on None
     */
    abstract public function unwrap(): mixed;

    /**
     * @template R
     *
     * @param R $fallback
     *
     * @return T|R
     */
    abstract public function unwrapOr(mixed $fallback): mixed;

    abstract public function isSome(): bool;

    public function isNone(): bool
    {
        return !$this->isSome();
    }

    /**
     * @template R
     *
     * @param callable(T): R $closure
     *
     * @return R
     *
     * @throws OptionException if invoked on None
     */
    abstract public function map(callable $closure): mixed;

    /**
     * @template R
     *
     * @param R              $default
     * @param callable(T): R $closure
     *
     * @return R
     *
     * @throws OptionException if invoked on None
     */
    abstract public function mapOr(mixed $default, callable $closure): mixed;
}
