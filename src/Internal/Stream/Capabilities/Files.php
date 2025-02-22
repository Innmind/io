<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\Stream;
use Innmind\Url\Path;

final class Files
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

    public function read(Path $path): Stream
    {
        return Stream::of(\fopen($path->toString(), 'r'));
    }

    public function write(Path $path): Stream
    {
        return Stream::of(\fopen($path->toString(), 'w'));
    }

    public function temporary(): Stream
    {
        return Stream::of(\fopen('php://temp', 'r+'));
    }

    /**
     * @param resource $resource
     */
    public function acquire($resource): Stream
    {
        return Stream::of($resource);
    }
}
