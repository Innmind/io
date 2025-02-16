<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Servers;

use Innmind\IO\Next\Sockets\{
    Servers\Server\Pool,
    Clients\Client,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class Server
{
    private function __construct()
    {
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return $this;
    }

    /**
     * @return Maybe<Client>
     */
    public function accept(): Maybe
    {
        /** @var Maybe<Client> */
        return Maybe::nothing();
    }

    public function pool(self $server): Pool
    {
        return Pool::of($this, $server);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return Maybe::just(new SideEffect);
    }
}
