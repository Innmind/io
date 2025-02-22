<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

final class Streams implements Capabilities
{
    private function __construct()
    {
    }

    public static function fromAmbientAuthority(): self
    {
        return new self;
    }

    #[\Override]
    public function temporary(): Streams\Temporary
    {
        return Streams\Temporary::of();
    }

    #[\Override]
    public function readable(): Streams\Readable
    {
        return Streams\Readable::of();
    }

    #[\Override]
    public function writable(): Streams\Writable
    {
        return Streams\Writable::of();
    }

    #[\Override]
    public function watch(): Streams\Watch
    {
        return Streams\Watch::of();
    }
}
