<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

final class Capabilities
{
    private function __construct()
    {
    }

    public static function fromAmbientAuthority(): self
    {
        return new self;
    }

    public function temporary(): Capabilities\Temporary
    {
        return Capabilities\Temporary::of();
    }

    public function readable(): Capabilities\Readable
    {
        return Capabilities\Readable::of();
    }

    public function writable(): Capabilities\Writable
    {
        return Capabilities\Writable::of();
    }

    public function watch(): Capabilities\Watch
    {
        return Capabilities\Watch::of();
    }
}
