<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\Stream;

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

    public function new(): Stream
    {
        return Stream::of(\fopen('php://temp', 'r+'));
    }
}
