<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Frame;

use Innmind\IO\Next\Frame;
use Innmind\Immutable\Maybe as Monad;

/**
 * Use this frame to hardcode a value inside a frame composition
 *
 * @internal
 * @template T
 * @implements Implementation<T>
 */
final class Maybe implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param Monad<T> $value
     */
    private function __construct(
        private Monad $value,
    ) {
    }

    #[\Override]
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Monad {
        return $this->value;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function just(mixed $value): self
    {
        return new self(Monad::just($value));
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Monad<A> $value
     *
     * @return self<A>
     */
    public static function of(Monad $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        return Filter::of($this, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return Implementation<U>
     */
    #[\Override]
    public function map(callable $map): Implementation
    {
        return Map::of($this, $map);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): Frame<U> $map
     *
     * @return Implementation<U>
     */
    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return FlatMap::of($this, $map);
    }
}
