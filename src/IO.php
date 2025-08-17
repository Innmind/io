<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\Internal\Capabilities;
use Innmind\TimeContinuum\Clock;

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

    /**
     * This is an internal feature for the innmind/async package.
     *
     * @internal
     */
    public static function async(Clock $clock): self
    {
        return new self(Capabilities::async($clock));
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
