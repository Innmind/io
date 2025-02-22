<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\{
    Capabilities,
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
    public function open(Path $path): Implementation
    {
        return Implementation::of(\fopen($path->toString(), 'r'));
    }

    #[\Override]
    public function acquire($resource): Implementation
    {
        return Implementation::of($resource);
    }
}
