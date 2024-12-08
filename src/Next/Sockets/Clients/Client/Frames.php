<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Clients\Client;

use Innmind\IO\Next\{
    Sockets\Clients\Client\Frames\Lazy,
    Frame,
};
use Innmind\Immutable\Maybe;

/**
 * @template T
 */
final class Frames
{
    private function __construct(
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
    public static function of(Frame $frame): self
    {
        return new self($frame);
    }

    /**
     * @return Maybe<T>
     */
    public function one(): Maybe
    {
        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    /**
     * @return Lazy<T>
     */
    public function lazy(): Lazy
    {
        return Lazy::of($this->frame);
    }
}
