<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets\Server;

use Innmind\IO\Previous\Sockets\{
    Server,
    Client,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\IO\Internal\Socket\{
    Server as Socket,
};
use Innmind\IO\Internal\Watch;
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

/**
 * @template-covariant T of Socket
 */
final class Pool
{
    /** @var non-empty-list<T> */
    private array $sockets;
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;
    /** @var callable(T): Sequence<T> */
    private $wait;

    /**
     * @psalm-mutation-free
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param non-empty-list<T> $sockets
     * @param callable(T): Sequence<T> $wait
     */
    private function __construct(
        callable $watch,
        array $sockets,
        callable $wait,
    ) {
        $this->watch = $watch;
        $this->sockets = $sockets;
        $this->wait = $wait;
    }

    /**
     * @psalm-mutation-free
     * @internal
     * @template A of Socket
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param A $first
     * @param A $second
     *
     * @return self<A>
     */
    public static function of(
        callable $watch,
        Socket $first,
        Socket $second,
    ): self {
        /** @var self<A> */
        return new self(
            $watch,
            [$first, $second],
            static fn(Socket $socket) => Sequence::of($socket),
        );
    }

    /**
     * @param Server<T> $server
     *
     * @return self<T>
     */
    public function with(Server $server): self
    {
        return new self(
            $this->watch,
            [...$this->sockets, $server->unwrap()],
            $this->wait,
        );
    }

    /**
     * @return Sequence<T>
     */
    public function unwrap(): Sequence
    {
        return Sequence::of(...$this->sockets);
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
            $this->sockets,
            fn(Socket $socket, Socket ...$sockets) => ($this->watch)(null)
                ->forRead($socket, ...$sockets)()
                ->map(
                    static fn($ready) => $ready
                        ->toRead()
                        ->filter(static fn($ready) => \in_array(
                            $ready,
                            [$socket, ...$sockets],
                            true,
                        ))
                        ->keep(Instance::of(Socket::class)),
                )
                ->toSequence()
                ->flatMap(
                    static fn($toRead) => Sequence::of(...$toRead->toList()),
                ),
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
            $this->sockets,
            fn(Socket $socket, Socket ...$sockets) => ($this->watch)($timeout)
                ->forRead($socket, ...$sockets)()
                ->map(
                    static fn($ready) => $ready
                        ->toRead()
                        ->filter(static fn($ready) => \in_array(
                            $ready,
                            [$socket, ...$sockets],
                            true,
                        ))
                        ->keep(Instance::of(Socket::class)),
                )
                ->toSequence()
                ->flatMap(
                    static fn($toRead) => Sequence::of(...$toRead->toList()),
                ),
        );
    }

    /**
     * @return Sequence<Client>
     */
    public function accept(): Sequence
    {
        return ($this->wait)(...$this->sockets)
            ->flatMap(
                static fn($socket) => $socket
                    ->accept()
                    ->toSequence(),
            )
            ->map(fn($client) => Client::of(
                $this->watch,
                $client,
            ));
    }
}
