<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream\Read;

use Innmind\IO\{
    Next\Streams\Stream\Read\Frames\Lazy,
    Next\Frame,
    Readable,
};
use Innmind\Immutable\Maybe;

/**
 * @template T
 */
final class Frames
{
    /**
     * @param Frame<T> $frame
     */
    private function __construct(
        private Readable\Stream $stream,
        private Frame $frame,
        private bool $blocking,
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
    public static function of(
        Readable\Stream $stream,
        Frame $frame,
        bool $blocking,
    ): self {
        return new self($stream, $frame, $blocking);
    }

    /**
     * @return Maybe<T>
     */
    public function one(): Maybe
    {
        // todo handle non blocking
        return $this
            ->stream
            ->frames($this->frame)
            ->one();
    }

    /**
     * @return Lazy<T>
     */
    public function lazy(): Lazy
    {
        return Lazy::of(
            $this->stream,
            $this->frame,
            $this->blocking,
        );
    }
}
