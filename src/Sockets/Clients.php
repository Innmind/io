<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\IO\{
    Sockets\Clients\Client,
    Sockets\Internet\Transport,
    Sockets\Unix\Address,
    Streams\Stream,
    Internal\Capabilities,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Attempt;

final class Clients
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

    /**
     * @return Attempt<Client>
     */
    public function internet(Transport $transport, Authority $authority): Attempt
    {
        return $this
            ->capabilities
            ->sockets()
            ->clients()
            ->internet($transport, $authority)
            ->map(fn($socket) => Client::of(
                Stream::of(
                    $this->capabilities,
                    $socket,
                ),
            ));
    }

    /**
     * @return Attempt<Client>
     */
    public function unix(Address $address): Attempt
    {
        return $this
            ->capabilities
            ->sockets()
            ->clients()
            ->unix($address)
            ->map(fn($socket) => Client::of(
                Stream::of(
                    $this->capabilities,
                    $socket,
                ),
            ));
    }
}
