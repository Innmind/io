<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Frame;

use Innmind\IO\Next\Frame;
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

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        // todo apply a filter on the read chunk to make sure it's of the
        // expected size. But this needs to make sure the chunk encoding and
        // the size are compatible
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
    public function flatMap(callable $map): Implementation
    {
        return FlatMap::of($this, $map);
    }
}
