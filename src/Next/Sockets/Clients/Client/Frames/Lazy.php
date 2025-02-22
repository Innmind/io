<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Clients\Client\Frames;

use Innmind\IO\{
    Next\Frame,
    Sockets\Client as Previous,
};
use Innmind\Immutable\Sequence;

/**
 * @template T
 */
final class Lazy
{
    /**
     * @param Frame<T> $frame
     */
    private function __construct(
        private Previous $socket,
        private Frame $frame,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param Frame<A> $frame
     *
     * @return self<A>
     */
    public static function of(Previous $socket, Frame $frame): self
    {
        return new self($socket, $frame);
    }

    /**
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        return $this
            ->socket
            ->frames($this->frame)
            ->sequence();
    }
}
