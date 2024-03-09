<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets;

use Innmind\IO\Readable\{
    Chunks,
    Frames,
    Frame,
    Lines,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Socket\Client as Socket;
use Innmind\Stream\{
    Writable,
    Stream\Size,
    Watch,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Str,
    Predicate\Instance,
    SideEffect,
};

/**
 * @template-covariant T of Socket
 */
final class Client
{
    /** @var T */
    private Socket $socket;
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;
    /** @var callable(T): Maybe<T> */
    private $readyToRead;
    /** @var callable(T): Maybe<Writable> */
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
     * @param T $socket
     * @param callable(T): Maybe<T> $readyToRead
     * @param callable(T): Maybe<Writable> $readyToWrite
     * @param Maybe<Str\Encoding> $encoding
     * @param Maybe<callable(): Sequence<Str>> $heartbeat
     * @param callable(): bool $abort
     */
    private function __construct(
        callable $watch,
        Socket $socket,
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
     * @template A of Socket
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param A $socket
     *
     * @return self<A>
     */
    public static function of(
        callable $watch,
        Socket $socket,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();
        /** @var Maybe<callable(): Sequence<Str>> */
        $heartbeat = Maybe::nothing();

        /** @var self<A> */
        return new self(
            $watch,
            $socket,
            static fn(Socket $socket) => Maybe::just($socket),
            static fn(Socket $socket) => Maybe::just($socket),
            $encoding,
            $heartbeat,
            static fn() => false,
        );
    }

    /**
     * @return T
     */
    public function unwrap(): Socket
    {
        return $this->socket;
    }

    /**
     * @psalm-mutation-free
     *
     * @return self<T>
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
     *
     * @return self<T>
     */
    public function watch(): self
    {
        /** @var self<T> */
        return new self(
            $this->watch,
            $this->socket,
            fn(Socket $socket) => ($this->watch)(null)
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Socket::class)),
            fn(Socket $socket) => ($this->watch)(null)
                ->forWrite($socket)()
                ->map(static fn($ready) => $ready->toWrite())
                ->flatMap(static fn($toWrite) => $toWrite->find(
                    static fn($ready) => $ready === $socket,
                )),
            $this->encoding,
            $this->heartbeat,
            $this->abort,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @return self<T>
     */
    public function timeoutAfter(ElapsedPeriod $timeout): self
    {
        /** @var self<T> */
        return new self(
            $this->watch,
            $this->socket,
            fn(Socket $socket) => ($this->watch)($timeout)
                ->forRead($socket)()
                ->map(static fn($ready) => $ready->toRead())
                ->flatMap(static fn($toRead) => $toRead->find(
                    static fn($ready) => $ready === $socket,
                ))
                ->keep(Instance::of(Socket::class)),
            fn(Socket $socket) => ($this->watch)($timeout)
                ->forWrite($socket)()
                ->map(static fn($ready) => $ready->toWrite())
                ->flatMap(static fn($toWrite) => $toWrite->find(
                    static fn($ready) => $ready === $socket,
                )),
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
     *
     * @return self<T>
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
     *
     * @return self<T>
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
     * @return Frames<T, F>
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
     * @return callable(T): Maybe<T>
     */
    private function readyToRead(): callable
    {
        return $this->heartbeat->match(
            fn($provide) => function(Socket $socket) use ($provide) {
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
                        /** @var Maybe<T> */
                        return Maybe::nothing();
                    }
                } while (!($this->abort)() && !$socket->closed());

                /** @var Maybe<T> */
                return Maybe::nothing();
            },
            fn() => $this->readyToRead,
        );
    }
}
