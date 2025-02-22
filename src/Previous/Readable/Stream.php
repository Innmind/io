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
    /** @var callable(LowLevelStream): Maybe<LowLevelStream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param callable(LowLevelStream): Maybe<LowLevelStream> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        Watch $watch,
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
    ) {
        $this->watch = $watch;
        $this->stream = $stream;
        $this->ready = $ready;
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
            static fn(LowLevelStream $stream) => Maybe::just($stream),
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
            $this->ready,
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
        $watch = $this->watch->waitForever();

        return new self(
            $watch,
            $this->stream,
            static fn(LowLevelStream $stream) => $watch
                ->forRead($stream)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(
                    static fn($toRead) => $toRead
                        ->find(
                            static fn($ready) => $ready === $stream,
                        )
                        ->map(static fn() => $stream),
                ),
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        $watch = $this->watch->timeoutAfter($timeout);

        return new self(
            $watch,
            $this->stream,
            static fn(LowLevelStream $stream) => $watch
                ->forRead($stream)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(
                    static fn($toRead) => $toRead
                        ->find(
                            static fn($ready) => $ready === $stream,
                        )
                        ->map(static fn() => $stream),
                ),
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
            $this->ready,
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
            $this->ready,
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
            $this->ready,
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
