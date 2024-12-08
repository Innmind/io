<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream\Read;

use Innmind\IO\Next\{
    Streams\Stream\Read\Frames\Lazy,
    Frame,
};
use Innmind\Immutable\Maybe;

/**
 * @template T
 */
final class Frames
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
        return Lazy::of($this->resource, $this->frame);
    }
}
