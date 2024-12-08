<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream\Read\Frames;

use Innmind\IO\Next\Frame;
use Innmind\Immutable\Sequence;

/**
 * @template T
 */
final class Lazy
{
    /**
     * @param resource $resource
     * @param Frame<T> $frame
     */
    private function __construct(
        private $resource,
        private Frame $frame,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param resource $resource
     * @param Frame<A> $frame
     *
     * @return self<A>
     */
    public static function of($resource, Frame $frame): self
    {
        return new self($resource, $frame);
    }

    /**
     * @psalm-mutation-free
     */
    public function rewindable(): self
    {
        return $this;
    }

    /**
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        return Sequence::of();
    }
}
