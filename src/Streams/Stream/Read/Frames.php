<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream\Read;

use Innmind\IO\{
    Streams\Stream\Write,
    Streams\Stream\Read\Frames\Lazy,
    Frame,
    Internal\Stream,
    Internal\Watch,
    Internal\Reader,
    Exception\RuntimeException,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
    Sequence,
};

/**
 * @template T
 */
final class Frames
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
        );
    }

    /**
     * @return Attempt<T>
     */
    public function one(): Attempt
    {
        $stream = $this->stream;
        $wait = Stream\Wait::of($this->watch, $stream);
        $wait = $this->heartbeat->match(
            fn($provide) => $wait->withHeartbeat(
                $this->write,
                $provide,
                $this->abort,
            ),
            static fn() => $wait,
        );

        $result = match ($this->blocking) {
            true => $stream->blocking(),
            false => $stream->nonBlocking(),
        };
        $switched = $result->match(
            static fn() => true,
            static fn() => false,
        );

        if (!$switched) {
            /** @var Attempt<T> */
            return Attempt::error(new RuntimeException('Failed to switch blocking mode'));
        }

        $reader = Reader::of($wait, $this->encoding);

        return ($this->frame)($reader);
    }

    /**
     * @return Lazy<T>
     */
    public function lazy(): Lazy
    {
        return Lazy::of(
            $this->write,
            $this->stream,
            $this->watch,
            $this->encoding,
            $this->frame,
            $this->heartbeat,
            $this->abort,
            $this->blocking,
        );
    }
}
