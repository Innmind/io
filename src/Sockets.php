<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\IO\Low\Stream\Watch;

final class Sockets
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
    public function clients(): Sockets\Clients
    {
        return Sockets\Clients::of($this->watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function servers(): Sockets\Servers
    {
        return Sockets\Servers::of($this->watch);
    }
}
