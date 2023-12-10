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
    Readable,
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

    /**
     * @psalm-mutation-free
     *
     * @param callable(?ElapsedPeriod): Watch $watch
     * @param T $socket
     * @param callable(T): Maybe<T> $readyToRead
     * @param callable(T): Maybe<Writable> $readyToWrite
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        callable $watch,
        Socket $socket,
        callable $readyToRead,
        callable $readyToWrite,
        Maybe $encoding,
    ) {
        $this->watch = $watch;
        $this->socket = $socket;
        $this->readyToRead = $readyToRead;
        $this->readyToWrite = $readyToWrite;
        $this->encoding = $encoding;
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

        /** @var self<A> */
        return new self(
            $watch,
            $socket,
            static fn(Socket $socket) => Maybe::just($socket),
            static fn(Socket $socket) => Maybe::just($socket),
            $encoding,
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
                fn($data) => ($this->readyToWrite)($this->socket)
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
            $this->readyToRead,
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
            $this->readyToRead,
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
            $this->readyToRead,
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
}
