<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream;

use Innmind\IO\{
    Next\Streams\Stream\Read\Frames,
    Next\Frame,
    Readable,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\Str;

final class Read
{
    private function __construct(
        private Readable\Stream $stream,
        private bool $blocking,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Readable\Stream $stream): self
    {
        return new self($stream, true);
    }

    /**
     * @psalm-mutation-free
     */
    public function nonBlocking(): self
    {
        return new self(
            $this->stream,
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->stream->toEncoding($encoding),
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->stream->watch(),
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->stream->timeoutAfter($period->asElapsedPeriod()),
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function buffer(): self
    {
        // todo
        return $this;
    }

    /**
     * @template T
     *
     * @param Frame<T> $frame
     *
     * @return Frames<T>
     */
    public function frames(Frame $frame): Frames
    {
        return Frames::of(
            $this->stream,
            $frame,
            $this->blocking,
        );
    }
}
