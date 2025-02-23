<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Clients;

use Innmind\IO\{
    Sockets\Clients\Client\Frames,
    Streams\Stream,
    Frame,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

final class Client
{
    private function __construct(
        private Stream $stream,
        private Stream\Read $read,
        private Stream\Write $write,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Stream $stream): self
    {
        return new self(
            $stream,
            $stream->read(),
            $stream->write(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->stream,
            $this->read->toEncoding($encoding),
            $this->write,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function buffer(): self
    {
        return new self(
            $this->stream,
            $this->read->buffer(),
            $this->write,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->stream,
            $this->read->watch(),
            $this->write->watch(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->stream,
            $this->read->timeoutAfter($period),
            $this->write,
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
     * When reading from the socket, if a timeout occurs then it will send the
     * data provided by the callback and then restart watching for the socket
     * to be readable.
     *
     * Bear in mind that this is useful only when watching for multiple frames.
     * Watching for a single frames will not be affected.
     *
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $chunks
     */
    public function heartbeatWith(callable $chunks): self
    {
        return new self(
            $this->stream,
            $this->read->heartbeatWith($chunks),
            $this->write,
        );
    }

    /**
     * This method is called when using a heartbeat is defined to abort
     * restarting the watching of the socket. It is also used to abort when
     * sending messages (the abort is triggered before trying to send a message).
     *
     * Use this method to abort the watch when you receive signals.
     *
     * Bear in mind that this is useful only when watching for multiple frames.
     * Watching for a single frames will not be affected.
     *
     * @psalm-mutation-free
     *
     * @param callable(): bool $abort
     */
    public function abortWhen(callable $abort): self
    {
        return new self(
            $this->stream,
            $this->read->abortWhen($abort),
            $this->write->abortWhen($abort),
        );
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        return $this->write->sink($chunks);
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
        return Frames::of($this->read->frames($frame));
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return $this->stream->close();
    }
}
