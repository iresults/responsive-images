<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages;

class ArrayUtility
{
    /**
     * @template T
     *
     * @param T[]               $collection
     * @param callable(T): bool $callback
     *
     * @return T|null
     */
    public static function find(array $collection, callable $callback): mixed
    {
        foreach ($collection as $value) {
            if ($callback($value)) {
                return $value;
            }
        }

        return null;
    }
}
