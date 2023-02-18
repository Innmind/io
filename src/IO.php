<?php
declare(strict_types = 1);

namespace Innmind\IO;

final class IO
{
    private function __construct()
    {
    }

    public static function of(): self
    {
        return new self;
    }

    public function readable(): Readable
    {
        return Readable::of();
    }
}
