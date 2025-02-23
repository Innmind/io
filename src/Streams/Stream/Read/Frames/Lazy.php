<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream\Read\Frames;

use Innmind\IO\{
    Frame,
    Previous\Readable,
};
use Innmind\Immutable\Sequence;

/**
 * @template T
 */
final class Lazy
{
    /**
     * @param Frame<T> $frame
     */
    private function __construct(
        private Readable\Stream $stream,
        private Frame $frame,
        private bool $blocking,
        private bool $rewindable,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param Frame<A> $frame
     *
     * @return self<A>
     */
    public static function of(
        Readable\Stream $stream,
        Frame $frame,
        bool $blocking,
    ): self {
        return new self($stream, $frame, $blocking, false);
    }

    /**
     * @psalm-mutation-free
     */
    public function rewindable(): self
    {
        return new self(
            $this->stream,
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
        $frame = $this->frame;
        $blocking = $this->blocking;
        $rewindable = $this->rewindable;

        return Sequence::lazy(static function() use (
            $stream,
            $frame,
            $blocking,
            $rewindable,
        ) {
            if ($stream->unwrap()->closed()) {
                return;
            }

            $result = match ($blocking) {
                true => $stream->unwrap()->blocking(),
                false => $stream->unwrap()->nonBlocking(),
            };
            $result->match(
                static fn() => null,
                static fn() => throw new \RuntimeException('Failed to set blocking mode'),
            );

            if ($rewindable) {
                $stream->unwrap()->rewind()->match(
                    static fn() => null,
                    static fn() => throw new \RuntimeException('Failed to read stream'),
                );
            }

            yield $stream
                ->frames($frame)
                ->sequence();
        })->flatMap(static fn($frames) => $frames);
    }
}
