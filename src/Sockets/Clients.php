<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\IO\{
    Sockets\Clients\Client,
    Sockets\Internet\Transport,
    Sockets\Unix\Address,
    Previous\IO as Previous,
    Internal\Capabilities,
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
        return $this
            ->capabilities
            ->sockets()
            ->clients()
            ->internet($transport, $authority)
            ->map($this->io->sockets()->clients()->wrap(...))
            ->map(Client::of(...));
    }

    /**
     * @return Maybe<Client>
     */
    public function unix(Address $address): Maybe
    {
        return $this
            ->capabilities
            ->sockets()
            ->clients()
            ->unix($address)
            ->map($this->io->sockets()->clients()->wrap(...))
            ->map(Client::of(...));
    }
}
