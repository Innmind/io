<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

final class IO
{
    private function __construct()
    {
    }

    public static function fromAmbientAuthority(): self
    {
        return new self;
    }

    public function files(): Files
    {
        return Files::of();
    }

    public function streams(): Streams
    {
        return Streams::of();
    }

    public function sockets(): Sockets
    {
        return Sockets::of();
    }
}
