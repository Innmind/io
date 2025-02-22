<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\{
    Capabilities,
    Readable as Read,
    Implementation,
};
use Innmind\Url\Path;

final class Readable implements Capabilities\Readable
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

    #[\Override]
    public function open(Path $path): Read
    {
        return Implementation::of(\fopen($path->toString(), 'r'));
    }

    #[\Override]
    public function acquire($resource): Read
    {
        return Implementation::of($resource);
    }
}
