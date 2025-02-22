<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Clients\Client;

use Innmind\IO\{
    Sockets\Clients\Client\Frames\Lazy,
    Frame,
    Previous\Sockets\Client as Previous,
};
use Innmind\Immutable\Maybe;

/**
 * @template T
 */
final class Frames
{
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
     * @return Maybe<T>
     */
    public function one(): Maybe
    {
        return $this
            ->socket
            ->frames($this->frame)
            ->one();
    }

    /**
     * @return Lazy<T>
     */
    public function lazy(): Lazy
    {
        return Lazy::of($this->socket, $this->frame);
    }
}
