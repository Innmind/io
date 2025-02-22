<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Servers\Server;

use Innmind\IO\{
    Next\Sockets\Servers\Server,
    Next\Sockets\Clients\Client,
    Previous\Sockets\Server\Pool as Previous,
};
use Innmind\Immutable\Sequence;

final class Pool
{
    private function __construct(
        private Previous $pool,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $pool): self
    {
        return new self($pool);
    }

    /**
     * @psalm-mutation-free
     */
    public function with(Server $server): self
    {
        // todo automatically determine the shortest timeout to watch for
        return new self($this->pool->with($server->internal()));
    }

    /**
     * @return Sequence<Client>
     */
    public function accept(): Sequence
    {
        return $this
            ->pool
            ->watch() // todo remove when shortest timeout is automatically determined
            ->accept()
            ->map(Client::of(...));
    }
}
