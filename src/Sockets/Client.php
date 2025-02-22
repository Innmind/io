<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\IO\{
    Readable\Chunks,
    Readable\Frames,
    Readable\Lines,
    Next\Frame,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\IO\Internal\Socket\Client as Socket;
use Innmind\IO\Internal\Stream\{
    Stream,
    Size,
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
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;
    /** @var callable(Stream): Maybe<Stream> */
    private $readyToRead;
    /** @var callable(Stream): Maybe<Stream> */
    private $readyToWrite;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var Maybe<callable(): Sequence<Str>> */
    private Maybe $heartbeat;
    /** @var callable(): bool */
    private $abort;

    /**
     * @psalm-mutation-free
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param callable(Stream): Maybe<Stream> $readyToRead
     * @param callable(Stream): Maybe<Stream> $readyToWrite
     * @param Maybe<Str\Encoding> $encoding
     * @param Maybe<callable(): Sequence<Str>> $heartbeat
     * @param callable(): bool $abort
     */
    private function __construct(
        callable $watch,
        Stream $socket,
        callable $readyToRead,
        callable $readyToWrite,
        Maybe $encoding,
        Maybe $heartbeat,
        callable $abort,
    ) {
        $this->watch = $watch;
        $this->socket = $socket;
        $this->readyToRead = $readyToRead;
        $this->readyToWrite = $readyToWrite;
        $this->encoding = $encoding;
        $this->heartbeat = $heartbeat;
        $this->abort = $abort;
    }

    /**
     * @psalm-mutation-free
     * @internal
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     */
    public static function of(
        callable $watch,
        Stream $socket,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();
        /** @var Maybe<callable(): Sequence<Str>> */
        $heartbeat = Maybe::nothing();

        return new self(
            $watch,
            $socket,
            static fn(Stream $socket) => Maybe::just($socket),
            static fn(Stream $socket) => Maybe::just($socket),
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
            $this->readyToRead,
            $this->readyToWrite,
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
            $this->watch,
            $this->socket,
            fn(Stream $socket) => ($this->watch)(null)
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Stream::class)),
            fn(Stream $socket) => ($this->watch)(null)
                ->forWrite($socket)()
                ->map(static fn($ready) => $ready->toWrite())
                ->flatMap(static fn($toWrite) => $toWrite->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Stream::class)),
            $this->encoding,
            $this->heartbeat,
            $this->abort,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(ElapsedPeriod $timeout): self
    {
        /** @var self<T> */
        return new self(
            $this->watch,
            $this->socket,
            fn(Stream $socket) => ($this->watch)($timeout)
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Stream::class)),
            fn(Stream $socket) => ($this->watch)($timeout)
                ->forWrite($socket)()
                ->map(static fn($ready) => $ready->toWrite())
                ->flatMap(static fn($toWrite) => $toWrite->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Stream::class)),
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
     * @param callable(): Sequence<Str> $provide
     */
    public function heartbeatWith(callable $provide): self
    {
        return new self(
            $this->watch,
            $this->socket,
            $this->readyToRead,
            $this->readyToWrite,
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
     * @param callable(): bool $abort
     */
    public function abortWhen(callable $abort): self
    {
        return new self(
            $this->watch,
            $this->socket,
            $this->readyToRead,
            $this->readyToWrite,
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
        // Using Sequence::matches() allows to stop on the first failure meaning
        // the whole sequence doesn't have to be unwrapped
        $allSent = $data
            ->map(fn($data) => $this->encoding->match(
                static fn($encoding) => $data->toEncoding($encoding),
                static fn() => $data,
            ))
            ->matches(
                fn($data) => (!($this->abort)()) && ($this->readyToWrite)($this->socket)
                    ->flatMap(
                        static fn($socket) => $socket
                            ->write($data)
                            ->maybe(),
                    )
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    ),
            );

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
     * @return Frames<Stream, F>
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
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        return $this->socket->size();
    }

    /**
     * @psalm-mutation-free
     *
     * @return callable(Stream): Maybe<Stream>
     */
    private function readyToRead(): callable
    {
        return $this->heartbeat->match(
            fn($provide) => function(Stream $socket) use ($provide) {
                do {
                    $ready = ($this->readyToRead)($socket);
                    $socketReadable = $ready->match(
                        static fn() => true,
                        static fn() => false,
                    );

                    if ($socketReadable) {
                        return $ready;
                    }

                    $sent = $this
                        ->send($provide())
                        ->match(
                            static fn() => true,
                            static fn() => false,
                        );

                    if (!$sent) {
                        /** @var Maybe<Stream> */
                        return Maybe::nothing();
                    }
                } while (!($this->abort)() && !$socket->closed());

                /** @var Maybe<Stream> */
                return Maybe::nothing();
            },
            fn() => $this->readyToRead,
        );
    }
}
