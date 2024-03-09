<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Socket\{
    Server as Socket,
    Server\Connection,
};
use Innmind\Stream\Watch;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @template-covariant T of Socket
 */
final class Server
{
    /** @var T */
    private Socket $socket;
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;
    /** @var callable(T): Maybe<T> */
    private $wait;

    /**
     * @psalm-mutation-free
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param T $socket
     * @param callable(T): Maybe<T> $wait
     */
    private function __construct(
        callable $watch,
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
     * @template A of Socket
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param A $socket
     *
     * @return self<A>
     */
    public static function of(
        callable $watch,
        Socket $socket,
    ): self {
        /** @var self<A> */
        return new self(
            $watch,
            $socket,
            static fn(Socket $socket) => Maybe::just($socket),
        );
    }

    /**
     * @return T
     */
    public function unwrap(): Socket
    {
        return $this->socket;
    }

    /**
     * Wait forever for the socket to be ready to read before tryin to use it
     *
     * @psalm-mutation-free
     *
     * @return self<T>
     */
    public function watch(): self
    {
        /** @var self<T> */
        return new self(
            $this->watch,
            $this->socket,
            fn(Socket $socket) => ($this->watch)(null)
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
     *
     * @return self<T>
     */
    public function timeoutAfter(ElapsedPeriod $timeout): self
    {
        /** @var self<T> */
        return new self(
            $this->watch,
            $this->socket,
            fn(Socket $socket) => ($this->watch)($timeout)
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Socket::class)),
        );
    }

    /**
     * @return Maybe<Client<Connection>>
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
