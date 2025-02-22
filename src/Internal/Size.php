<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\Internal\Exception\SizeCantBeNegative;

/**
 * @psalm-immutable
 */
final class Size
{
    /** @var int<0, max> */
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new SizeCantBeNegative((string) $value);
        }

        $this->value = $value;
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @return int<0, max>
     */
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
