<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\{
    IO as Previous,
    Internal\Capabilities,
};
use Innmind\TimeContinuum\ElapsedPeriod;

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
            Previous::of(static fn(?ElapsedPeriod $timeout) => match ($timeout) {
                null => $capabilities->watch()->waitForever(),
                default => $capabilities->watch()->timeoutAfter($timeout),
            }),
            $capabilities,
        );
    }

    public function files(): Files
    {
        return Files::of($this->io, $this->capabilities);
    }

    public function streams(): Streams
    {
        return Streams::of($this->io, $this->capabilities);
    }

    public function sockets(): Sockets
    {
        return Sockets::of($this->io, $this->capabilities);
    }
}
