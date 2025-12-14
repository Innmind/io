<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Watch;

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
    public function sockets(): Sockets
    {
        return Sockets::of();
    }

    #[\Override]
    public function watch(): Watch
    {
        return Watch::sync();
    }
}
