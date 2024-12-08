<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Servers\Server;

use Innmind\IO\Next\Sockets\{
    Servers\Server,
    Clients\Client,
};
use Innmind\Immutable\Sequence;

final class Pool
{
    /**
     * @param Sequence<Server> $servers
     */
    private function __construct(
        private Sequence $servers,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Server $a, Server $b): self
    {
        return new self(Sequence::of($a, $b));
    }

    /**
     * @psalm-mutation-free
     */
    public function with(Server $server): self
    {
        // todo automatically determine the shortest timeout to watch for
        return new self(($this->servers)($server));
    }

    /**
     * @return Sequence<Client>
     */
    public function accept(): Sequence
    {
        return Sequence::of();
    }
}
