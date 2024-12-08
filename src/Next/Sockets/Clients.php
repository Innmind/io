<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets;

use Innmind\IO\Next\Sockets\{
    Clients\Client,
    Internet\Transport,
    Unix\Address,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Maybe;

final class Clients
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

    /**
     * @return Maybe<Client>
     */
    public function internet(Transport $transport, Authority $authority): Maybe
    {
        /** @var Maybe<Client> */
        return Maybe::nothing();
    }

    /**
     * @return Maybe<Client>
     */
    public function unix(Address $address): Maybe
    {
        /** @var Maybe<Client> */
        return Maybe::nothing();
    }
}
