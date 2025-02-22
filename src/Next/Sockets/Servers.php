<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets;

use Innmind\IO\{
    Next\Sockets\Servers\Server,
    Next\Sockets\Internet\Transport,
    Next\Sockets\Unix\Address,
    IO as Previous,
    Internal,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\Maybe;

final class Servers
{
    private function __construct(
        private Previous $io,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $io): self
    {
        return new self($io);
    }

    /**
     * @return Maybe<Server>
     */
    public function internet(Transport $transport, IP $ip, Port $port): Maybe
    {
        return Internal\Socket\Server\Internet::of($transport, $ip, $port)
            ->map($this->io->sockets()->servers()->wrap(...))
            ->map(Server::of(...));
    }

    /**
     * @return Maybe<Server>
     */
    public function unix(Address $address): Maybe
    {
        return Internal\Socket\Server\Unix::of($address)
            ->map($this->io->sockets()->servers()->wrap(...))
            ->map(Server::of(...));
    }

    /**
     * @return Maybe<Server>
     */
    public function takeOver(Address $address): Maybe
    {
        return Internal\Socket\Server\Unix::recoverable($address)
            ->map($this->io->sockets()->servers()->wrap(...))
            ->map(Server::of(...));
    }
}
