<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\{
    Next\Sockets\Clients,
    Next\Sockets\Clients\Client,
    Next\Sockets\Servers,
    IO as Previous,
    Internal\Capabilities,
};

final class Sockets
{
    private function __construct(
        private Previous $io,
        private Capabilities $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $io, Capabilities $capabilities): self
    {
        return new self($io, $capabilities);
    }

    public function clients(): Clients
    {
        return Clients::of($this->io, $this->capabilities);
    }

    public function servers(): Servers
    {
        return Servers::of($this->io, $this->capabilities);
    }

    /**
     * @return array{Client, Client}
     */
    public function pair(): array
    {
        // todo
        /** @var array{Client, Client} */
        return [];
    }
}
