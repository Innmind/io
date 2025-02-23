<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Servers\Server;

use Innmind\IO\{
    Sockets\Servers\Server,
    Sockets\Clients\Client,
    Streams\Stream,
    Internal\Capabilities,
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
        private Capabilities $capabilities,
        private Watch $watch,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Capabilities $capabilities, Watch $watch): self
    {
        return new self($capabilities, $watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function with(Server $server): self
    {
        // todo automatically determine the shortest timeout to watch for
        return new self(
            $this->capabilities,
            $this->watch->forRead($server->unwrap()),
        );
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
            ->map(fn($socket) => Client::of(
                Stream::of(
                    $this->capabilities,
                    $socket,
                ),
            ));
    }
}
