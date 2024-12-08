<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\Next\Streams\Stream;

final class Streams
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

    /**
     * @param resource $resource
     */
    public function acquire($resource): Stream
    {
        return Stream::of($resource);
    }
}
