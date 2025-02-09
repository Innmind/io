<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Stream;

use Innmind\IO\Low\Stream\Exception\PositionCantBeNegative;

/**
 * @psalm-immutable
 */
final class Position
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new PositionCantBeNegative((string) $value);
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
