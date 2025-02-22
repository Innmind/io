<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets\Server;

use Innmind\IO\Previous\Sockets\{
    Server,
    Client,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\IO\Internal\Socket\Server as Socket;
use Innmind\IO\Internal\Watch;
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

final class Pool
{
    /** @var non-empty-list<Socket> */
    private array $sockets;
    private Watch $watch;
    /** @var callable(Socket): Sequence<Socket> */
    private $wait;

    /**
     * @psalm-mutation-free
     *
     * @param non-empty-list<Socket> $sockets
     * @param callable(Socket): Sequence<Socket> $wait
     */
    private function __construct(
        Watch $watch,
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
     */
    public static function of(
        Watch $watch,
        Socket $first,
        Socket $second,
    ): self {
        return new self(
            $watch,
            [$first, $second],
            static fn(Socket $socket) => Sequence::of($socket),
        );
    }

    public function with(Server $server): self
    {
        return new self(
            $this->watch,
            [...$this->sockets, $server->unwrap()],
            $this->wait,
        );
    }

    /**
     * @return Sequence<Socket>
     */
    public function unwrap(): Sequence
    {
        return Sequence::of(...$this->sockets);
    }

    /**
     * Wait forever for the socket to be ready to read before tryin to use it
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        /** @var self<T> */
        return new self(
            $this->watch->waitForever(),
            $this->sockets,
            fn(Socket $socket, Socket ...$sockets) => $this
                ->watch
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
     */
    public function timeoutAfter(ElapsedPeriod $timeout): self
    {
        return new self(
            $this->watch->timeoutAfter($timeout->asPeriod()),
            $this->sockets,
            fn(Socket $socket, Socket ...$sockets) => $this
                ->watch
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
