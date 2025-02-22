<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Clients;

use Innmind\IO\{
    Next\Sockets\Clients\Client\Frames,
    Next\Frame,
    Previous\Sockets\Client as Previous,
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
        private Previous $socket,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $socket): self
    {
        return new self($socket);
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->socket->toEncoding($encoding),
        );
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
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->socket->watch(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->socket->timeoutAfter($period->asElapsedPeriod()),
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
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $chunks
     */
    public function heartbeatWith(callable $chunks): self
    {
        return new self(
            $this->socket->heartbeatWith($chunks),
        );
    }

    /**
     * This method is called when using a heartbeat is defined to abort
     * restarting the watching of the socket. It is also used to abort when
     * sending messages (the abort is triggered before trying to send a message).
     *
     * Use this method to abort the watch when you receive signals.
     *
     * @psalm-mutation-free
     *
     * @param callable(): bool $abort
     */
    public function abortWhen(callable $abort): self
    {
        return new self(
            $this->socket->abortWhen($abort),
        );
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        return $this->socket->send($chunks);
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
        return Frames::of($this->socket, $frame);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return $this
            ->socket
            ->unwrap()
            ->close()
            ->maybe();
    }
}
