<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Clients;

use Innmind\IO\Next\{
    Sockets\Clients\Client\Frames,
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
    private function __construct()
    {
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function buffer(): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function poll(): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $chunks
     */
    public function heartbeatWith(callable $chunks): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(): bool $abort
     */
    public function abortWhen(callable $abort): self
    {
        return $this;
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        return Maybe::just(new SideEffect);
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
        return Frames::of($frame);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return Maybe::just(new SideEffect);
    }
}
