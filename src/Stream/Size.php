<?php
declare(strict_types = 1);

namespace Innmind\IO\Stream;

/**
 * @psalm-immutable
 */
final class Size
{
    /** @param int<0, max> $value */
    private function __construct(
        private int $value,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param int<0, max> $value
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    public function lessThan(self $size): bool
    {
        return $this->value < $size->value;
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
