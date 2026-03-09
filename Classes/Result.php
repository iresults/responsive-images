<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages;

use Iresults\ResponsiveImages\Result\ResultException;

/**
 * @template T
 * @template E of \Throwable
 */
abstract class Result
{
    protected function __construct(protected readonly mixed $value)
    {
    }

    /**
     * @phpstan-assert-if-true =T $this->value
     *
     * @phpstan-assert-if-false =E $this->value
     */
    abstract public function isOk(): bool;

    /**
     * @phpstan-assert-if-true =E $this->value
     *
     * @phpstan-assert-if-false =T $this->value
     */
    public function isErr(): bool
    {
        return !$this->isOk();
    }

    /**
     * @return T
     */
    public function unwrap(): mixed
    {
        if ($this->isErr()) {
            throw new ResultException(
                'Called `unwrap` on Err: ' . $this->value->getMessage(),
                1772111030,
                $this->value
            );
        }

        return $this->value;
    }

    /**
     * @return E
     */
    public function unwrapErr(): mixed
    {
        if ($this->isOk()) {
            throw new ResultException('Called `unwrapErr` on Ok', 1772111031);
        }

        return $this->value;
    }

    /**
     * Invoke `$ok()` if this instance is `Ok`. Invoke `$err()` if this instance is `Err`
     *
     * @template R
     * @template X
     *
     * @param callable(T): R $ok
     * @param callable(E): X $err
     *
     * @return R|X
     */
    public function doMatch(callable $ok, callable $err): mixed
    {
        if ($this->isOk()) {
            return $ok($this->value);
        } else {
            return $err($this->value);
        }
    }

    /**
     * @template R of \Throwable
     *
     * @param callable(E): R $callback
     *
     * @return Result<T, R>
     */
    public function mapErr(callable $callback): Result
    {
        if ($this->isOk()) {
            return clone $this;
        } else {
            return new Result\Err($callback($this->value));
        }
    }
}
