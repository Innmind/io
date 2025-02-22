<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Servers;

use Innmind\IO\{
    Sockets\Servers\Server\Pool,
    Sockets\Clients\Client,
    Previous\Sockets\Server as Previous,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class Server
{
    private function __construct(
        private Previous $socket,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $socket): self
    {
        return new self($socket);
    }

    /**
     * @internal
     */
    public function internal(): Previous
    {
        return $this->socket;
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->socket->watch(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->socket->timeoutAfter($period->asElapsedPeriod()),
        );
    }

    /**
     * @return Maybe<Client>
     */
    public function accept(): Maybe
    {
        return $this
            ->socket
            ->accept()
            ->map(Client::of(...));
    }

    public function pool(self $server): Pool
    {
        return Pool::of($this->socket->with($server->socket));
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return $this
            ->socket
            ->unwrap()
            ->close()
            ->maybe();
    }
}
