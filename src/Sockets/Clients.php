<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\IO\Low\Socket\Client as Socket;
use Innmind\IO\Low\Stream\Watch;

final class Clients
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
    public function wrap(Socket $socket): Client
    {
        return Client::of($this->watch, $socket);
    }
}
