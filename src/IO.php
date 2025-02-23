<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\Internal\Capabilities;

final class IO
{
    private function __construct(
        private Capabilities $capabilities,
    ) {
    }

    public static function fromAmbientAuthority(): self
    {
        return new self(Capabilities::fromAmbientAuthority());
    }

    public function files(): Files
    {
        return Files::of($this->capabilities);
    }

    public function streams(): Streams
    {
        return Streams::of($this->capabilities);
    }

    public function sockets(): Sockets
    {
        return Sockets::of($this->capabilities);
    }
}
