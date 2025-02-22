<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Frame;
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

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    public function map(callable $map): self;

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): Frame<U> $map
     *
     * @return self<U>
     */
    public function flatMap(callable $map): self;
}
