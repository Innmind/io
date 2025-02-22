<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\Stream;

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
