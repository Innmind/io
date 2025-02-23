<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Readable;

use Innmind\IO\Frame;
use Innmind\IO\Previous\Exception\FailedToLoadStream;
use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

/**
 * @template-covariant F
 */
final class Frames
{
    /** @var Frame<F> */
    private Frame $frame;
    private Stream $stream;
    private Stream\Wait|Stream\Wait\WithHeartbeat $wait;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param Frame<F> $frame
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        Frame $frame,
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
    ) {
        $this->frame = $frame;
        $this->stream = $stream;
        $this->wait = $wait;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-mutation-free
     * @internal
     * @template A
     *
     * @param Frame<A> $frame
     * @param Maybe<Str\Encoding> $encoding
     *
     * @return self<A>
     */
    public static function of(
        Frame $frame,
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
    ): self {
        return new self($frame, $stream, $wait, $encoding);
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
        $read = fn(?int $size): Maybe => ($this->wait)($this->stream)
            ->flatMap(static fn($stream) => $stream->read($size))
            ->otherwise(fn() => Maybe::just(Str::of(''))->filter(
                fn() => $this->stream->end(),
            ))
            ->map(fn($chunk) => $this->encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
        $readLine = fn(): Maybe => ($this->wait)($this->stream)
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
