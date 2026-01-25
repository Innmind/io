<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Watch;
use Innmind\Time\Clock;

/**
 * @internal
 */
final class Async implements Implementation
{
    private function __construct(
        private Implementation $capabilities,
        private Clock $clock,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Implementation $capabilities, Clock $clock): self
    {
        return new self($capabilities, $clock);
    }

    #[\Override]
    public function files(): Files
    {
        return $this->capabilities->files();
    }

    #[\Override]
    public function streams(): Streams
    {
        return $this->capabilities->streams();
    }

    #[\Override]
    public function sockets(): Sockets
    {
        return $this->capabilities->sockets();
    }

    #[\Override]
    public function watch(): Watch
    {
        return Watch::async($this->clock);
    }
}
