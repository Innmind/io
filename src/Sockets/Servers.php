<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\IO\{
    Sockets\Servers\Server,
    Sockets\Internet\Transport,
    Sockets\Unix\Address,
    Internal\Capabilities,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\Attempt;

final class Servers
{
    private function __construct(
        private Capabilities $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Capabilities $capabilities): self
    {
        return new self($capabilities);
    }

    /**
     * @return Attempt<Server>
     */
    public function internet(Transport $transport, IP $ip, Port $port): Attempt
    {
        return $this
            ->capabilities
            ->sockets()
            ->servers()
            ->internet($transport, $ip, $port)
            ->map(fn($socket) => Server::of(
                $this->capabilities,
                $this->capabilities->watch(),
                $socket,
            ));
    }

    /**
     * @return Attempt<Server>
     */
    public function unix(Address $address): Attempt
    {
        return $this
            ->capabilities
            ->sockets()
            ->servers()
            ->unix($address)
            ->map(fn($socket) => Server::of(
                $this->capabilities,
                $this->capabilities->watch(),
                $socket,
            ));
    }

    /**
     * @return Attempt<Server>
     */
    public function takeOver(Address $address): Attempt
    {
        return $this
            ->capabilities
            ->sockets()
            ->servers()
            ->takeOver($address)
            ->map(fn($socket) => Server::of(
                $this->capabilities,
                $this->capabilities->watch(),
                $socket,
            ));
    }
}
