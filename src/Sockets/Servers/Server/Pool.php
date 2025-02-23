<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Servers\Server;

use Innmind\IO\{
    Sockets\Servers\Server,
    Sockets\Clients\Client,
    Previous\Sockets\Client as Previous,
    Internal\Socket\Server as Socket,
    Internal\Watch,
};
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

final class Pool
{
    private function __construct(
        private Watch $watch,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Watch $watch): self
    {
        return new self($watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function with(Server $server): self
    {
        // todo automatically determine the shortest timeout to watch for
        return new self($this->watch->forRead($server->internal()->unwrap()));
    }

    /**
     * @return Sequence<Client>
     */
    public function accept(): Sequence
    {
        // todo remove when shortest timeout is automatically determined
        $watch = $this->watch->waitForever();

        return $watch()
            ->toSequence()
            ->flatMap(
                static fn($ready) => $ready
                    ->toRead()
                    ->keep(Instance::of(Socket::class)),
            )
            ->flatMap(
                static fn($socket) => $socket
                    ->accept()
                    ->toSequence(),
            )
            ->map(fn($client) => Previous::of(
                $this->watch->clear(),
                $client,
            ))
            ->map(Client::of(...));
    }
}
