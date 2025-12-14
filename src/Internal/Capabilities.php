<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\Internal\Capabilities\{
    Implementation,
    AmbientAuthority,
    Async,
    Simulation,
};
use Innmind\TimeContinuum\Clock;

/**
 * @internal
 */
final class Capabilities
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @internal
     */
    public static function fromAmbientAuthority(): self
    {
        return new self(AmbientAuthority::of());
    }

    /**
     * @internal
     */
    public static function async(self $capabilities, Clock $clock): self
    {
        return new self(Async::of(
            $capabilities->implementation,
            $clock,
        ));
    }

    /**
     * @internal
     */
    public static function simulation(self $capabilities): self
    {
        return new self(Simulation::of($capabilities->implementation));
    }

    public function files(): Capabilities\Files
    {
        return $this->implementation->files();
    }

    public function streams(): Capabilities\Streams
    {
        return $this->implementation->streams();
    }

    public function sockets(): Capabilities\Sockets
    {
        return $this->implementation->sockets();
    }

    public function watch(): Watch
    {
        return $this->implementation->watch();
    }
}
