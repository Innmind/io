<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\IO\Internal\Socket\Server as Socket;
use Innmind\IO\Internal\Watch;

final class Servers
{
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;

    /**
     * @psalm-mutation-free
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     */
    private function __construct(callable $watch)
    {
        $this->watch = $watch;
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     */
    public static function of(callable $watch): self
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
