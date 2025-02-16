<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\{
    Capabilities,
    Writable as Write,
};
use Innmind\Url\Path;

final class Writable implements Capabilities\Writable
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

    public function open(Path $path): Write
    {
        return Write\Stream::of(\fopen($path->toString(), 'w'));
    }

    public function acquire($resource): Write
    {
        return Write\Stream::of($resource);
    }
}
