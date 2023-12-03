<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Maybe;

/**
 * @template T
 * @template U
 * @implements Frame<U>
 */
final class FlatMap implements Frame
{
    /** @var Frame<T> */
    private Frame $frame;
    /** @var callable(T): Frame<U> */
    private $map;

    /**
     * @psalm-mutation-free
     *
     * @param Frame<T> $frame
     * @param callable(T): Frame<U> $map
     */
    private function __construct(Frame $frame, callable $map)
    {
        $this->frame = $frame;
        $this->map = $map;
    }

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        /** @psalm-suppress MixedArgument */
        return ($this->frame)($read, $readLine)->flatMap(
            fn($value) => ($this->map)($value)($read, $readLine),
        );
    }

    /**
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param Frame<A> $frame
     * @param callable(A): Frame<B> $map
     *
     * @return self<A, B>
     */
    public static function of(Frame $frame, callable $map): self
    {
        return new self($frame, $map);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(U): bool $predicate
     *
     * @return Frame<U>
     */
    public function filter(callable $predicate): Frame
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
     * @return Frame<V>
     */
    public function map(callable $map): Frame
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
     * @return Frame<V>
     */
    public function flatMap(callable $map): Frame
    {
        return new self($this, $map);
    }
}
