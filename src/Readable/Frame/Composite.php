<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Sequence;

final class Composite
{
    private function __construct()
    {
    }

    /**
     * @template T
     * @no-named-arguments
     * @psalm-pure
     *
     * @param callable(...mixed): T $map
     *
     * @return Frame<T>
     */
    public static function of(
        callable $map,
        Frame $frame,
        Frame ...$rest,
    ): Frame {
        return Sequence::of(...$rest)
            ->reduce(
                $frame->map(static fn($value) => [$value]),
                static fn(Frame $previous, $frame) => $previous->flatMap(
                    static fn(array $values) => $frame->map(
                        static fn($value) => \array_merge($values, [$value]),
                    ),
                ),
            )
            ->map(static fn($values) => $map(...$values));
    }
}
