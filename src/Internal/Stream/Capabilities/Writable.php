<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\Stream;
use Innmind\Url\Path;

final class Writable
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

    public function open(Path $path): Stream
    {
        return Stream::of(\fopen($path->toString(), 'w'));
    }

    /**
     * @param resource $resource
     */
    public function acquire($resource): Stream
    {
        return Stream::of($resource);
    }
}
