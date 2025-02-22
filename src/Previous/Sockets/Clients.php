<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets;

use Innmind\IO\Internal\Stream;
use Innmind\IO\Internal\Watch;

final class Clients
{
    private Watch $watch;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Watch $watch)
    {
        $this->watch = $watch;
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function of(Watch $watch): self
    {
        return new self($watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function wrap(Stream $socket): Client
    {
        return Client::of($this->watch, $socket);
    }
}
