<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets\Server;

use Innmind\IO\Previous\Sockets\{
    Server,
    Client,
};
use Innmind\TimeContinuum\Period;
use Innmind\IO\Internal\Socket\Server as Socket;
use Innmind\IO\Internal\Watch;
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

final class Pool
{
    /**
     * @psalm-mutation-free
     *
     * @param Sequence<Socket> $sockets
     */
    private function __construct(
        private Watch $watch,
        private Sequence $sockets,
    ) {
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
            $watch->forRead($first, $second),
            Sequence::of($first, $second),
        );
    }

    public function with(Server $server): self
    {
        return new self(
            $this->watch->forRead($server->unwrap()),
            $this->sockets->add($server->unwrap()),
        );
    }

    /**
     * @return Sequence<Socket>
     */
    public function unwrap(): Sequence
    {
        return $this->sockets;
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
            $this->sockets,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->watch->timeoutAfter($timeout),
            $this->sockets,
        );
    }

    /**
     * @return Sequence<Client>
     */
    public function accept(): Sequence
    {
        $sockets = $this->sockets;

        return ($this->watch)()
            ->map(
                static fn($ready) => $ready
                    ->toRead()
                    ->keep(Instance::of(Socket::class))
                    ->filter(static fn($ready) => $sockets->contains($ready)),
            )
            ->toSequence()
            ->flatMap(
                static fn($toRead) => Sequence::of(...$toRead->toList()),
            )
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
