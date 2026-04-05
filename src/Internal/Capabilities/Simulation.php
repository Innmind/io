<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\{
    Internal\Watch,
    Simulation\Disk,
};

/**
 * @internal
 */
final class Simulation implements Implementation
{
    private function __construct(
        private Implementation $capabilities,
        private Disk $disk,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Implementation $capabilities, Disk $disk): self
    {
        return new self($capabilities, $disk);
    }

    #[\Override]
    public function files(): Files
    {
        return Files::simulation(
            $this->capabilities->files(),
            $this->disk,
        );
    }

    #[\Override]
    public function streams(): Streams
    {
        return $this->capabilities->streams();
    }

    #[\Override]
    public function sockets(Files $files): Sockets
    {
        return $this->capabilities->sockets($files);
    }

    #[\Override]
    public function watch(): Watch
    {
        return $this->capabilities->watch();
    }
}
