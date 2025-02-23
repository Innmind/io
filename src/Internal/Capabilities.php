<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

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

    public function sockets(): Capabilities\Sockets
    {
        return Capabilities\Sockets::of();
    }

    public function watch(): Watch
    {
        return Watch\Select::new();
    }
}
