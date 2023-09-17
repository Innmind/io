<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\{
    Readable as LowLevelStream,
    Watch,
};
use Innmind\Immutable\{
    Maybe,
    Str,
};

final class Stream
{
    private LowLevelStream $stream;
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;
    /** @var callable(LowLevelStream): Maybe<LowLevelStream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param callable(LowLevelStream): Maybe<LowLevelStream> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        callable $watch,
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
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     */
    public static function of(
        callable $watch,
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
        return new self(
            $this->watch,
            $this->stream,
            fn(LowLevelStream $stream) => ($this->watch)(null)
                ->forRead($stream)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $stream,
                )),
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(ElapsedPeriod $timeout): self
    {
        return new self(
            $this->watch,
            $this->stream,
            fn(LowLevelStream $stream) => ($this->watch)($timeout)
                ->forRead($stream)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $stream,
                )),
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
}
