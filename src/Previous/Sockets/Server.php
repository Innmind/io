<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets;

use Innmind\TimeContinuum\ElapsedPeriod;
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
    /** @var callable(Socket): Maybe<Socket> */
    private $wait;

    /**
     * @psalm-mutation-free
     *
     * @param callable(Socket): Maybe<Socket> $wait
     */
    private function __construct(
        Watch $watch,
        Socket $socket,
        callable $wait,
    ) {
        $this->watch = $watch;
        $this->socket = $socket;
        $this->wait = $wait;
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
            $watch,
            $socket,
            static fn(Socket $socket) => Maybe::just($socket),
        );
    }

    public function with(self $socket): Server\Pool
    {
        return Server\Pool::of($this->watch, $this->socket, $socket->unwrap());
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
            fn(Socket $socket) => $this
                ->watch
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Socket::class)),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(ElapsedPeriod $timeout): self
    {
        return new self(
            $this->watch->timeoutAfter($timeout->asPeriod()),
            $this->socket,
            fn(Socket $socket) => $this
                ->watch
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Socket::class)),
        );
    }

    /**
     * @return Maybe<Client>
     */
    public function accept(): Maybe
    {
        return ($this->wait)($this->socket)
            ->flatMap(static fn($socket) => $socket->accept())
            ->map(fn($client) => Client::of(
                $this->watch,
                $client,
            ));
    }
}
