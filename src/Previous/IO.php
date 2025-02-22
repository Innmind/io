<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous;

use Innmind\IO\Internal\Watch;

final class IO
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
     * @psalm-pure
     */
    public static function of(Watch $watch): self
    {
        return new self($watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function readable(): Readable
    {
        return Readable::of($this->watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function sockets(): Sockets
    {
        return Sockets::of($this->watch);
    }
}
