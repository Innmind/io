<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Stream;

/**
 * @internal
 */
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
