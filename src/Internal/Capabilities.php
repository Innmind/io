<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\TimeContinuum\Clock;

/**
 * @internal
 */
final class Capabilities
{
    /**
     * The async nature is determined by the presence of the clock only as the
     * sync implementation don't need one. And having a separate bool flag would
     * not be understood by Psalm.
     */
    private function __construct(
        private ?Clock $clock,
    ) {
    }

    /**
     * @internal
     */
    public static function fromAmbientAuthority(): self
    {
        return new self(null);
    }

    /**
     * @internal
     */
    public static function async(Clock $clock): self
    {
        return new self($clock);
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
        return match ($this->clock) {
            null => Watch::sync(),
            default => Watch::async($this->clock),
        };
    }
}
