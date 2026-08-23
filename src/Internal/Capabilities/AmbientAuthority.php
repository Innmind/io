<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Watch;
use Innmind\Time\Clock;

/**
 * @internal
 */
final class AmbientAuthority implements Implementation
{
    private function __construct(
    ) {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function asAsync(Clock $clock): Implementation
    {
        return Async::of($this, $clock);
    }

    #[\Override]
    public function files(): Files
    {
        return Files::fromAmbientAuthority();
    }

    #[\Override]
    public function streams(): Streams
    {
        return Streams::of();
    }

    #[\Override]
    public function sockets(Files $files): Sockets
    {
        return Sockets::of($files);
    }

    #[\Override]
    public function watch(): Watch
    {
        return Watch::sync();
    }
}
