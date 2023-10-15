<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Chunks;

use Innmind\IO\Exception\FailedToLoadStream;
use Innmind\Stream\Readable as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
};

final class Lazy
{
    private LowLevelStream $stream;
    /** @var callable(LowLevelStream): Maybe<LowLevelStream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var positive-int */
    private int $size;
    /** @var callable(LowLevelStream): void */
    private $rewind;

    /**
     * @psalm-mutation-free
     *
     * @param callable(LowLevelStream): Maybe<LowLevelStream> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     * @param callable(LowLevelStream): void $rewind
     */
    private function __construct(
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
        int $size,
        callable $rewind,
    ) {
        $this->stream = $stream;
        $this->ready = $ready;
        $this->encoding = $encoding;
        $this->size = $size;
        $this->rewind = $rewind;
    }

    /**
     * @psalm-mutation-free
     * @internal
     *
     * @param callable(LowLevelStream): Maybe<LowLevelStream> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    public static function of(
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
        int $size,
    ): self {
        return new self($stream, $ready, $encoding, $size, static fn() => null);
    }

    public function rewindable(): self
    {
        return new self(
            $this->stream,
            $this->ready,
            $this->encoding,
            $this->size,
            static fn(LowLevelStream $stream) => $stream->rewind()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadStream,
            ),
        );
    }

    /**
     * @return Sequence<Str>
     */
    public function sequence(): Sequence
    {
        return Sequence::lazy(function($register) {
            $register(fn() => ($this->rewind)($this->stream));
            ($this->rewind)($this->stream);

            do {
                // we yield an empty line when the read() call doesn't return
                // anything otherwise it will fail to load empty streams or
                // streams ending with the "end of line" character
                yield ($this->ready)($this->stream)
                    ->flatMap(fn($stream) => $stream->read($this->size))
                    ->map(fn($chunk) => $this->encoding->match(
                        static fn($encoding) => $chunk->toEncoding($encoding),
                        static fn() => $chunk,
                    ))
                    ->match(
                        static fn($chunk) => $chunk,
                        fn() => match ($this->stream->end()) {
                            true => $this->encoding->match(
                                static fn($encoding) => Str::of('')->toEncoding($encoding),
                                static fn() => Str::of(''),
                            ),
                            false => throw new FailedToLoadStream,
                        },
                    );
            } while (!$this->stream->end());

            ($this->rewind)($this->stream);
        });
    }
}
