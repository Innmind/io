<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Stream;

final class Size
{
    /** @param int<0, max> $value */
    private function __construct(
        private int $value,
    ) {
        // todo express units (bytes, kilobytes, etc...)
    }

    /**
     * @param int<0, max> $value
     */
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
}
