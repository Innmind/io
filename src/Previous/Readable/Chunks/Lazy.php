<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Readable\Chunks;

use Innmind\IO\Previous\Exception\FailedToLoadStream;
use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
};

final class Lazy
{
    private Stream $stream;
    private Stream\Wait|Stream\Wait\WithHeartbeat $wait;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var positive-int */
    private int $size;
    /** @var callable(Stream): void */
    private $rewind;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     * @param callable(Stream): void $rewind
     */
    private function __construct(
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
        int $size,
        callable $rewind,
    ) {
        $this->stream = $stream;
        $this->wait = $wait;
        $this->encoding = $encoding;
        $this->size = $size;
        $this->rewind = $rewind;
    }

    /**
     * @psalm-mutation-free
     * @internal
     *
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    public static function of(
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
        int $size,
    ): self {
        return new self($stream, $wait, $encoding, $size, static fn() => null);
    }

    /**
     * @psalm-mutation-free
     */
    public function rewindable(): self
    {
        return new self(
            $this->stream,
            $this->wait,
            $this->encoding,
            $this->size,
            static fn(Stream $stream) => $stream->rewind()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadStream,
            ),
        );
    }

    /**
     * @psalm-mutation-free
     *
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
                yield ($this->wait)($this->stream)
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
