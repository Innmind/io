<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Servers;

use Innmind\IO\{
    Sockets\Servers\Server\Pool,
    Sockets\Clients\Client,
    Streams\Stream,
    Internal\Capabilities,
    Internal\Socket\Server as Socket,
    Internal\Watch,
    Exception\RuntimeException,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
    Predicate\Instance,
};

final class Server
{
    private function __construct(
        private Capabilities $capabilities,
        private Watch $watch,
        private Socket $socket,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Watch $watch,
        Socket $socket,
    ): self {
        return new self($capabilities, $watch->forRead($socket), $socket);
    }

    /**
     * @internal
     */
    public function unwrap(): Watch
    {
        return $this->watch;
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->capabilities,
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
            $this->capabilities,
            $this->watch->timeoutAfter($period),
            $this->socket,
        );
    }

    /**
     * @return Attempt<Client>
     */
    public function accept(): Attempt
    {
        $socket = $this->socket;

        return ($this->watch)()
            ->map(static fn($ready) => $ready->toRead())
            ->flatMap(
                static fn($toRead) => $toRead
                    ->find(static fn($ready) => $ready === $socket)
                    ->keep(Instance::of(Socket::class))
                    ->match(
                        static fn($socket) => $socket->accept(),
                        static fn() => Attempt::error(new RuntimeException('Stream not ready')),
                    ),
            )
            ->map(fn($socket) => Client::of(
                Stream::of(
                    $this->capabilities,
                    $socket,
                ),
            ));
    }

    public function pool(self $server): Pool
    {
        return Pool::of($this->capabilities, $this->watch->forRead(
            $server->socket,
        ));
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt
    {
        return $this->socket->close();
    }
}
