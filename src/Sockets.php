<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Sockets\Clients,
    Sockets\Clients\Client,
    Sockets\Servers,
    Previous\IO as Previous,
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
    public static function of(
        Previous $io,
        Capabilities $capabilities,
    ): self {
        return new self($io, $capabilities);
    }

    public function clients(): Clients
    {
        return Clients::of($this->capabilities);
    }

    public function servers(): Servers
    {
        return Servers::of($this->capabilities);
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
