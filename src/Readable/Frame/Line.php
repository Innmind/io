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
