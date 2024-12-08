<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\Next\Sockets\{
    Clients,
    Clients\Client,
    Servers,
};

final class Sockets
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

    public function clients(): Clients
    {
        return Clients::of();
    }

    public function servers(): Servers
    {
        return Servers::of();
    }

    /**
     * @return array{Client, Client}
     */
    public function pair(): array
    {
        /** @var array{Client, Client} */
        return [];
    }
}
