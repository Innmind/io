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
final class Line implements Implementation
{
    /**
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return $readLine();
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
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
