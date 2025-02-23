<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Servers;

use Innmind\IO\{
    Sockets\Servers\Server\Pool,
    Sockets\Clients\Client,
    Previous\Sockets\Client as PreviousClient,
    Internal\Socket\Server as Socket,
    Internal\Watch,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
    Predicate\Instance,
};

final class Server
{
    private function __construct(
        private Watch $watch,
        private Socket $socket,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Watch $watch, Socket $socket): self
    {
        return new self($watch->forRead($socket), $socket);
    }

    /**
     * @internal
     */
    public function unwrap(): Socket
    {
        return $this->socket;
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->watch->waitForever(),
            $this->socket,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->watch->timeoutAfter($period),
            $this->socket,
        );
    }

    /**
     * @return Maybe<Client>
     */
    public function accept(): Maybe
    {
        $socket = $this->socket;

        return ($this->watch)()
            ->map(static fn($ready) => $ready->toRead())
            ->flatMap(static fn($toRead) => $toRead->find(
                static fn($ready) => $ready === $socket,
            ))
            ->keep(Instance::of(Socket::class))
            ->flatMap(static fn($socket) => $socket->accept())
            ->map(fn($client) => PreviousClient::of(
                $this->watch->clear(),
                $client,
            ))
            ->map(Client::of(...));
    }

    public function pool(self $server): Pool
    {
        return Pool::of($this->watch->forRead(
            $server->socket,
        ));
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return $this
            ->socket
            ->close()
            ->maybe();
    }
}
