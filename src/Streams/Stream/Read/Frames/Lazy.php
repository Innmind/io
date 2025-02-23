<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream\Read\Frames;

use Innmind\IO\{
    Frame,
    Previous\Exception\FailedToLoadStream,
    Internal\Stream,
    Internal\Watch,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
};

/**
 * @template T
 */
final class Lazy
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
        private bool $rewindable,
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
        return new self($stream, $watch, $encoding, $frame, $blocking, false);
    }

    /**
     * @psalm-mutation-free
     */
    public function rewindable(): self
    {
        return new self(
            $this->stream,
            $this->watch,
            $this->encoding,
            $this->frame,
            $this->blocking,
            true,
        );
    }

    /**
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        $stream = $this->stream;
        $watch = $this->watch;
        $encoding = $this->encoding;
        $frame = $this->frame;
        $blocking = $this->blocking;
        $rewindable = $this->rewindable;

        return Sequence::lazy(static function() use (
            $stream,
            $watch,
            $encoding,
            $frame,
            $blocking,
            $rewindable,
        ) {
            if ($stream->closed()) {
                return;
            }

            $wait = Stream\Wait::of($watch, $stream);
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

            $result = match ($blocking) {
                true => $stream->blocking(),
                false => $stream->nonBlocking(),
            };
            $result->match(
                static fn() => null,
                static fn() => throw new \RuntimeException('Failed to set blocking mode'),
            );

            if ($rewindable) {
                $stream->rewind()->match(
                    static fn() => null,
                    static fn() => throw new FailedToLoadStream,
                );
            }

            while (!$stream->end()) {
                yield $frame($read, $readLine)->match(
                    static fn($frame): mixed => $frame,
                    static fn() => throw new FailedToLoadStream,
                );
            }
        });
    }
}
