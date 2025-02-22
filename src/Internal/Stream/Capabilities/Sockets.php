<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

final class Sockets
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    public function clients(): Sockets\Clients
    {
        return Sockets\Clients::of();
    }

    public function servers(): Sockets\Servers
    {
        return Sockets\Servers::of();
    }
}
