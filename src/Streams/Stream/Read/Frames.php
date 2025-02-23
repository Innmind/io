<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream\Read;

use Innmind\IO\{
    Streams\Stream\Read\Frames\Lazy,
    Frame,
    Internal\Stream,
    Internal\Watch,
};
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @template T
 */
final class Frames
{
    /**
     * @param Maybe<Str\Encoding> $encoding
     * @param Frame<T> $frame
     */
    private function __construct(
        private Stream $stream,
        private Watch $watch,
        private Maybe $encoding,
        private Frame $frame,
        private bool $blocking,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param Maybe<Str\Encoding> $encoding
     * @param Frame<A> $frame
     *
     * @return self<A>
     */
    public static function of(
        Stream $stream,
        Watch $watch,
        Maybe $encoding,
        Frame $frame,
        bool $blocking,
    ): self {
        return new self($stream, $watch, $encoding, $frame, $blocking);
    }

    /**
     * @return Maybe<T>
     */
    public function one(): Maybe
    {
        $stream = $this->stream;
        $wait = Stream\Wait::of($this->watch, $stream);
        $encoding = $this->encoding;

        $result = match ($this->blocking) {
            true => $stream->blocking(),
            false => $stream->nonBlocking(),
        };
        $switched = $result->match(
            static fn() => true,
            static fn() => false,
        );

        if (!$switched) {
            /** @var Maybe<T> */
            return Maybe::nothing();
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var callable(?positive-int): Maybe<Str>
         */
        $read = static fn(?int $size): Maybe => $wait()
            ->flatMap(static fn($stream) => $stream->read($size))
            ->otherwise(static fn() => Maybe::just(Str::of(''))->filter(
                static fn() => $stream->end(),
            ))
            ->map(static fn($chunk) => $encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
        $readLine = static fn(): Maybe => $wait()
            ->flatMap(static fn($stream) => $stream->readLine())
            ->otherwise(static fn() => Maybe::just(Str::of(''))->filter(
                static fn() => $stream->end(),
            ))
            ->map(static fn($chunk) => $encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));

        return ($this->frame)($read, $readLine);
    }

    /**
     * @return Lazy<T>
     */
    public function lazy(): Lazy
    {
        return Lazy::of(
            $this->stream,
            $this->watch,
            $this->encoding,
            $this->frame,
            $this->blocking,
        );
    }
}
