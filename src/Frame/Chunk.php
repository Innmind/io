<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Frame;
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @internal
 * @implements Implementation<Str>
 */
final class Chunk implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param int<1, max> $size
     */
    private function __construct(
        private int $size,
    ) {
    }

    #[\Override]
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return $read($this->size);
    }

    /**
     * @psalm-pure
     *
     * @param int<1, max> $size
     */
    public static function of(int $size): self
    {
        return new self($size);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Str): bool $predicate
     *
     * @return Implementation<Str>
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
     * @param callable(Str): U $map
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
     * @param callable(Str): Frame<U> $map
     *
     * @return Implementation<U>
     */
    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return FlatMap::of($this, $map);
    }
}
