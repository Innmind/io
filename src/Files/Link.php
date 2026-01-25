<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

final class Link
{
    private function __construct(
    ) {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }
}
