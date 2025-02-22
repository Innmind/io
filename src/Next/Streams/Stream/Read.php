<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream;

use Innmind\IO\{
    Next\Streams\Stream\Read\Frames,
    Next\Streams\Stream\Read\Pool,
    Next\Frame,
    Readable,
    Internal,
    Internal\Capabilities,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\Str;

final class Read
{
    private function __construct(
        private Capabilities $capabilities,
        private Readable\Stream $stream,
        private bool $blocking,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Readable\Stream $stream,
    ): self {
        return new self($capabilities, $stream, true);
    }

    /**
     * @internal
     */
    public function internal(): Internal\Stream
    {
        return $this->stream->unwrap();
    }

    /**
     * @psalm-mutation-free
     */
    public function nonBlocking(): self
    {
        return new self(
            $this->capabilities,
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
            $this->capabilities,
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
            $this->capabilities,
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
            $this->capabilities,
            $this->stream->timeoutAfter($period->asElapsedPeriod()),
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function poll(): self
    {
        return $this->timeoutAfter(Period::second(0));
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
     * @param T $id
     *
     * @return Pool<T>
     */
    public function pool(mixed $id): Pool
    {
        return Pool::of(
            $this->capabilities,
            $this,
            $id,
        );
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
