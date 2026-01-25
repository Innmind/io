<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\Internal\Capabilities;
use Innmind\Time\Clock;

final class IO
{
    private function __construct(
        private Capabilities $capabilities,
    ) {
    }

    #[\NoDiscard]
    public static function fromAmbientAuthority(): self
    {
        return new self(Capabilities::fromAmbientAuthority());
    }

    /**
     * This is an internal feature for the innmind/async package.
     *
     * @internal
     */
    #[\NoDiscard]
    public static function async(self $io, Clock $clock): self
    {
        return new self(Capabilities::async(
            $io->capabilities,
            $clock,
        ));
    }

    /**
     * This is an internal feature for the innmind/testing package.
     *
     * @internal
     */
    #[\NoDiscard]
    public static function simulation(
        self $io,
        Simulation\Disk $disk,
    ): self {
        return new self(Capabilities::simulation(
            $io->capabilities,
            $disk,
        ));
    }

    #[\NoDiscard]
    public function files(): Files
    {
        return Files::of($this->capabilities);
    }

    #[\NoDiscard]
    public function streams(): Streams
    {
        return Streams::of($this->capabilities);
    }

    #[\NoDiscard]
    public function sockets(): Sockets
    {
        return Sockets::of($this->capabilities);
    }
}
