<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets;

use Innmind\TimeContinuum\Period;
use Innmind\IO\Internal\Socket\{
    Server as Socket,
};
use Innmind\IO\Internal\Watch;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

final class Server
{
    private Socket $socket;
    private Watch $watch;

    /**
     * @psalm-mutation-free
     */
    private function __construct(
        Watch $watch,
        Socket $socket,
    ) {
        $this->watch = $watch;
        $this->socket = $socket;
    }

    /**
     * @psalm-mutation-free
     * @internal
     */
    public static function of(
        Watch $watch,
        Socket $socket,
    ): self {
        return new self(
            $watch->forRead($socket),
            $socket,
        );
    }

    public function with(self $socket): Server\Pool
    {
        return Server\Pool::of(
            $this->watch->unwatch($this->socket),
            $this->socket,
            $socket->unwrap(),
        );
    }

    public function unwrap(): Socket
    {
        return $this->socket;
    }

    /**
     * Wait forever for the socket to be ready to read before tryin to use it
     *
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
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->watch->timeoutAfter($timeout),
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
            ->map(fn($client) => Client::of(
                $this->watch->clear(),
                $client,
            ));
    }
}
