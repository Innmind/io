<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Sockets;

use Innmind\IO\{
    Previous\Readable\Chunks,
    Previous\Readable\Frames,
    Previous\Readable\Lines,
    Frame,
};
use Innmind\TimeContinuum\Period;
use Innmind\IO\Internal\Socket\Client as Socket;
use Innmind\IO\Internal\{
    Stream,
    Watch,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Str,
    Predicate\Instance,
    SideEffect,
};

final class Client
{
    private Stream $socket;
    private Watch $watch;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var Maybe<callable(): Sequence<Str>> */
    private Maybe $heartbeat;
    /** @var callable(): bool */
    private $abort;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Str\Encoding> $encoding
     * @param Maybe<callable(): Sequence<Str>> $heartbeat
     * @param callable(): bool $abort
     */
    private function __construct(
        Watch $watch,
        Stream $socket,
        Maybe $encoding,
        Maybe $heartbeat,
        callable $abort,
    ) {
        $this->watch = $watch;
        $this->socket = $socket;
        $this->encoding = $encoding;
        $this->heartbeat = $heartbeat;
        $this->abort = $abort;
    }

    /**
     * @psalm-mutation-free
     * @internal
     */
    public static function of(
        Watch $watch,
        Stream $socket,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();
        /** @var Maybe<callable(): Sequence<Str>> */
        $heartbeat = Maybe::nothing();

        return new self(
            $watch,
            $socket,
            $encoding,
            $heartbeat,
            static fn() => false,
        );
    }

    public function unwrap(): Stream
    {
        return $this->socket;
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->watch,
            $this->socket,
            Maybe::just($encoding),
            $this->heartbeat,
            $this->abort,
        );
    }

    /**
     * Wait forever for the socket to be ready to read before tryin to use it
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->watch->waitForever(),
            $this->socket,
            $this->encoding,
            $this->heartbeat,
            $this->abort,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->watch->timeoutAfter($timeout),
            $this->socket,
            $this->encoding,
            $this->heartbeat,
            $this->abort,
        );
    }

    /**
     * When reading from the socket, if a timeout occurs then it will send the
     * data provided by the callback and then restart watching for the socket
     * to be readable.
     *
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $provide
     */
    public function heartbeatWith(callable $provide): self
    {
        return new self(
            $this->watch,
            $this->socket,
            $this->encoding,
            Maybe::just($provide),
            $this->abort,
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
            $this->watch,
            $this->socket,
            $this->encoding,
            $this->heartbeat,
            $abort,
        );
    }

    /**
     * @param Sequence<Str> $data
     *
     * @return Maybe<SideEffect>
     */
    public function send(Sequence $data): Maybe
    {
        $socket = $this->socket;
        // Using Sequence::matches() allows to stop on the first failure meaning
        // the whole sequence doesn't have to be unwrapped
        $allSent = $data
            ->map(fn($data) => $this->encoding->match(
                static fn($encoding) => $data->toEncoding($encoding),
                static fn() => $data,
            ))
            ->matches(function($data) use ($socket) {
                if (($this->abort)()) {
                    return false;
                }

                return $this
                    ->watch
                    ->forWrite($socket)()
                    ->map(static fn($ready) => $ready->toWrite())
                    ->flatMap(static fn($toWrite) => $toWrite->find(
                        static fn($ready) => $ready === $socket,
                    ))
                    ->keep(Instance::of(Stream::class))
                    ->flatMap(
                        static fn($socket) => $socket
                            ->write($data)
                            ->maybe(),
                    )
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    );
            });

        /** @var Maybe<SideEffect> */
        return match ($allSent) {
            true => Maybe::just(new SideEffect),
            false => Maybe::nothing(),
        };
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     */
    public function chunks(int $size): Chunks
    {
        return Chunks::of(
            $this->socket,
            $this->readyToRead(),
            $this->encoding,
            $size,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function lines(): Lines
    {
        return Lines::of(
            $this->socket,
            $this->readyToRead(),
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     * @template F
     *
     * @param Frame<F> $frame
     *
     * @return Frames<F>
     */
    public function frames(Frame $frame): Frames
    {
        return Frames::of(
            $frame,
            $this->socket,
            $this->readyToRead(),
            $this->encoding,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @return callable(Stream): Maybe<Stream>
     */
    private function readyToRead(): callable
    {
        $wait = Stream\Wait::of($this->watch);
        $send = $this->send(...);
        $abort = $this->abort;

        return $this->heartbeat->match(
            static fn($provide) => Stream\Wait\WithHeartbeat::of(
                $wait,
                $send,
                $provide,
                $abort,
            ),
            static fn() => $wait,
        );
    }
}
