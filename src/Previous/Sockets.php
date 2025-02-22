<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous;

use Innmind\IO\Internal\Watch;

final class Sockets
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
