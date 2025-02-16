<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Stream\Position;

/**
 * @psalm-immutable
 */
enum Mode
{
    case fromStart;
    case fromCurrentPosition;

    public function toInt(): int
    {
        return match ($this) {
            self::fromStart => \SEEK_SET,
            self::fromCurrentPosition => \SEEK_CUR,
        };
    }
}
