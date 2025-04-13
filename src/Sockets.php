<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Sockets\Clients,
    Sockets\Clients\Client,
    Sockets\Servers,
    Internal\Capabilities,
};
use Innmind\Immutable\Attempt;

final class Sockets
{
    private function __construct(
        private Capabilities $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
    ): self {
        return new self($capabilities);
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
     * @return Attempt<array{Client, Client}>
     */
    public function pair(): Attempt
    {
        return $this->capabilities->sockets()->pair()->map(
            fn($pair) => [
                Client::of(Streams\Stream::of(
                    $this->capabilities,
                    $pair[0],
                )),
                Client::of(Streams\Stream::of(
                    $this->capabilities,
                    $pair[1],
                )),
            ],
        );
    }
}
