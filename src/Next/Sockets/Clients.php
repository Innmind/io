<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets;

use Innmind\IO\{
    Next\Sockets\Clients\Client,
    Next\Sockets\Internet\Transport,
    Next\Sockets\Unix\Address,
    IO as Previous,
    Internal,
    Internal\Stream\Streams as Capabilities,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Maybe;

final class Clients
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

    /**
     * @return Maybe<Client>
     */
    public function internet(Transport $transport, Authority $authority): Maybe
    {
        return Internal\Socket\Client\Internet::of($transport, $authority)
            ->map($this->io->sockets()->clients()->wrap(...))
            ->map(Client::of(...));
    }

    /**
     * @return Maybe<Client>
     */
    public function unix(Address $address): Maybe
    {
        return Internal\Socket\Client\Unix::of($address)
            ->map($this->io->sockets()->clients()->wrap(...))
            ->map(Client::of(...));
    }
}
