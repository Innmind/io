<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream\Read\Frames;

use Innmind\IO\{
    Streams\Stream\Write,
    Frame,
    Exception\FailedToLoadStream,
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
     * @param Maybe<callable(): Sequence<Str>> $heartbeat
     * @param callable(): bool $abort
     */
    private function __construct(
        private Write $write,
        private Stream $stream,
        private Watch $watch,
        private Maybe $encoding,
        private Frame $frame,
        private Maybe $heartbeat,
        private $abort,
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
     * @param Maybe<callable(): Sequence<Str>> $heartbeat
     * @param callable(): bool $abort
     *
     * @return self<A>
     */
    public static function of(
        Write $write,
        Stream $stream,
        Watch $watch,
        Maybe $encoding,
        Frame $frame,
        Maybe $heartbeat,
        callable $abort,
        bool $blocking,
    ): self {
        return new self(
            $write,
            $stream,
            $watch,
            $encoding,
            $frame,
            $heartbeat,
            $abort,
            $blocking,
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function rewindable(): self
    {
        return new self(
            $this->write,
            $this->stream,
            $this->watch,
            $this->encoding,
            $this->frame,
            $this->heartbeat,
            $this->abort,
            $this->blocking,
            true,
        );
    }

    /**
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        $write = $this->write;
        $stream = $this->stream;
        $watch = $this->watch;
        $encoding = $this->encoding;
        $frame = $this->frame;
        $heartbeat = $this->heartbeat;
        $abort = $this->abort;
        $blocking = $this->blocking;
        $rewindable = $this->rewindable;

        return Sequence::lazy(static function() use (
            $write,
            $stream,
            $watch,
            $encoding,
            $frame,
            $heartbeat,
            $abort,
            $blocking,
            $rewindable,
        ) {
            if ($stream->closed()) {
                return;
            }

            $wait = Stream\Wait::of($watch, $stream);
            $wait = $heartbeat->match(
                static fn($provide) => $wait->withHeartbeat(
                    $write,
                    $provide,
                    $abort,
                ),
                static fn() => $wait,
            );
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
