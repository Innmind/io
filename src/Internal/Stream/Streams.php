<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

final class Streams
{
    private function __construct()
    {
    }

    public static function fromAmbientAuthority(): self
    {
        return new self;
    }

    public function temporary(): Streams\Temporary
    {
        return Streams\Temporary::of();
    }

    public function readable(): Streams\Readable
    {
        return Streams\Readable::of();
    }

    public function writable(): Streams\Writable
    {
        return Streams\Writable::of();
    }

    public function watch(): Streams\Watch
    {
        return Streams\Watch::of();
    }
}
