<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Watch;
use Innmind\Time\Clock;

/**
 * @internal
 */
interface Implementation
{
    /**
     * @psalm-mutation-free
     */
    #[\NoDiscard]
    public function asAsync(Clock $clock): self;
    public function files(): Files;
    public function streams(): Streams;
    public function sockets(Files $files): Sockets;
    public function watch(): Watch;
}
