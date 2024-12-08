<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets;

use Innmind\IO\Next\Sockets\{
    Servers\Server,
    Internet\Transport,
    Unix\Address,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\Maybe;

final class Servers
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
     * @return Maybe<Server>
     */
    public function internet(Transport $transport, IP $ip, Port $port): Maybe
    {
        /** @var Maybe<Server> */
        return Maybe::nothing();
    }

    /**
     * @return Maybe<Server>
     */
    public function unix(Address $address): Maybe
    {
        /** @var Maybe<Server> */
        return Maybe::nothing();
    }

    /**
     * @return Maybe<Server>
     */
    public function takeOver(Address $address): Maybe
    {
        /** @var Maybe<Server> */
        return Maybe::nothing();
    }
}
