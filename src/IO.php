<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Previous\IO as Previous,
    Internal\Capabilities,
};

final class IO
{
    private function __construct(
        private Previous $io,
        private Capabilities $capabilities,
    ) {
    }

    public static function fromAmbientAuthority(): self
    {
        $capabilities = Capabilities::fromAmbientAuthority();

        return new self(
            Previous::of($capabilities->watch()),
            $capabilities,
        );
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
        return Sockets::of($this->io, $this->capabilities);
    }
}
