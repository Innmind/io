<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\Implementation;
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

    public function open(Path $path): Implementation
    {
        return Implementation::of(\fopen($path->toString(), 'w'));
    }

    /**
     * @param resource $resource
     */
    public function acquire($resource): Implementation
    {
        return Implementation::of($resource);
    }
}
