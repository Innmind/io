<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\Stream\Readable as LowLevelStream;

final class Readable
{
    private function __construct()
    {
    }

    public static function of(): self
    {
        return new self;
    }

    /**
     * @psalm-mutation-free
     */
    public function wrap(LowLevelStream $stream): Readable\Stream
    {
        return Readable\Stream::of($stream);
    }
}
