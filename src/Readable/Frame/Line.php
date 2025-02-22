<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @implements Frame<Str>
 */
final class Line implements Frame
{
    /**
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }

    #[\Override]
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
     * @return Frame<Str>
     */
    #[\Override]
    public function filter(callable $predicate): Frame
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
     * @return Frame<U>
     */
    #[\Override]
    public function map(callable $map): Frame
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
     * @return Frame<U>
     */
    #[\Override]
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
