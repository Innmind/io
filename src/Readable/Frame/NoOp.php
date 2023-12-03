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
    private function __construct(int $value)
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
     */
    public function filter(callable $predicate): Frame
    {
        return Filter::of($this, $predicate);
    }

    /**
     * @psalm-mutation-free
     */
    public function map(callable $map): Frame
    {
        return Map::of($this, $map);
    }

    /**
     * @psalm-mutation-free
     */
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
