<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Readable\Lines;

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
    /** @var callable(Stream): Maybe<Stream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var callable(Stream): void */
    private $rewind;

    /**
     * @psalm-mutation-free
     *
     * @param callable(Stream): Maybe<Stream> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param callable(Stream): void $rewind
     */
    private function __construct(
        Stream $stream,
        callable $ready,
        Maybe $encoding,
        callable $rewind,
    ) {
        $this->stream = $stream;
        $this->ready = $ready;
        $this->encoding = $encoding;
        $this->rewind = $rewind;
    }

    /**
     * @psalm-mutation-free
     * @internal
     *
     * @param callable(Stream): Maybe<Stream> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    public static function of(
        Stream $stream,
        callable $ready,
        Maybe $encoding,
    ): self {
        return new self($stream, $ready, $encoding, static fn() => null);
    }

    /**
     * @psalm-mutation-free
     */
    public function rewindable(): self
    {
        return new self(
            $this->stream,
            $this->ready,
            $this->encoding,
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
                // we yield an empty line when the readLine() call doesn't return
                // anything otherwise it will fail to load empty streams or
                // streams ending with the "end of line" character
                yield ($this->ready)($this->stream)
                    ->flatMap(static fn($stream) => $stream->readLine())
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
