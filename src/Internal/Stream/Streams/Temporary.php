<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\Implementation;

final class Temporary
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    public function new(): Implementation
    {
        return Implementation::of(\fopen('php://temp', 'r+'));
    }
}
