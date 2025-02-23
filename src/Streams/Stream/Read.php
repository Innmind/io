<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream;

use Innmind\IO\{
    Streams\Stream\Read\Frames,
    Streams\Stream\Read\Pool,
    Frame,
    Internal\Capabilities,
    Internal\Stream,
    Internal\Watch,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

final class Read
{
    /**
     * @param Maybe<Str\Encoding> $encoding
     * @param Maybe<callable(): Sequence<Str>> $heartbeat
     * @param callable(): bool $abort
     */
    private function __construct(
        private Write $write,
        private Capabilities $capabilities,
        private Stream $stream,
        private Watch $watch,
        private Maybe $encoding,
        private Maybe $heartbeat,
        private $abort,
        private bool $blocking,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Write $write,
        Capabilities $capabilities,
        Stream $stream,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();
        /** @var Maybe<callable(): Sequence<Str>> */
        $heartbeat = Maybe::nothing();

        return new self(
            $write,
            $capabilities,
            $stream,
            $capabilities->watch(),
            $encoding,
            $heartbeat,
            static fn() => false,
            true,
        );
    }

    /**
     * @internal
     */
    public function internal(): Stream
    {
        return $this->stream;
    }

    /**
     * @psalm-mutation-free
     */
    public function nonBlocking(): self
    {
        return new self(
            $this->write,
            $this->capabilities,
            $this->stream,
            $this->watch,
            $this->encoding,
            $this->heartbeat,
            $this->abort,
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->write,
            $this->capabilities,
            $this->stream,
            $this->watch,
            Maybe::just($encoding),
            $this->heartbeat,
            $this->abort,
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->write->watch(),
            $this->capabilities,
            $this->stream,
            $this->watch->waitForever(),
            $this->encoding,
            $this->heartbeat,
            $this->abort,
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->write,
            $this->capabilities,
            $this->stream,
            $this->watch->timeoutAfter($period),
            $this->encoding,
            $this->heartbeat,
            $this->abort,
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function poll(): self
    {
        return $this->timeoutAfter(Period::second(0));
    }

    /**
     * @psalm-mutation-free
     */
    public function buffer(): self
    {
        // todo
        return $this;
    }

    /**
     * When reading from the stream, if a timeout occurs then it will send the
     * data provided by the callback and then restart watching for the stream
     * to be readable.
     *
     * Bear in mind that this is useful only when watching for multiple frames.
     * Pooling doesn't support this feature.
     *
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $provide
     */
    public function heartbeatWith(callable $provide): self
    {
        return new self(
            $this->write,
            $this->capabilities,
            $this->stream,
            $this->watch,
            $this->encoding,
            Maybe::just($provide),
            $this->abort,
            $this->blocking,
        );
    }

    /**
     * This method is called when using a heartbeat is defined to abort
     * restarting the watching of the stream. It is also used to abort when
     * sending messages (the abort is triggered before trying to send a message).
     *
     * Use this method to abort the watch when you receive signals.
     *
     * Bear in mind that this is useful only when watching for multiple frames.
     * Pooling doesn't support this feature.
     *
     * @psalm-mutation-free
     *
     * @param callable(): bool $abort
     */
    public function abortWhen(callable $abort): self
    {
        return new self(
            $this->write,
            $this->capabilities,
            $this->stream,
            $this->watch,
            $this->encoding,
            $this->heartbeat,
            $abort,
            $this->blocking,
        );
    }

    /**
     * @template T
     *
     * @param T $id
     *
     * @return Pool<T>
     */
    public function pool(mixed $id): Pool
    {
        return Pool::of(
            $this->capabilities,
            $this,
            $id,
        );
    }

    /**
     * @template T
     *
     * @param Frame<T> $frame
     *
     * @return Frames<T>
     */
    public function frames(Frame $frame): Frames
    {
        return Frames::of(
            $this->write,
            $this->stream,
            $this->watch,
            $this->encoding,
            $frame,
            $this->heartbeat,
            $this->abort,
            $this->blocking,
        );
    }
}
