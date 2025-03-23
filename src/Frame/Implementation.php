<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @internal
 * @template-covariant T
 */
interface Implementation
{
    /**
     * @param callable(?int<1, max>): Maybe<Str> $read
     * @param callable(): Maybe<Str> $readLine
     *
     * @return Maybe<T>
     */
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe;
}
