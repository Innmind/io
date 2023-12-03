<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * Use this frame to hardcode a value inside a frame composition
 *
 * @template T
 * @implements Frame<T>
 */
final class NoOp implements Frame
{
    /** @var T */
    private mixed $value;

    /**
     * @psalm-mutation-free
     *
     * @param T $value
     */
    private function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return Maybe::just($this->value);
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return Frame<T>
     */
    public function filter(callable $predicate): Frame
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
     * @return Frame<U>
     */
    public function map(callable $map): Frame
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
     * @return Frame<U>
     */
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
