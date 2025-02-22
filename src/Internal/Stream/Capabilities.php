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

    public function files(): Capabilities\Files
    {
        return Capabilities\Files::of();
    }

    public function streams(): Capabilities\Streams
    {
        return Capabilities\Streams::of();
    }

    public function watch(): Capabilities\Watch
    {
        return Capabilities\Watch::of();
    }
}
