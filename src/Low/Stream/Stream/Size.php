<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Stream;

use Innmind\IO\Low\Stream\Exception\SizeCantBeNegative;

/**
 * @psalm-immutable
 */
final class Size
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new SizeCantBeNegative((string) $value);
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function unit(): Size\Unit
    {
        return Size\Unit::for($this->value);
    }

    public function toString(): string
    {
        return Size\Unit::format($this->value);
    }
}
