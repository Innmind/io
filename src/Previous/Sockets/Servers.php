<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets;

use Innmind\IO\Internal\Socket\Server as Socket;
use Innmind\IO\Internal\Watch;

final class Servers
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
    public function wrap(Socket $socket): Server
    {
        return Server::of($this->watch, $socket);
    }
}
