<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Watch;

/**
 * @internal
 */
final class Simulation implements Implementation
{
    private function __construct(
        private Implementation $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Implementation $capabilities): self
    {
        return new self($capabilities);
    }

    #[\Override]
    public function files(): Files
    {
        return Files::simulation($this->capabilities->files());
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
        return $this->capabilities->watch();
    }
}
