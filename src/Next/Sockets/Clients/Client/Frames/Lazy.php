<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Clients\Client\Frames;

use Innmind\IO\Next\Frame;
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
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        return Sequence::of();
    }
}
