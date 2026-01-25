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
    #[\NoDiscard]
    public static function of(int $value): self
    {
        return new self($value);
    }

    #[\NoDiscard]
    public function lessThan(self $size): bool
    {
        return $this->value < $size->value;
    }

    /**
     * @return int<0, max>
     */
    #[\NoDiscard]
    public function toInt(): int
    {
        return $this->value;
    }

    #[\NoDiscard]
    public function unit(): Size\Unit
    {
        return Size\Unit::for($this->value);
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return Size\Unit::format($this->value);
    }
}
