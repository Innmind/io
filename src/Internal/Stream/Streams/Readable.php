<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\{
    Capabilities,
    Readable as Read,
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
        return Read\Stream::open($path);
    }

    #[\Override]
    public function acquire($resource): Read
    {
        return Read\Stream::of($resource);
    }
}
