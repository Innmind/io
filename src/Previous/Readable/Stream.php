<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Readable;

use Innmind\IO\{
    Frame,
    Stream\Size,
};
use Innmind\TimeContinuum\Period;
use Innmind\IO\Internal\{
    Stream as LowLevelStream,
    Watch,
};
use Innmind\Immutable\{
    Maybe,
    Str,
};

final class Stream
{
    private LowLevelStream $stream;
    private Watch $watch;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        Watch $watch,
        LowLevelStream $stream,
        Maybe $encoding,
    ) {
        $this->watch = $watch;
        $this->stream = $stream;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-mutation-free
     * @internal
     */
    public static function of(
        Watch $watch,
        LowLevelStream $stream,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();

        return new self(
            $watch,
            $stream,
            $encoding,
        );
    }

    public function unwrap(): LowLevelStream
    {
        return $this->stream;
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->watch,
            $this->stream,
            Maybe::just($encoding),
        );
    }

    /**
     * Wait forever for the stream to be ready to read before tryin to use it
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->watch->waitForever(),
            $this->stream,
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->watch->timeoutAfter($timeout),
            $this->stream,
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     */
    public function chunks(int $size): Chunks
    {
        return Chunks::of(
            $this->stream,
            LowLevelStream\Wait::of($this->watch, $this->stream),
            $this->encoding,
            $size,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function lines(): Lines
    {
        return Lines::of(
            $this->stream,
            LowLevelStream\Wait::of($this->watch, $this->stream),
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     * @template F
     *
     * @param Frame<F> $frame
     *
     * @return Frames<F>
     */
    public function frames(Frame $frame): Frames
    {
        return Frames::of(
            $frame,
            $this->stream,
            LowLevelStream\Wait::of($this->watch, $this->stream),
            $this->encoding,
        );
    }

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        return $this->stream->size();
    }
}
