<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Frame;

use Innmind\IO\Next\Frame;
use Innmind\Immutable\Maybe;

/**
 * @internal
 * @template T
 * @template U
 * @implements Implementation<U>
 */
final class FlatMap implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param Implementation<T> $frame
     * @param \Closure(T): Frame<U> $map
     */
    private function __construct(
        private Implementation $frame,
        private \Closure $map,
    ) {
    }

    #[\Override]
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        $map = $this->map;

        /** @psalm-suppress MixedArgument */
        return ($this->frame)($read, $readLine)->flatMap(
            static fn($value) => $map($value)($read, $readLine),
        );
    }

    /**
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param Implementation<A> $frame
     * @param callable(A): Frame<B> $map
     *
     * @return self<A, B>
     */
    public static function of(Implementation $frame, callable $map): self
    {
        return new self($frame, \Closure::fromCallable($map));
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(U): bool $predicate
     *
     * @return Implementation<U>
     */
    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        return Filter::of($this, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @template V
     *
     * @param callable(U): V $map
     *
     * @return Implementation<V>
     */
    #[\Override]
    public function map(callable $map): Implementation
    {
        return Map::of($this, $map);
    }

    /**
     * @psalm-mutation-free
     *
     * @template V
     *
     * @param callable(U): Frame<V> $map
     *
     * @return Implementation<V>
     */
    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return self::of($this, $map);
    }
}
