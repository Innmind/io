<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\IO\Next\Frame;
use Innmind\IO\Exception\FailedToLoadStream;
use Innmind\IO\Internal\Stream as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

/**
 * @template-covariant T of LowLevelStream
 * @template-covariant F
 */
final class Frames
{
    /** @var Frame<F> */
    private Frame $frame;
    /** @var T */
    private LowLevelStream $stream;
    /** @var callable(T): Maybe<T> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param Frame<F> $frame
     * @param T $stream
     * @param callable(T): Maybe<T> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        Frame $frame,
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
    ) {
        $this->frame = $frame;
        $this->stream = $stream;
        $this->ready = $ready;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-mutation-free
     * @internal
     * @template A of LowLevelStream
     * @template B
     *
     * @param Frame<B> $frame
     * @param A $stream
     * @param callable(A): Maybe<A> $ready
     * @param Maybe<Str\Encoding> $encoding
     *
     * @return self<A, B>
     */
    public static function of(
        Frame $frame,
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
    ): self {
        return new self($frame, $stream, $ready, $encoding);
    }

    /**
     * @return Maybe<F>
     */
    public function one(): Maybe
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var callable(?positive-int): Maybe<Str>
         */
        $read = fn(?int $size): Maybe => ($this->ready)($this->stream)
            ->flatMap(static fn($stream) => $stream->read($size))
            ->otherwise(fn() => Maybe::just(Str::of(''))->filter(
                fn() => $this->stream->end(),
            ))
            ->map(fn($chunk) => $this->encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
        $readLine = fn(): Maybe => ($this->ready)($this->stream)
            ->flatMap(static fn($stream) => $stream->readLine())
            ->otherwise(fn() => Maybe::just(Str::of(''))->filter(
                fn() => $this->stream->end(),
            ))
            ->map(fn($chunk) => $this->encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));

        return ($this->frame)($read, $readLine);
    }

    /**
     * @return Sequence<F>
     */
    public function sequence(): Sequence
    {
        return Sequence::lazy(function() {
            while (!$this->stream->end()) {
                yield $this->one()->match(
                    static fn($frame): mixed => $frame,
                    static fn() => throw new FailedToLoadStream,
                );
            }
        });
    }
}
